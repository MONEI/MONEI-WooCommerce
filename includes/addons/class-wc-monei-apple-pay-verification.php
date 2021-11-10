<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class to verify Apple Pay Registration.
 * Expose apple-developer-merchantid-domain-association at https://example.com/.well-known/apple-developer-merchantid-domain-association.
 *
 * @since 5.5
 */
class WC_Monei_Addons_Apple_Pay_Verification {

	const DOMAIN_ASSOCIATION_FILE_NAME = 'apple-developer-merchantid-domain-association';
	const DOMAIN_ASSOCIATION_DIR       = '.well-known';

	public function __construct() {
		/**
		 * If not active, we don't expose file.
		 */
		if ( 'yes' !== monei_get_settings( 'apple_google_pay' ) ) {
			return;
		}
		add_action( 'parse_request', array( $this, 'expose_on_domain_association_request' ), 1 );
	}

	/**
	 * Expose DOMAIN_ASSOCIATION_FILE_NAME on https://example.com/.well-known/apple-developer-merchantid-domain-association request.
	 * @param $wp
	 */
	public function expose_on_domain_association_request( $wp ) {
		if ( isset( $wp->request ) && ( self::DOMAIN_ASSOCIATION_DIR . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME ) === $wp->request ) {
			$path = WC_Monei()->plugin_path() . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;
			header( 'Content-Type: text/plain;charset=utf-8' );
			echo esc_html( file_get_contents( $path ) );
			exit;
		}
	}
}

new WC_Monei_Addons_Apple_Pay_Verification();

