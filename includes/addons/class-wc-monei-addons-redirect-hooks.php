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

		$payment_id = filter_input( INPUT_GET, 'id' );
		$order_id   = filter_input( INPUT_GET, 'orderId' );

		/**
		 * Bail when not subscription.
		 */
		if ( ! $this->is_order_subscription( $order_id ) ) {
			return;
		}

		try {
			/**
			 * If order is a subscription, we are getting a sequence_id that we will use to charge next payments.
			 */
			$payment = WC_Monei_API::get_payment( $payment_id );
			update_post_meta( $order_id, '_monei_sequence_id', $payment->getSequenceId() );
		} catch ( Exception $e ) {
			wc_add_notice( __( 'Error while saving sequence id. Please contact admin. Payment ID: ', 'monei' ) . $payment_id, 'error' );
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
		}
	}
}

new WC_Monei_Addons_Redirect_Hooks();

