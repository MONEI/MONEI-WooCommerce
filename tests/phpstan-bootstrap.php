<?php
/**
 * PHPStan Bootstrap File
 * Defines constants and global functions for static analysis
 */

// Define plugin constants
define( 'MONEI_VERSION', '6.4.0' );
define( 'MONEI_MAIN_FILE', __DIR__ . '/../monei.php' );
define( 'MONEI_SIGNUP', 'https://dashboard.monei.com/signup' );
define( 'MONEI_WEB', 'https://monei.com' );
define( 'MONEI_SUPPORT', 'https://support.monei.com' );
define( 'MONEI_REVIEW', 'https://wordpress.org/support/plugin/monei/reviews/' );
define( 'MONEI_GATEWAY_ID', 'monei' );

// Define WordPress constants if not already defined
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Define plugin helper functions that PHPStan should know about
if ( ! function_exists( 'WC_Monei' ) ) {
	/**
	 * @return object
	 */
	function WC_Monei() {
		return new class {
			public function plugin_path() {
				return __DIR__ . '/..';
			}
			public function image_url( $file ) {
				return 'https://example.com/' . $file;
			}
			public function get_ipn_url() {
				return 'https://example.com/ipn';
			}
		};
	}
}

if ( ! function_exists( 'monei_price_format' ) ) {
	/**
	 * @param float $price
	 * @return int
	 */
	function monei_price_format( $price ) {
		return (int) ( $price * 100 );
	}
}

if ( ! function_exists( 'locale_iso_639_1_code' ) ) {
	/**
	 * @return string
	 */
	function locale_iso_639_1_code() {
		return 'en';
	}
}

// Define legacy classes from includes/ directory
if ( ! class_exists( 'WC_Monei_IPN' ) ) {
	/**
	 * Mock WC_Monei_IPN class for PHPStan
	 */
	class WC_Monei_IPN {
		public function __construct( bool $logging = false ) {}
	}
}

if ( ! class_exists( 'WC_Monei_Logger' ) ) {
	/**
	 * Mock WC_Monei_Logger class for PHPStan
	 */
	class WC_Monei_Logger {
		public static function log( $message, $level = 'info' ) {}
	}
}
