<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * For readability's sake, we will create addons redirect logic here instead of using class-wc-monei-redirect-hooks.php.
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_Addons_Redirect_Hooks {

	/**
	 * Use Subscription trait.
	 */
	use WC_Monei_Subscriptions_Trait;

	/**
	 * Hooks on redirects.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'subscriptions_save_sequence_id' ) );
		add_action( 'template_redirect', array( $this, 'subscriptions_save_sequence_id_on_payment_method_change' ) );
	}

	/**
	 * When subscribers, changes manually payment method from my account.
	 * We need to update the subscription sequence_id and cc information.
	 * On success, we are sent to "My account"
	 */
	public function subscriptions_save_sequence_id_on_payment_method_change() {
		if ( ! is_account_page() ) {
			return;
		}

		if ( ! isset( $_GET['id'] ) ) {
			return;
		}

		$payment_id = filter_input( INPUT_GET, 'id', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field') );
		$order_id   = filter_input( INPUT_GET, 'orderId', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field') );

		$verification_order_id = explode( '_', $order_id );
		// Order ID will have a format like follows.
		// orderId=453_verification1635257618
		if ( ! isset( $verification_order_id[1] ) || false === strpos( $verification_order_id[1], 'verification' ) ) {
			return;
		}

		$order_id = $verification_order_id[0];
		if ( ! $this->is_order_subscription( $order_id ) ) {
			return;
		}

		try {
			/**
			 * We need to update parent from subscription, where sequence id is stored.
			 */
			$payment      = WC_Monei_API::get_payment( $payment_id );
			$subscription = new WC_Subscription( $order_id );

			$subscription->update_meta_data( '_monei_sequence_id', $payment->getSequenceId() );
			$subscription->update_meta_data( '_monei_payment_method_brand', $payment->getPaymentMethod()->getCard()->getBrand() );
			$subscription->update_meta_data( '_monei_payment_method_4_last_digits', $payment->getPaymentMethod()->getCard()->getLast4() );
			$subscription->save_meta_data();
		} catch ( Exception $e ) {
			wc_add_notice( __( 'Error while saving sequence id. Please contact admin. Payment ID: ', 'monei' ) . $payment_id, 'error' );
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
		}
	}

	/**
	 * When a payment is done on a subscription order, in order_received_page we need to save its sequence id.
	 * This sequence id will be used afterwards on recurring payments.
	 */
	public function subscriptions_save_sequence_id() {
		if ( ! is_order_received_page() ) {
			return;
		}

		if ( ! isset( $_GET['id'] ) ) {
			return;
		}

		$payment_id = filter_input( INPUT_GET, 'id', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field') );
		$order_id   = filter_input( INPUT_GET, 'orderId', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field') );

		/**
		 * Bail when not subscription.
		 */
		if ( ! $this->is_order_subscription( $order_id ) ) {
			return;
		}

		try {

			$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => array( 'any' ) ) );
			if ( ! $subscriptions ) {
				return;
			}

			$payment = WC_Monei_API::get_payment( $payment_id );
			/**
			 * Iterate all subscriptions contained in the order, and add sequence id and cc data individually.
			 */
			foreach ( $subscriptions as $subscription_id => $subscription ) {
				$subscription->update_meta_data( '_monei_sequence_id', $payment->getSequenceId() );
				$subscription->update_meta_data( '_monei_payment_method_brand', $payment->getPaymentMethod()->getCard()->getBrand() );
				$subscription->update_meta_data( '_monei_payment_method_4_last_digits', $payment->getPaymentMethod()->getCard()->getLast4() );
				$subscription->save_meta_data();
			}
		} catch ( Exception $e ) {
			wc_add_notice( __( 'Error while saving sequence id. Please contact admin. Payment ID: ', 'monei' ) . $payment_id, 'error' );
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
		}
	}
}

new WC_Monei_Addons_Redirect_Hooks();

