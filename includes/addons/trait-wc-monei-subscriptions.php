<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Trait for Subscriptions compatibility.
 *
 * @since 5.0
 */
trait WC_Monei_Subscriptions_Trait {

	use WC_Monei_Addons_Helper_Trait;

	/**
	 * Add support to subscription.
	 * Add all related hooks.
	 */
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

	/**
	 * It adds subscription configurtion to the payload.
	 *
	 * @param $order_id
	 * @param $payment_method
	 *
	 * @return array
	 */
	public function create_subscription_payload( $order_id, $payment_method ) {
		$order               = new WC_Order( $order_id );
		$payload             = parent::create_payload( $order, $payment_method );
		$payload['sequence'] = [
			'type' => 'recurring',
			'recurring' => [
				'frequency' => $this->get_cart_subscription_interval_in_days() // The minimum number of days between the different recurring payments.
			]
		];

		$payload = apply_filters( 'wc_gateway_monei_create_subscription_payload', $payload );
		return $payload;
	}
}

