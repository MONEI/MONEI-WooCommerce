<?php

use Monei\Services\ApiKeyService;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\sdk\MoneiSdkClientFactory;

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
	private MoneiPaymentServices $moneiPaymentServices;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment_when_pre_auth' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment_when_pre_auth' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_payment_when_pre_auth' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_payment_when_pre_auth' ) );
		//TODO use the container
		$apiKeyService              = new ApiKeyService();
		$sdkClient                  = new MoneiSdkClientFactory( $apiKeyService );
		$this->moneiPaymentServices = new MoneiPaymentServices( $sdkClient );
	}

	/**
	 * Capture $order_id Payment.
	 *
	 * @param $order_id
	 */
	public function capture_payment_when_pre_auth( $order_id ) {
		$order      = wc_get_order( $order_id );
		$payment_id = $this->is_pre_auth_order( $order );
		if ( ! $payment_id ) {
			return;
		}

		try {
			$this->moneiPaymentServices->set_order( $order );
			$result = $this->moneiPaymentServices->capture_payment( $payment_id, monei_price_format( $order->get_total() ) );
			// Deleting pre-auth metadata, once the order is captured.
			// ⚠️ `delete_meta_data()` only changes the object in memory, so the
			// deletion has to be persisted or the marker survives the capture and a
			// later cancellation tries to release an authorization already gone.
			// ⚠️ `save_meta_data()` and NOT `save()`: this runs inside a status
			// transition, where the order object still carries the status it is
			// moving away from. A full save writes that stale status back and puts
			// the order straight back on hold — captured, but reading as unpaid.
			$order->delete_meta_data( '_payment_not_captured_monei' );
			$order->save_meta_data();

			WC_Monei_Logger::logDebug( 'Capture Payment OK.' );
			WC_Monei_Logger::logDebug( $result );
			$order->add_order_note( '<strong>Capture approved</strong>: Status: ' . $result->getStatus() . ' ' . $result->getStatusMessage() . ' ' . $result->getStatusCode() );
		} catch ( Exception $e ) {
			WC_Monei_Logger::logError( 'Capture error: ' . $e->getMessage() );
			$order->add_order_note( '<strong>Capture error</strong>: ' . $e->getMessage() );
		}
	}

	/**
	 * Capture $order_id Pre-Authorized payment.
	 *
	 * @param $order_id
	 */
	public function cancel_payment_when_pre_auth( $order_id ) {
		$order      = wc_get_order( $order_id );
		$payment_id = $this->is_pre_auth_order( $order );
		if ( ! $payment_id ) {
			return;
		}

		try {
			$this->moneiPaymentServices->set_order( $order );
			$result = $this->moneiPaymentServices->cancel_payment( $payment_id );
			// A released authorization holds nothing either, so the marker goes the
			// same way it does after a capture.
			$order->delete_meta_data( '_payment_not_captured_monei' );
			$order->save_meta_data();

			WC_Monei_Logger::logDebug( 'Cancel Payment Payment OK.' );
			WC_Monei_Logger::logDebug( $result );
			$order->add_order_note( '<strong>Cancel Payment approved</strong>: Status: ' . $result->getStatus() . ' ' . $result->getStatusMessage() . ' ' . $result->getStatusCode() );
		} catch ( Exception $e ) {
			WC_Monei_Logger::logError( 'Cancel Payment error: ' . $e->getMessage() );
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
