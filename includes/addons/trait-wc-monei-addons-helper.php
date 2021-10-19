<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Trait for Addons helper functions.
 *
 * @since 5.0
 */
trait WC_Monei_Addons_Helper_Trait {

	/**
	 * Checks if subscription plugin is enabled.
	 *
	 * @since 5.0
	 *
	 * @return bool
	 */
	public function is_subscriptions_addon_enabled() {
		return class_exists( 'WC_Subscriptions' );
	}

	/**
	 * If $order_id is a subscription.
	 *
	 * @param  int $order_id
	 * @return boolean
	 */
	public function is_order_subscription( $order_id ) {
		if ( ! $this->is_subscriptions_addon_enabled() ) {
			return false;
		}
		return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );
	}

	/**
	 * Return Subscription interval in days.
	 * Support for only one subscription.
	 *
	 * @return int
	 */
	public function get_cart_subscription_interval_in_days() {
		foreach ( WC()->cart->cart_contents as $cart_item ) {
			if ( WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
				$interval = WC_Subscriptions_Product::get_interval( $cart_item['data'] );
				$period   = WC_Subscriptions_Product::get_period( $cart_item['data'] );
				break;
			}
		}

		switch ( $period ) {
			case 'year':
				$interval_in_days = $interval * 365;
				break;
			case 'month':
				$interval_in_days = $interval * 28; // Monei Needs minimun days, to be safe, 28.
				break;
			case 'week':
				$interval_in_days = $interval * 7;
				break;
			default:
				$interval_in_days = $interval;
				break;
		}

		return $interval_in_days;
	}

}

