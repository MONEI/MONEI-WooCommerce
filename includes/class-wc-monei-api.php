<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * API Helper Class
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_API {

	const option_api_key = 'woocommerce_monei_apikey';

	/**
	 * @var string
	 */
	protected static $api_key;

	/**
	 * @var \Monei\MoneiClient
	 */
	protected static $client;

	/**
	 * Get API Key.
	 * @return false|string
	 */
	protected static function get_api_key() {
		if ( isset( self::$api_key ) ) {
			return self::$api_key;
		}
		return get_option( self::option_api_key, true );
	}

	/**
	 * @return \Monei\MoneiClient
	 */
	protected static function get_client() {
		if ( isset( self::$client ) ) {
			return self::$client;
		}

		include_once WC_Monei()->plugin_path() . '/vendor/autoload.php';
		self::$client = new Monei\MoneiClient( self::get_api_key() );
		return self::$client;
	}
}

