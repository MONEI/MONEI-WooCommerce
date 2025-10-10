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
		return new class() {
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

if ( ! function_exists( 'monei_get_settings' ) ) {
	/**
	 * @param string $key
	 * @param string $gateway_id
	 * @return mixed
	 */
	function monei_get_settings( $key, $gateway_id = '' ) {
		return '';
	}
}

if ( ! function_exists( 'monei_get_option_key_from_order' ) ) {
	/**
	 * @param \WC_Order $order
	 * @return string
	 */
	function monei_get_option_key_from_order( $order ) {
		return 'monei';
	}
}

if ( ! function_exists( 'wc_clean' ) ) {
	/**
	 * @param string|array $var
	 * @return string|array
	 */
	function wc_clean( $var ) {
		return $var;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * @param string|array $value
	 * @return string|array
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'wc_price' ) ) {
	/**
	 * @param float $price
	 * @return string
	 */
	function wc_price( $price ) {
		return '$' . number_format( $price, 2 );
	}
}

if ( ! function_exists( 'wc_get_order' ) ) {
	/**
	 * @param int $order_id
	 * @return \WC_Order|false
	 */
	function wc_get_order( $order_id ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * @param string $transient
	 * @param mixed $value
	 * @param int $expiration
	 * @return bool
	 */
	function set_transient( $transient, $value, $expiration = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * @param string $transient
	 * @return mixed
	 */
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * @param string $transient
	 * @return bool
	 */
	function delete_transient( $transient ) {
		return true;
	}
}

if ( ! function_exists( 'wp_rand' ) ) {
	/**
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	function wp_rand( $min = 0, $max = 0 ) {
		return rand( $min, $max );
	}
}

// Define legacy classes from includes/ directory
if ( ! class_exists( 'WC_Monei_IPN' ) ) {
	/**
	 * Mock WC_Monei_IPN class for PHPStan
	 *
	 * @param bool $logging
	 */
	class WC_Monei_IPN {
		public function __construct( bool $logging = false ) {
			// Stub implementation - parameter kept for signature compatibility
			unset( $logging );
		}
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

if ( ! class_exists( 'WC_Geolocation' ) ) {
	/**
	 * Mock WC_Geolocation class for PHPStan
	 */
	class WC_Geolocation {
		/**
		 * @return string
		 */
		public static function get_ip_address() {
			return '127.0.0.1';
		}
	}
}
