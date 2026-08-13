<?php
/**
 * Express checkout AJAX endpoints.
 *
 * @package Monei
 */

namespace Monei\Services\express;

use Monei\Core\ContainerProvider;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;
use WC_Data_Store;
use WC_Product;
use WC_Product_Variation;
use WC_Session_Handler;
use WC_Session;
use WC_Validation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves the `wc-ajax` endpoints the express checkout wallet buttons call.
 *
 * Nonce strategy under page caching: product and cart pages are the pages a store
 * caches most aggressively, so a nonce printed into that HTML is stale by the time
 * a shopper uses it and `check_ajax_referer` then fails with no useful signal. The
 * page therefore prints no authoritative nonce. Express components call
 * `monei_express_bootstrap` first and use the nonce it returns. `wc-ajax` requests
 * are never cached — `WC_AJAX::define_ajax()` sets `DONOTCACHEPAGE` and sends
 * nocache headers — so that response is always fresh. The endpoint issues a nonce
 * bound to the caller's own session and reads nothing, which is the same shape
 * WooCommerce core uses for `get_refreshed_fragments`.
 *
 * The same call forces the WooCommerce session open, because `sessionId` comes from
 * `WC()->session->get_customer_id()` and a first-time guest on a product page has no
 * session cookie yet.
 */
class ExpressCheckoutAjaxHandler {

	/**
	 * Nonce action shared by every express endpoint.
	 */
	const NONCE_ACTION = 'monei_express_checkout';

	/**
	 * Request field carrying the nonce.
	 */
	const NONCE_FIELD = 'security';

	/**
	 * Currencies whose minor unit is the currency itself.
	 *
	 * @var string[]
	 */
	const ZERO_DECIMAL_CURRENCIES = array(
		'BIF',
		'CLP',
		'DJF',
		'GNF',
		'JPY',
		'KMF',
		'KRW',
		'MGA',
		'PYG',
		'RWF',
		'UGX',
		'VND',
		'VUV',
		'XAF',
		'XOF',
		'XPF',
	);

	/**
	 * Gateways that expose express checkout.
	 *
	 * @var string[]
	 */
	const EXPRESS_GATEWAY_CLASSES = array(
		'Monei\Gateways\PaymentMethods\WCGatewayMoneiAppleGoogle',
		'Monei\Gateways\PaymentMethods\WCGatewayMoneiPaypal',
	);

	/**
	 * Resolved express gateways.
	 *
	 * @var WCMoneiPaymentGateway[]|null
	 */
	private $express_gateways = null;

	/**
	 * Cart snapshot service.
	 *
	 * @var ExpressCartBackup
	 */
	private $cart_backup;

	/**
	 * @param ExpressCartBackup $cart_backup Cart snapshot service.
	 */
	public function __construct( ExpressCartBackup $cart_backup ) {
		$this->cart_backup = $cart_backup;
	}

	/**
	 * Register the endpoints.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wc_ajax_monei_express_bootstrap', array( $this, 'ajax_bootstrap' ) );
		add_action( 'wc_ajax_monei_express_get_cart_details', array( $this, 'ajax_get_cart_details' ) );
		add_action( 'wc_ajax_monei_express_get_shipping_options', array( $this, 'ajax_get_shipping_options' ) );
		add_action( 'wc_ajax_monei_express_normalize_address', array( $this, 'ajax_normalize_address' ) );
		add_action( 'wc_ajax_monei_express_update_shipping_method', array( $this, 'ajax_update_shipping_method' ) );
		add_action( 'wc_ajax_monei_express_get_selected_product_data', array( $this, 'ajax_get_selected_product_data' ) );
		add_action( 'wc_ajax_monei_express_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
		add_action( 'wc_ajax_monei_express_clear_cart', array( $this, 'ajax_clear_cart' ) );
		add_action( 'wp', array( $this, 'maybe_start_customer_session' ) );
	}

	/**
	 * Hands the express components a fresh nonce and the WooCommerce session id.
	 *
	 * @return void
	 */
	public function ajax_bootstrap() {
		$this->deny_unless_express_available();
		$this->start_customer_session();

		wp_send_json(
			array(
				'result'    => 'success',
				'nonce'     => wp_create_nonce( self::NONCE_ACTION ),
				'sessionId' => $this->get_session_id(),
			)
		);
	}

	/**
	 * Cart amount, currency and display items in minor units.
	 *
	 * @return void
	 */
	public function ajax_get_cart_details() {
		$this->verify_request();

		WC()->cart->calculate_totals();

		wp_send_json( array_merge( array( 'result' => 'success' ), $this->build_cart_payload() ) );
	}

	/**
	 * Shipping options available for a partial wallet address.
	 *
	 * The wallet sends country, city, state and postcode only — never a street — so
	 * the response must survive an address that cannot be fully validated.
	 *
	 * @return void
	 */
	public function ajax_get_shipping_options() {
		$this->verify_request();

		$address = $this->get_posted_address( 'address' );

		if ( ! WC()->cart->needs_shipping() ) {
			WC()->cart->calculate_totals();

			wp_send_json(
				array_merge(
					array(
						'result'          => 'success',
						'shippingOptions' => array(),
					),
					$this->build_cart_payload()
				)
			);
		}

		$this->apply_shipping_address( $address );

		$options = $this->get_available_shipping_options();

		if ( empty( $options ) ) {
			wp_send_json(
				array_merge(
					array(
						'result'          => 'invalid_shipping_address',
						'message'         => __( 'No shipping method is available for this address.', 'monei' ),
						'shippingOptions' => array(),
					),
					$this->build_cart_payload()
				)
			);
		}

		// The wallet auto-selects the first option, so the returned amount must be the
		// total with that option already applied.
		$this->set_chosen_shipping_method( $options[0]['id'] );
		WC()->cart->calculate_totals();

		wp_send_json(
			array_merge(
				array(
					'result'          => 'success',
					'shippingOptions' => $options,
				),
				$this->build_cart_payload()
			)
		);
	}

	/**
	 * Wallet address mapped to WooCommerce fields.
	 *
	 * @return void
	 */
	public function ajax_normalize_address() {
		$this->verify_request();

		wp_send_json(
			array(
				'result'   => 'success',
				'billing'  => $this->get_posted_address( 'billing' ),
				'shipping' => $this->get_posted_address( 'shipping' ),
			)
		);
	}

	/**
	 * Applies the shipping rate the shopper picked in the wallet sheet.
	 *
	 * @return void
	 */
	public function ajax_update_shipping_method() {
		$this->verify_request();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_request() runs check_ajax_referer first.
		$rate_id = isset( $_POST['shipping_method'] ) ? (string) wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : '';

		WC()->cart->calculate_totals();

		if ( WC()->cart->needs_shipping() ) {
			// The rate id comes from the client, so it is only honoured when it is one
			// of the rates this cart and address actually produced.
			$available = wp_list_pluck( $this->get_available_shipping_options(), 'id' );

			if ( ! in_array( $rate_id, $available, true ) ) {
				wp_send_json(
					array_merge(
						array(
							'result'  => 'invalid_shipping_option',
							'message' => __( 'That shipping method is not available.', 'monei' ),
						),
						$this->build_cart_payload()
					)
				);
			}

			$this->set_chosen_shipping_method( $rate_id );
			WC()->cart->calculate_totals();
		}

		wp_send_json( array_merge( array( 'result' => 'success' ), $this->build_cart_payload() ) );
	}

	/**
	 * Price and shipping requirement of the product the shopper is looking at.
	 *
	 * The wallet sheet has to open with the right total before the cart has been
	 * touched, so the amount comes from the product rather than from the cart. Once
	 * `add_to_cart` has run, `get_cart_details` is the authority.
	 *
	 * @return void
	 */
	public function ajax_get_selected_product_data() {
		$this->verify_request();

		$product  = $this->get_posted_product();
		$quantity = $this->get_posted_quantity();
		$currency = get_woocommerce_currency();

		wp_send_json(
			array(
				'result'           => 'success',
				'productId'        => $product->get_id(),
				'currency'         => $currency,
				'amount'           => self::to_minor_units(
					(float) wc_get_price_to_display( $product, array( 'qty' => $quantity ) ),
					$currency
				),
				// Mirrors WC_Cart::needs_shipping() rather than asking the product alone:
				// a store with shipping switched off, or with no method configured
				// anywhere, would otherwise make the wallet collect a shipping address
				// that get_cart_details() then says is not needed.
				'shippingRequired' => wc_shipping_enabled()
					&& wc_get_shipping_method_count( true ) > 0
					&& $product->needs_shipping(),
				'displayItems'     => array(
					array(
						'label'  => $product->get_name(),
						'amount' => self::to_minor_units(
							(float) wc_get_price_to_display( $product, array( 'qty' => $quantity ) ),
							$currency
						),
					),
				),
			)
		);
	}

	/**
	 * Puts the product being viewed into the cart, on its own.
	 *
	 * ⚠️ The snapshot is taken **first**. Everything after this point can be undone by
	 * `clear_cart`; anything emptied before it cannot.
	 *
	 * @return void
	 */
	public function ajax_add_to_cart() {
		$this->verify_request();

		$product  = $this->get_posted_product();
		$quantity = $this->get_posted_quantity();

		$this->cart_backup->save();

		WC()->cart->empty_cart();

		$is_variation = $product instanceof WC_Product_Variation;
		$variation_id = $is_variation ? $product->get_id() : 0;
		$parent_id    = $is_variation ? $product->get_parent_id() : $product->get_id();

		$key = WC()->cart->add_to_cart(
			$parent_id,
			$quantity,
			$variation_id,
			$is_variation ? $product->get_variation_attributes() : array()
		);

		if ( ! $key ) {
			// The cart is empty at this point, so the shopper's own cart has to go back
			// before this request returns, whatever the reason for the failure.
			$this->cart_backup->restore();

			wp_send_json_error(
				array(
					'code'    => 'add_to_cart_failed',
					'message' => __( 'This product could not be added to the cart.', 'monei' ),
				),
				400
			);
		}

		$this->cart_backup->remember_express_items( array( $key ) );

		WC()->cart->calculate_totals();

		wp_send_json( array_merge( array( 'result' => 'success' ), $this->build_cart_payload() ) );
	}

	/**
	 * Puts the shopper's own cart back, on every way out of a product page express
	 * flow: cancelled wallet sheet, failed payment, or navigating away.
	 *
	 * @return void
	 */
	public function ajax_clear_cart() {
		$this->verify_request();

		wp_send_json(
			array(
				'result'   => 'success',
				'restored' => $this->cart_backup->restore(),
			)
		);
	}

	/**
	 * The product the request refers to, resolved down to a concrete variation.
	 *
	 * Sends an error response and stops rather than returning, because every caller
	 * would otherwise have to repeat the same check.
	 *
	 * @return WC_Product
	 */
	private function get_posted_product() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_request() runs check_ajax_referer first.
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;

		$product = wc_get_product( $variation_id > 0 ? $variation_id : $product_id );

		if ( ! $product instanceof WC_Product ) {
			$this->deny( 'invalid_product', __( 'This product is not available.', 'monei' ) );
		}

		if ( $product->is_type( 'variable' ) ) {
			$product = $this->resolve_variation( $product );
		}

		// Grouped and external products have no price and no single line to buy, and a
		// wallet sheet cannot ask the questions their pages ask.
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			$this->deny( 'unsupported_product', __( 'This product cannot be bought with express checkout.', 'monei' ) );
		}

		return $product;
	}

	/**
	 * Turns the posted attributes into the one variation they select.
	 *
	 * @param WC_Product $product Variable product.
	 *
	 * @return WC_Product
	 */
	private function resolve_variation( WC_Product $product ) {
		$attributes = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_request() runs check_ajax_referer first.
		if ( isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$attributes = (array) wc_clean( wp_unslash( $_POST['attributes'] ) );
		}

		$data_store = WC_Data_Store::load( 'product' );
		// WC_Data_Store proxies to the concrete store through __call, so the method is
		// invisible to static analysis.
		/** @phpstan-ignore-next-line */
		$variation = $data_store->find_matching_product_variation( $product, $attributes );
		$resolved  = $variation ? wc_get_product( $variation ) : null;

		if ( ! $resolved instanceof WC_Product ) {
			$this->deny( 'variation_not_found', __( 'Please choose product options before paying.', 'monei' ) );
		}

		return $resolved;
	}

	/**
	 * @return int
	 */
	private function get_posted_quantity() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_request() runs check_ajax_referer first.
		$quantity = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 1;

		return max( 1, $quantity );
	}

	/**
	 * @param string $code    Machine readable reason.
	 * @param string $message Shopper facing message.
	 *
	 * @return void
	 */
	private function deny( $code, $message ) {
		wp_send_json_error(
			array(
				'code'    => $code,
				'message' => $message,
			),
			400
		);
	}

	/**
	 * Opens the session on the pages express buttons render on, so the very first
	 * request a guest makes already carries a usable session id.
	 *
	 * @return void
	 */
	public function maybe_start_customer_session() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		$location = $this->get_current_location();

		if ( null === $location || ! $this->is_express_enabled_at( $location ) ) {
			return;
		}

		$this->start_customer_session();
	}

	/**
	 * A positive integer in the smallest unit of the currency, which is what every
	 * MONEI amount on the wire is.
	 *
	 * Matches `monei_price_format()` for every two-decimal currency, so express never
	 * puts a second amount format on the wire. It differs only for the zero-decimal
	 * currencies the global helper multiplies by 100 regardless. Task 22 must verify
	 * the order total with this same method.
	 *
	 * @param float|int|string $amount   Amount in major units.
	 * @param string           $currency ISO 4217 code.
	 *
	 * @return int
	 */
	public static function to_minor_units( $amount, $currency = '' ) {
		$factor = in_array( strtoupper( (string) $currency ), self::ZERO_DECIMAL_CURRENCIES, true ) ? 1 : 100;

		// round() rather than an int cast: (int) ( 10.10 * 100 ) is 1009, because the
		// product is 1009.9999999999999 in binary floating point.
		return (int) round( (float) $amount * $factor );
	}

	/**
	 * Cart amount, currency and display items, all in minor units.
	 *
	 * The display items sum to the amount: subtotal and discount are net of tax, and
	 * tax is a line of its own — the same decomposition WooCommerce totals use.
	 *
	 * @return array<string, mixed>
	 */
	private function build_cart_payload() {
		$cart     = WC()->cart;
		$currency = get_woocommerce_currency();

		$items = array(
			array(
				'label'  => __( 'Subtotal', 'monei' ),
				'amount' => self::to_minor_units( $cart->get_subtotal(), $currency ),
			),
		);

		$discount = (float) $cart->get_discount_total();

		if ( $discount > 0 ) {
			$items[] = array(
				'label'  => __( 'Discount', 'monei' ),
				'amount' => -self::to_minor_units( $discount, $currency ),
			);
		}

		foreach ( $cart->get_fees() as $fee ) {
			$items[] = array(
				'label'  => $fee->name,
				'amount' => self::to_minor_units( $fee->total, $currency ),
			);
		}

		if ( $cart->needs_shipping() ) {
			$items[] = array(
				'label'  => __( 'Shipping', 'monei' ),
				'amount' => self::to_minor_units( $cart->get_shipping_total(), $currency ),
			);
		}

		$tax = (float) $cart->get_taxes_total();

		if ( wc_tax_enabled() && $tax > 0 ) {
			$items[] = array(
				'label'  => __( 'Tax', 'monei' ),
				'amount' => self::to_minor_units( $tax, $currency ),
			);
		}

		return array(
			'currency'         => $currency,
			'amount'           => self::to_minor_units( $cart->get_total( false ), $currency ),
			'shippingRequired' => $cart->needs_shipping(),
			'displayItems'     => $items,
		);
	}

	/**
	 * Shipping rates for the current packages, as `{ id, label, amount }`.
	 *
	 * Duplicate rate ids are dropped: a wallet sheet given two options with the same
	 * id never finishes loading.
	 *
	 * ⚠️ KNOWN LIMITATION — carts that WooCommerce splits into more than one shipping
	 * package. A wallet sheet takes exactly one flat list of shipping methods, so it
	 * cannot express a choice per package. Every package's rates are flattened into
	 * that one list, each `amount` is the cost of the single package the rate came
	 * from, and `set_chosen_shipping_method()` writes only `chosen_shipping_methods[0]`
	 * — so the shopper's pick governs the first package and WooCommerce falls back to
	 * its own default for the rest.
	 *
	 * The charge itself stays correct: the total handed to the wallet is always
	 * `WC()->cart->get_total()` recomputed after `calculate_totals()`, never a figure
	 * assembled here. What is wrong is the per-option amount shown in the sheet and
	 * the shopper's inability to choose for packages after the first.
	 *
	 * Not fixed here on purpose. There is no patch — only a redesign of the option
	 * model that the wallet APIs cannot represent anyway, on a code path with no
	 * multi-package coverage to redesign it against. Splitting needs several shipping
	 * zones or per-class packaging, so it is a minority configuration.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_available_shipping_options() {
		$currency = get_woocommerce_currency();
		$options  = array();
		$seen     = array();

		foreach ( WC()->shipping()->get_packages() as $package ) {
			if ( empty( $package['rates'] ) ) {
				continue;
			}

			foreach ( $package['rates'] as $rate ) {
				if ( in_array( $rate->get_id(), $seen, true ) ) {
					continue;
				}

				$seen[]    = $rate->get_id();
				$options[] = array(
					'id'     => $rate->get_id(),
					'label'  => wp_strip_all_tags( $rate->get_label() ),
					'amount' => self::to_minor_units( (float) $rate->get_cost() + (float) $rate->get_shipping_tax(), $currency ),
				);
			}
		}

		// Keep the method the shopper already chose first, so the wallet's automatic
		// selection of the first option does not silently change it.
		$chosen = $this->get_chosen_shipping_method();

		if ( '' !== $chosen ) {
			usort(
				$options,
				function ( $a, $b ) use ( $chosen ) {
					if ( $a['id'] === $chosen ) {
						return -1;
					}

					if ( $b['id'] === $chosen ) {
						return 1;
					}

					return 0;
				}
			);
		}

		return $options;
	}

	/**
	 * Points the customer at the wallet-supplied address and recalculates shipping.
	 *
	 * @param array<string, string> $address Partial address.
	 *
	 * @return void
	 */
	private function apply_shipping_address( array $address ) {
		$country  = $address['country'];
		$state    = $address['state'];
		$city     = $address['city'];
		$postcode = $address['postcode'];

		WC()->shipping()->reset_shipping();

		if ( '' !== $postcode && WC_Validation::is_postcode( $postcode, $country ) ) {
			$postcode = wc_format_postcode( $postcode, $country );
		}

		if ( '' !== $country ) {
			WC()->customer->set_location( $country, $state, $postcode, $city );
			WC()->customer->set_shipping_location( $country, $state, $postcode, $city );
		} else {
			WC()->customer->set_billing_address_to_base();
			WC()->customer->set_shipping_address_to_base();
		}

		WC()->customer->set_calculated_shipping( true );
		WC()->customer->save();

		// calculate_totals() builds the packages through WC_Cart::get_shipping_packages()
		// and runs the shipping calculation, so the packages are never hand-rolled here.
		WC()->cart->calculate_totals();
	}

	/**
	 * @return string
	 */
	private function get_chosen_shipping_method() {
		$session = function_exists( 'WC' ) ? WC()->session : null;

		if ( ! $session instanceof WC_Session ) {
			return '';
		}

		$chosen = (array) $session->get( 'chosen_shipping_methods', array() );

		return isset( $chosen[0] ) ? (string) $chosen[0] : '';
	}

	/**
	 * @param string $rate_id Shipping rate id.
	 *
	 * @return void
	 */
	private function set_chosen_shipping_method( $rate_id ) {
		$session = function_exists( 'WC' ) ? WC()->session : null;

		if ( ! $session instanceof WC_Session ) {
			return;
		}

		$chosen    = (array) $session->get( 'chosen_shipping_methods', array() );
		$chosen[0] = $rate_id;
		$session->set( 'chosen_shipping_methods', $chosen );
	}

	/**
	 * Reads a posted address object and returns it in WooCommerce format.
	 *
	 * @param string $key POST key holding the address object.
	 *
	 * @return array<string, string>
	 */
	private function get_posted_address( $key ) {
		$raw = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_request() runs check_ajax_referer first.
		if ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw = (array) wc_clean( wp_unslash( $_POST[ $key ] ) );
		}

		return $this->normalize_address( $raw );
	}

	/**
	 * @param array<string, mixed> $address Wallet address.
	 *
	 * @return array<string, string>
	 */
	private function normalize_address( array $address ) {
		$normalized          = self::map_wallet_address( $address );
		$normalized['state'] = self::normalize_state_code(
			$normalized['state'],
			$this->get_country_states( $normalized['country'] )
		);

		return $normalized;
	}

	/**
	 * @param string $country Two-letter country code.
	 *
	 * @return array<string, string>
	 */
	private function get_country_states( $country ) {
		if ( '' === $country ) {
			return array();
		}

		$states = WC()->countries->get_states( $country );

		return is_array( $states ) ? $states : array();
	}

	/**
	 * Maps a wallet address onto WooCommerce address fields.
	 *
	 * Wallets disagree on field names — monei.js hands back `{ line1, line2, city,
	 * state, zip, country }` while Apple Pay, Google Pay and PayPal each use their own
	 * spelling — and mid-flow the object holds country, city, state and postcode only.
	 * Every field is therefore optional and every unknown key is ignored.
	 *
	 * @param array<string, mixed> $address Wallet address, flat or with a nested `address`.
	 *
	 * @return array<string, string>
	 */
	public static function map_wallet_address( array $address ) {
		$nested = isset( $address['address'] ) && is_array( $address['address'] ) ? $address['address'] : array();
		$fields = array_merge( array_filter( $address, 'is_scalar' ), array_filter( $nested, 'is_scalar' ) );

		$mapped = array(
			'first_name' => self::first_value( $fields, array( 'first_name', 'firstName', 'givenName', 'given_name' ) ),
			'last_name'  => self::first_value( $fields, array( 'last_name', 'lastName', 'surname', 'familyName', 'family_name' ) ),
			'company'    => self::first_value( $fields, array( 'company', 'organization' ) ),
			'address_1'  => self::first_value( $fields, array( 'address_1', 'line1', 'addressLine1', 'address_line_1' ) ),
			'address_2'  => self::first_value( $fields, array( 'address_2', 'line2', 'addressLine2', 'address_line_2' ) ),
			'city'       => self::first_value( $fields, array( 'city', 'locality', 'admin_area_2' ) ),
			'state'      => self::first_value( $fields, array( 'state', 'region', 'province', 'administrativeArea', 'admin_area_1' ) ),
			'postcode'   => self::first_value( $fields, array( 'postcode', 'zip', 'postalCode', 'postal_code' ) ),
			'country'    => strtoupper( self::first_value( $fields, array( 'country', 'countryCode', 'country_code' ) ) ),
			'email'      => self::first_value( $fields, array( 'email', 'emailAddress', 'email_address' ) ),
			'phone'      => self::first_value( $fields, array( 'phone', 'phoneNumber', 'phone_number', 'telephone' ) ),
		);

		if ( '' === $mapped['first_name'] && '' === $mapped['last_name'] ) {
			$name = self::first_value( $fields, array( 'name', 'fullName', 'full_name', 'recipient' ) );

			if ( '' !== $name ) {
				$parts = preg_split( '/\s+/', $name );
				$parts = is_array( $parts ) ? $parts : array( $name );

				$mapped['last_name']  = count( $parts ) > 1 ? (string) array_pop( $parts ) : '';
				$mapped['first_name'] = implode( ' ', $parts );
			}
		}

		return $mapped;
	}

	/**
	 * Resolves a state to the code WooCommerce stores.
	 *
	 * This is where express checkout usually breaks: wallets send a display name
	 * ("Madrid", "California"), sometimes decorated ("Co. Clare") and sometimes without
	 * accents, while WooCommerce shipping zones and address validation match on the
	 * code. Countries WooCommerce has no state list for keep the value untouched.
	 *
	 * @param string                $state     Value from the wallet.
	 * @param array<string, string> $wc_states WooCommerce states for the country, code => name.
	 *
	 * @return string
	 */
	public static function normalize_state_code( $state, array $wc_states ) {
		$state = trim( (string) $state );

		if ( '' === $state || empty( $wc_states ) ) {
			return $state;
		}

		if ( isset( $wc_states[ $state ] ) ) {
			return $state;
		}

		foreach ( array_keys( $wc_states ) as $code ) {
			if ( 0 === strcasecmp( (string) $code, $state ) ) {
				return (string) $code;
			}
		}

		$needle = self::fold( $state );

		// Exact name match runs as its own pass: a containment pass alone would resolve
		// "West Virginia" to Virginia, whichever key WooCommerce happens to list first.
		foreach ( $wc_states as $code => $name ) {
			if ( self::fold( $name ) === $needle ) {
				return (string) $code;
			}
		}

		foreach ( $wc_states as $code => $name ) {
			$folded = self::fold( $name );

			if ( '' !== $folded && false !== strpos( $needle, $folded ) ) {
				return (string) $code;
			}
		}

		return $state;
	}

	/**
	 * Lowercases, strips accents and drops punctuation, so "Málaga", "Malaga" and
	 * "MALAGA." all compare equal.
	 *
	 * @param string $value Value to fold.
	 *
	 * @return string
	 */
	private static function fold( $value ) {
		$value = mb_strtolower( (string) $value, 'UTF-8' );

		$value = strtr(
			$value,
			array(
				'á' => 'a',
				'à' => 'a',
				'â' => 'a',
				'ä' => 'a',
				'ã' => 'a',
				'å' => 'a',
				'é' => 'e',
				'è' => 'e',
				'ê' => 'e',
				'ë' => 'e',
				'í' => 'i',
				'ì' => 'i',
				'î' => 'i',
				'ï' => 'i',
				'ó' => 'o',
				'ò' => 'o',
				'ô' => 'o',
				'ö' => 'o',
				'õ' => 'o',
				'ú' => 'u',
				'ù' => 'u',
				'û' => 'u',
				'ü' => 'u',
				'ñ' => 'n',
				'ç' => 'c',
			)
		);

		$value = (string) preg_replace( '/[^a-z0-9]+/', ' ', $value );

		return trim( $value );
	}

	/**
	 * First non-empty scalar among the given keys.
	 *
	 * @param array<string, mixed> $source Source data.
	 * @param string[]             $keys   Keys to try, in order.
	 *
	 * @return string
	 */
	private static function first_value( array $source, array $keys ) {
		foreach ( $keys as $key ) {
			if ( ! isset( $source[ $key ] ) || ! is_scalar( $source[ $key ] ) ) {
				continue;
			}

			$value = trim( (string) $source[ $key ] );

			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Rejects the request unless express checkout is on and the nonce is valid.
	 *
	 * @return void
	 */
	private function verify_request() {
		$this->deny_unless_express_available();

		if ( ! check_ajax_referer( self::NONCE_ACTION, self::NONCE_FIELD, false ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_nonce',
					'message' => __( 'Your express checkout session expired. Please reload the page.', 'monei' ),
				),
				403
			);
		}

		$this->start_customer_session();

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}
	}

	/**
	 * @return void
	 */
	private function deny_unless_express_available() {
		if ( $this->is_express_available() ) {
			return;
		}

		wp_send_json_error(
			array(
				'code'    => 'express_disabled',
				'message' => __( 'Express checkout is not available.', 'monei' ),
			),
			403
		);
	}

	/**
	 * True when any express gateway is enabled at any location.
	 *
	 * @return bool
	 */
	private function is_express_available() {
		foreach ( array_keys( WCMoneiPaymentGateway::get_express_location_options() ) as $location ) {
			if ( $this->is_express_enabled_at( (string) $location ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * True when any express gateway is enabled at the given location.
	 *
	 * @param string $location One of the keys of get_express_location_options().
	 *
	 * @return bool
	 */
	private function is_express_enabled_at( $location ) {
		foreach ( $this->get_express_gateways() as $gateway ) {
			if ( $gateway->is_express_enabled_at( $location ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gateways are resolved on demand rather than injected: the container builds them
	 * eagerly on construction, and this service is instantiated during `init`, before
	 * WooCommerce asks for its payment gateways.
	 *
	 * @return WCMoneiPaymentGateway[]
	 */
	private function get_express_gateways() {
		if ( null !== $this->express_gateways ) {
			return $this->express_gateways;
		}

		$container              = ContainerProvider::getContainer();
		$this->express_gateways = array();

		foreach ( self::EXPRESS_GATEWAY_CLASSES as $class_name ) {
			$gateway = $container->get( $class_name );

			if ( $gateway instanceof WCMoneiPaymentGateway ) {
				$this->express_gateways[] = $gateway;
			}
		}

		return $this->express_gateways;
	}

	/**
	 * @return string|null
	 */
	private function get_current_location() {
		if ( is_product() ) {
			return 'product';
		}

		if ( is_cart() ) {
			return 'cart';
		}

		if ( is_checkout() ) {
			return 'checkout';
		}

		return null;
	}

	/**
	 * @return void
	 */
	private function start_customer_session() {
		$session = function_exists( 'WC' ) ? WC()->session : null;

		// has_session()/set_customer_session_cookie() live on the handler, not on the
		// abstract WC_Session a custom implementation could subclass.
		if ( ! $session instanceof WC_Session_Handler ) {
			return;
		}

		if ( ! $session->has_session() ) {
			$session->set_customer_session_cookie( true );
		}
	}

	/**
	 * @return string
	 */
	private function get_session_id() {
		$session = function_exists( 'WC' ) ? WC()->session : null;

		if ( ! $session instanceof WC_Session ) {
			return '';
		}

		return (string) $session->get_customer_id();
	}
}
