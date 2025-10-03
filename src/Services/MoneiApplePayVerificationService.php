<?php

namespace Monei\Services;

use Monei\Services\payment\MoneiPaymentServices;
use Monei\ApiException;
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
		add_filter( 'woocommerce_update_options_payment_gateways_monei_apple_google', array( $this, 'apple_domain_register' ) );
	}

	/**
	 * Apple API Domain registration.
	 */
	public function apple_domain_register() {
		if ( ! check_admin_referer( 'woocommerce-settings' ) ) {
			return;
		}

		// Check if Apple/Google Pay is being enabled
		if ( ! isset( $_POST['woocommerce_monei_apple_google_enabled'] ) ) {
			return;
		}
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( 'yes' !== wc_clean( wp_unslash( $_POST['woocommerce_monei_apple_google_enabled'] ) ) ) {
			return;
		}

		try {
			$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( $_SERVER['HTTP_HOST'] ) : str_replace( array( 'https://', 'http://' ), '', get_site_url() ); // @codingStandardsIgnoreLine
			$this->moneiPaymentServices->register_apple_domain( $domain );
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
			$file_path = WC_Monei()->plugin_path() . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;

			if ( file_exists( $file_path ) ) {
				header( 'Content-Type: text/plain' );
				header( 'Content-Disposition: inline; filename="' . self::DOMAIN_ASSOCIATION_FILE_NAME . '"' );
				readfile( $file_path );
			} else {
				status_header( 404 );
				echo 'Apple Pay domain verification file not found';
			}
			exit;
		}
	}
}
