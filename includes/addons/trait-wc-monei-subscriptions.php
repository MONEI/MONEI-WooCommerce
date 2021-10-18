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

		add_action( 'wp', array( $this, 'save_sequence_id' ) );
	}

	/**
	 * When a payment is done on a subscription order, we need to
	 */
	public function save_sequence_id() {
		die('a');
		if ( ! is_order_received_page() ) {
			return;
		}

		if ( ! isset( $_GET['id'] ) ) {
			return;
		}

		$payment_id = filter_input( INPUT_GET, 'id' );
		$order_id   = filter_input( INPUT_GET, 'order-received' );
		try {
			$payment = WC_Monei_API::get_payment( $payment_id );
			/**
			 * If order is a subscription, we are getting a sequence_id that we will use to charge next payments.
			 */
			if ( $this->is_order_subscription( $order_id ) ) {
				die('a');
				update_post_meta( $order_id, '_monei_sequence_id', $payment->getSequenceId() );
			}
		} catch ( Exception $e ) {

		}

	}

	public function process_subscription( $order_id, $payment_method ) {
		$order               = new WC_Order( $order_id );
		$payload             = parent::create_payload( $order, $payment_method );
		$payload['sequence'] = [
			'type' => 'recurring',
			'recurring' => [
				'frequency' => $this->get_cart_subscription_interval_in_days() // The minimum number of days between the different recurring payments.
			]
		];

		try {
			$payment = WC_Monei_API::create_payment( $payload );
			WC_Monei_Logger::log( 'WC_Monei_API::process_subscription', 'debug' );
			WC_Monei_Logger::log( $payload, 'debug' );
			WC_Monei_Logger::log( $payment, 'debug' );
			do_action( 'wc_gateway_monei_process_subscription_success', $payload, $payment );
			return array(
				'result'   => 'success',
				'redirect' => $payment->getNextAction()->getRedirectUrl(),
			);
		} catch ( Exception $e ) {
			WC_Monei_Logger::log( $e, 'error' );
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'failure',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}

	}


}

