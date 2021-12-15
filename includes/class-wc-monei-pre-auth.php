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
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_payment_when_pre_auth' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_payment_when_pre_auth' ) );
	}

	/**
	 * Capture $order_id Payment.
	 *
	 * @param $order_id
	 */
	public function capture_payment_when_pre_auth( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $payment_id = $this->is_pre_auth_order( $order ) ) {
			return;
		}

		try {
			WC_Monei_API::set_order( $order );
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

	/**
	 * Capture $order_id Pre-Authorized payment.
	 *
	 * @param $order_id
	 */
	public function cancel_payment_when_pre_auth( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $payment_id = $this->is_pre_auth_order( $order ) ) {
			return;
		}

		try {
			WC_Monei_API::set_order( $order );
			$result = WC_Monei_API::cancel_payment( $payment_id );
			WC_Monei_Logger::log( 'Cancel Payment Payment OK.', 'debug' );
			WC_Monei_Logger::log( $result, 'debug' );
			$order->add_order_note( '<strong>Cancel Payment approved</strong>: Status: ' . $result->getStatus() . ' ' . $result->getStatusMessage() . ' ' . $result->getStatusCode() );
		} catch ( Exception $e ) {
			WC_Monei_Logger::log( 'Cancel Payment error: ' . $e->getMessage(), 'error' );
			$order->add_order_note( '<strong>Cancel Payment error</strong>: ' . $e->getMessage() );
		}
	}

	/**
	 * Checks to know if we are on a pre-auth order.
	 * If it is, we return monei payment id.
	 *
	 * @param WC_Order $order
	 *
	 * @return string|false
	 */
	protected function is_pre_auth_order( $order ) {

		/**
		 * If not MONEI payment, bail.
		 */
		if ( false === strpos( $order->get_payment_method(), 'monei' ) ) {
			return false;
		}

		/**
		 * If not payment_id, bail.
		 */
		$payment_id = $order->get_meta( '_payment_order_number_monei', true );
		if ( ! $payment_id ) {
			return false;
		}

		/**
		 * If order has already being captured, bail.
		 */
		if ( ! $order->get_meta( '_payment_not_captured_monei', true ) ) {
			return false;
		}

		return $payment_id;
	}

}

new WC_Monei_Pre_Auth();

