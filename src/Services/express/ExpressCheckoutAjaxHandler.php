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

		wp_send_json( array( 'result' => 'success' ) );
	}

	/**
	 * Shipping options available for a partial wallet address.
	 *
	 * @return void
	 */
	public function ajax_get_shipping_options() {
		$this->verify_request();

		wp_send_json( array( 'result' => 'success' ) );
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
