<?php
/**
 * Stubs for WooCommerce Subscriptions and YITH Subscriptions
 * Used by PHPStan for static analysis
 */

// WooCommerce Subscriptions functions
if (!function_exists('wcs_is_subscription')) {
	/**
	 * @param int|WC_Order $order
	 * @return bool
	 */
	function wcs_is_subscription($order) {
		return false;
	}
}

if (!function_exists('wcs_order_contains_renewal')) {
	/**
	 * @param int|WC_Order $order
	 * @return bool
	 */
	function wcs_order_contains_renewal($order) {
		return false;
	}
}

if (!function_exists('wcs_get_subscriptions_for_order')) {
	/**
	 * @param int|WC_Order $order
	 * @param array $args
	 * @return WCS_Subscription[]
	 */
	function wcs_get_subscriptions_for_order($order, $args = array()) {
		return array();
	}
}

if (!function_exists('wcs_get_subscriptions_for_renewal_order')) {
	/**
	 * @param int|WC_Order $order
	 * @return WCS_Subscription[]
	 */
	function wcs_get_subscriptions_for_renewal_order($order) {
		return array();
	}
}

// YITH Subscriptions functions
if (!function_exists('ywsbs_get_subscription')) {
	/**
	 * @param int $subscription_id
	 * @return YWSBS_Subscription|false
	 */
	function ywsbs_get_subscription($subscription_id) {
		return false;
	}
}

// WooCommerce Subscriptions Classes
if (!class_exists('WCS_Subscription')) {
	/**
	 * Mock WCS_Subscription class for PHPStan
	 */
	class WCS_Subscription extends WC_Order {
		/**
		 * @return bool
		 */
		public static function is_subscription() {
			return true;
		}

		/**
		 * @return int
		 */
		public static function get_interval() {
			return 1;
		}

		/**
		 * @return string
		 */
		public static function get_period() {
			return 'month';
		}

		/**
		 * @return WC_Order|false
		 */
		public function get_parent() {
			return false;
		}
	}
}

// YITH Subscriptions Classes
if (!class_exists('YWSBS_Subscription')) {
	/**
	 * Mock YWSBS_Subscription class for PHPStan
	 */
	class YWSBS_Subscription {
		/**
		 * @return bool
		 */
		public static function is_subscription() {
			return true;
		}

		/**
		 * @return int
		 */
		public static function get_interval() {
			return 1;
		}

		/**
		 * @return string
		 */
		public static function get_period() {
			return 'month';
		}

		/**
		 * @return WC_Order|false
		 */
		public function get_parent() {
			return false;
		}
	}
}

// WooCommerce Subscriptions Product Class
if (!class_exists('WC_Subscriptions_Product')) {
	/**
	 * Mock WC_Subscriptions_Product class for PHPStan
	 */
	class WC_Subscriptions_Product {
		/**
		 * @param WC_Product $product
		 * @return bool
		 */
		public static function is_subscription($product) {
			return false;
		}

		/**
		 * @param WC_Product $product
		 * @return int
		 */
		public static function get_interval($product) {
			return 1;
		}

		/**
		 * @param WC_Product $product
		 * @return string
		 */
		public static function get_period($product) {
			return 'month';
		}
	}
}
