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

		add_action( 'wc_gateway_monei_create_payment_success', [ $this, 'subscription_after_payment_success' ], 1, 3 );
	}

	/**
	 * If Subscription has a free trial, first payment 0 euros.
	 * We will charge customer 1 cent ( create_subscription_payload ):
	 * 1. Free Trial
	 * 2. Payment made with tokenized card ( paymentToken is set )
	 *
	 * Therefore, after the 1 cent payment, we need to refund it automatically.
	 * This hooks will trigger after successful (1 cent) payment.
	 *
	 * @param $confirm_payload
	 * @param $confirm_payment
	 * @param $order
	 *
	 * @throws \OpenAPI\Client\ApiException
	 */
	public function subscription_after_payment_success( $confirm_payload, $confirm_payment, $order ) {
		/**
		 * If order is not subscription, bail.
		 */
		if ( ! $this->is_order_subscription( $order->get_id() ) ) {
			return;
		}

		/**
		 * If payment wasn't 1 cent, bail.
		 */
		if ( 1 !== $confirm_payload['amount'] ) {
			return;
		}

		/**
		 * If payment is not done with a tokenized card, bail.
		 */
		if ( ! isset( $confirm_payload['paymentToken'] ) ) {
			return;
		}

		/**
		 * Refund that cent.
		 */
		WC_Monei_API::refund_payment( $confirm_payment->getId(), 1 );
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
		/**
		 * If there is a free trial, (first payment for free) and user has selected a tokenized card,
		 * We hit a monei limitation, so we need to charge the customer 1 cent, that will be refunded afterwards.
		 */
		if ( 0 === monei_price_format( $order->get_total() ) && $this->get_payment_token_id_if_selected() ) {
			$payload['amount'] = 1;
		}

		$payload = apply_filters( 'wc_gateway_monei_create_subscription_payload', $payload );
		return $payload;
	}
}

