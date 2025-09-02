<?php

namespace Monei\Services;

use Monei\ApiException;
use Monei\Services\payment\MoneiPaymentServices;
use WC_Monei_Logger;
use WC_Admin_Settings;

/**
 * Class to verify Apple Pay Registration.
 * Expose apple-developer-merchantid-domain-association at https://example.com/.well-known/apple-developer-merchantid-domain-association.
 *
 * @since 5.5
 */
class MoneiApplePayVerificationService {

	const DOMAIN_ASSOCIATION_FILE_NAME = 'apple-developer-merchantid-domain-association';
	const DOMAIN_ASSOCIATION_DIR       = '.well-known';
	private MoneiPaymentServices $moneiPaymentServices;

	public function __construct( MoneiPaymentServices $moneiPaymentServices ) {
		$this->moneiPaymentServices = $moneiPaymentServices;
		add_action( 'parse_request', array( $this, 'expose_on_domain_association_request' ), 1 );
		add_filter( 'woocommerce_update_options_payment_gateways_monei', array( $this, 'apple_domain_register' ) );
	}

	/**
	 * Apple API Domain registration.
	 */
	public function apple_domain_register() {
		if ( ! check_admin_referer( 'woocommerce-settings' ) ) {
			return;
		}

		if ( ! isset( $_POST['woocommerce_monei_apple_google_pay'] ) ) {
			return;
		}
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wc_clean( wp_unslash( $_POST['woocommerce_monei_apple_google_pay'] ) ) ) {
			return;
		}

		try {
			$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( $_SERVER['HTTP_HOST'] ) : str_replace( array( 'https://', 'http://' ), '', get_site_url() ); // @codingStandardsIgnoreLine
			$this->moneiPaymentServices->register_apple_domain( $domain );
			WC_Monei_Logger::log( 'Apple Pay registered with domain: ' . $domain );
		} catch ( ApiException $e ) {
			WC_Monei_Logger::log( $e, 'error' );
			$response_body = json_decode( $e->getResponseBody() );
			WC_Admin_Settings::add_error( __( 'Apple', 'monei' ) . ' ' . $response_body->message );
		}
	}

	/**
	 * Expose DOMAIN_ASSOCIATION_FILE_NAME on https://example.com/.well-known/apple-developer-merchantid-domain-association request.
	 *
	 * @param $wp
	 */
	public function expose_on_domain_association_request( $wp ) {
		if ( isset( $wp->request ) && ( self::DOMAIN_ASSOCIATION_DIR . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME ) === $wp->request ) {
			$path = WC_Monei()->plugin_url() . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;
			WC_Monei_Logger::log( 'Apple Pay Domain Association requested in: ' . $path );
			$args     = array( 'headers' => array( 'Content-Type' => 'text/plain;charset=utf-8' ) );
			$response = wp_remote_get( $path, $args );
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$body = $response['body'];
				echo esc_html( $response['body'] );
			}
			exit;
		}
	}
}
