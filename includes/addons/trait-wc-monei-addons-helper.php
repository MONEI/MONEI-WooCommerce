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

	public function init_subscriptions() {
		$this->supports = array_merge(
			$this->supports,
			[
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change',
				'subscription_payment_method_change_customer',
				'subscription_payment_method_change_admin',
			]
		);
	}
	

}

