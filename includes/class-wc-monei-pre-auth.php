<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Pre Authorization class.
 * Pre-Auth status is "on-hold".
 * When admin changes status to completed or processing, we capture the payment.
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_Pre_Auth {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment_when_pre_auth' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment_when_pre_auth' ) );
	}

	/**
	 * Capture $order_id Payment.
	 *
	 * @param $order_id
	 */
	public function capture_payment_when_pre_auth( $order_id ) {
		$order = wc_get_order( $order_id );

		/**
		 * If not MONEI payment, bail.
		 */
		if ( 'monei' !== $order->get_payment_method() ) {
			return;
		}

		/**
		 * If not payment_id, bail.
		 */
		$payment_id = $order->get_meta( '_payment_order_number_monei', true );
		if ( ! $payment_id ) {
			return;
		}

		/**
		 * If order has already being captured, bail.
		 */
		if ( ! $order->get_meta( '_payment_not_captured_monei', true ) ) {
			return;
		}

		try {
			$result = WC_Monei_API::capture_payment( $payment_id, monei_price_format( $order->get_total() ) );
			// Deleting pre-auth metadata, once the order is captured.
			$order->delete_meta_data( '_payment_not_captured_monei' );

			WC_Monei_Logger::log( 'Capture Payment OK.', 'debug' );
			WC_Monei_Logger::log( $result, 'debug' );
			$order->add_order_note( '<strong>Capture approved</strong>: Status: ' . $result->getStatus() . ' ' . $result->getStatusMessage() . ' ' . $result->getStatusCode() );
		} catch ( Exception $e ) {
			WC_Monei_Logger::log( 'Capture error: ' . $e->getMessage(), 'error' );
			$order->add_order_note( '<strong>Capture error</strong>: ' . $e->getMessage() );
		}
	}

}

new WC_Monei_Pre_Auth();

