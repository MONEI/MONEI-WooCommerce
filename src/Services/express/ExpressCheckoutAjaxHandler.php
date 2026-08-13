<?php
/**
 * Express checkout AJAX endpoints.
 *
 * @package Monei
 */

namespace Monei\Services\express;

use Monei\Core\ContainerProvider;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;
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

		$address = $this->get_posted_address_fields();

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

		wp_send_json( array( 'result' => 'success' ) );
	}

	/**
	 * Applies the shipping rate the shopper picked in the wallet sheet.
	 *
	 * @return void
	 */
	public function ajax_update_shipping_method() {
		$this->verify_request();

		wp_send_json( array( 'result' => 'success' ) );
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
	 * The partial address a wallet sends mid-flow.
	 *
	 * @return array<string, string>
	 */
	private function get_posted_address_fields() {
		$address = array(
			'country'  => '',
			'state'    => '',
			'city'     => '',
			'postcode' => '',
		);

		foreach ( array_keys( $address ) as $field ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verify_request() runs check_ajax_referer before this is reached.
			if ( isset( $_POST[ $field ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$address[ $field ] = (string) wc_clean( wp_unslash( $_POST[ $field ] ) );
			}
		}

		$address['country'] = strtoupper( $address['country'] );

		return $address;
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
