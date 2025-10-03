<?php

use Monei\Core\ContainerProvider;
use Monei\Services\ApiKeyService;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\PaymentMethodFormatter;
use Monei\Services\sdk\MoneiSdkClientFactory;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * IPN Helper Class
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_IPN {

	private $logging;
	private MoneiPaymentServices $moneiPaymentServices;
	private PaymentMethodFormatter $paymentMethodFormatter;

	/**
	 * Constructor.
	 */
	public function __construct( bool $logging = false ) {
		$this->logging = $logging;
		// Handles request from MONEI.
		add_action( 'woocommerce_api_monei_ipn', array( $this, 'check_ipn_request' ) );
		//TODO use the container
		$apiKeyService                = new ApiKeyService();
		$sdkClient                    = new MoneiSdkClientFactory( $apiKeyService );
		$this->moneiPaymentServices   = new MoneiPaymentServices( $sdkClient );
		$container                    = ContainerProvider::getContainer();
		$this->paymentMethodFormatter = $container->get( PaymentMethodFormatter::class );
	}

	/**
	 * Check for MONEI IPN Notifications.
	 *
	 * @access public
	 * @return void
	 */
	public function check_ipn_request() {
		// Enforce POST-only webhook endpoint.
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && ( 'POST' !== wc_clean( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
            //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			WC_Monei_Logger::log( '[MONEI] Webhook received non-POST request: ' . wc_clean( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
			http_response_code( 405 );
			header( 'Allow: POST' );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Method Not Allowed';
			exit;
		}

		$headers  = $this->get_all_headers();
		$raw_body = file_get_contents( 'php://input' );
		$this->log_ipn_request( $headers, $raw_body );

		// Check for signature header.
		if ( ! isset( $_SERVER['HTTP_MONEI_SIGNATURE'] ) ) {
			WC_Monei_Logger::log( '[MONEI] Webhook missing signature header from IP: ' . WC_Geolocation::get_ip_address() );
			http_response_code( 401 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Unauthorized';
			exit;
		}

		$payload = null;

		try {
            //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$payload = $this->verify_signature_get_payload( $raw_body, wc_clean( wp_unslash( $_SERVER['HTTP_MONEI_SIGNATURE'] ) ) );
			$this->logging && WC_Monei_Logger::log( $payload, 'debug' );
		} catch ( Throwable $e ) {
			// Signature verification failed - this is a security issue, always log.
			WC_Monei_Logger::log( '[MONEI] Webhook signature verification failed: ' . $e->getMessage() );
			do_action( 'woocommerce_monei_handle_failed_ipn', $payload, $e );
			http_response_code( 401 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Unauthorized';
			exit;
		}

		// Acquire lock to prevent concurrent processing of the same payment.
		$payment_id = $payload['id'] ?? '';
		$lock_key   = 'monei_ipn_' . md5( $payment_id );
		$lock_value = wp_rand();

		if ( ! $this->acquire_lock( $lock_key, $lock_value ) ) {
			// Another process is handling this payment.
			$this->logging && WC_Monei_Logger::log( '[MONEI] Webhook already being processed [payment_id=' . $payment_id . ']', 'debug' );
			http_response_code( 200 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'OK';
			exit;
		}

		try {
			$this->handle_valid_ipn( $payload );
			do_action( 'woocommerce_monei_handle_valid_ipn', $payload );
			http_response_code( 200 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'OK';
		} catch ( Throwable $e ) {
			// Processing error - always log.
			WC_Monei_Logger::log( '[MONEI] Webhook processing error: ' . $e->getMessage() );
			do_action( 'woocommerce_monei_handle_processing_error', $payload, $e );
			http_response_code( 400 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Bad Request';
		} finally {
			// Always release the lock.
			$this->release_lock( $lock_key, $lock_value );
		}

		exit;
	}

	/**
	 * MONEI IPN
	 *
	 * @access public
	 * @param array $payload
	 * @return void
	 */
	protected function handle_valid_ipn( $payload ) {

		$order_id       = $payload['orderId'];
		$monei_id       = $payload['id'];
		$status         = $payload['status'];
		$status_code    = $payload['statusCode'];
		$status_message = $payload['statusMessage'];
		$amount         = $payload['amount'];

		$order = wc_get_order( $order_id );
		// IPN can come from a non-order payment ( 0 eur order to tokenize card )
		// In this case, we just need to ignore it.
		if ( ! $order ) {
			return;
		}

		// Check if this payment was already processed (idempotency check).
		$processed_payment_id = $order->get_meta( '_monei_payment_id_processed', true );
		if ( $processed_payment_id === $monei_id ) {
			// Payment already processed, skip to prevent duplicate processing.
			$this->logging && WC_Monei_Logger::log( '[MONEI] Payment already processed [payment_id=' . $monei_id . ', order_id=' . $order_id . ']', 'debug' );
			return;
		}

		/**
		 * Saving related information into order meta.
		 */
		$order->update_meta_data( '_payment_order_number_monei', $monei_id );
		$order->update_meta_data( '_payment_order_status_monei', $status );
		$order->update_meta_data( '_payment_order_status_code_monei', $status_code );
		$order->update_meta_data( '_payment_order_status_message_monei', $status_message );
		$order->update_meta_data( '_monei_payment_id_processed', $monei_id );

		// Fetch payment from API to get payment method information
		try {
			$this->moneiPaymentServices->set_order( $order );
			$payment                = $this->moneiPaymentServices->get_payment( $monei_id );
			$payment_method_display = $this->paymentMethodFormatter->get_payment_method_display_from_payment( $payment );
			if ( $payment_method_display ) {
				$order->update_meta_data( '_monei_payment_method_display', $payment_method_display );
			}
		} catch ( \Exception $e ) {
			// Log but don't fail - payment method display is not critical
			WC_Monei_Logger::log( '[MONEI] Failed to get payment method display: ' . $e->getMessage(), 'warning' );
		}

		$order->save();

		if ( 'PENDING' === $status ) {
			// Payment is pending (e.g., Multibanco waiting for payment).
			$order_note  = __( 'HTTP Notification received - <strong>Payment Pending</strong> ', 'monei' ) . '. <br><br>';
			$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $monei_id . '. <br><br>';
			$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $status_message;
			$order->add_order_note( $order_note );
			$order->update_status( 'on-hold', __( 'Payment pending confirmation', 'monei' ) );
			return;
		}

		if ( 'FAILED' === $status ) {
			// Order failed.
			$order->add_order_note( __( 'HTTP Notification received - <strong>Payment Failed</strong> ', 'monei' ) . $status );
			$order->update_status( 'failed', 'Failed MONEI payment: ' . $status_message );
			return;
		}

		if ( 'CANCELED' === $status ) {
			// Order cancelled.
			$order->add_order_note( __( 'HTTP Notification received - <strong>Payment Cancelled</strong> ', 'monei' ) . $status );
			$message = __( 'Cancelled by MONEI: ', 'monei' ) . $status_message;
			$order->add_order_note( $message );
			$order->update_status( 'cancelled', $message );
			return;
		}

		if ( 'EXPIRED' === $status ) {
			// Payment expired.
			$order->add_order_note( __( 'HTTP Notification received - <strong>Payment Expired</strong> ', 'monei' ) . $status );
			$message = __( 'Payment expired: ', 'monei' ) . $status_message;
			$order->add_order_note( $message );
			$order->update_status( 'failed', $message );
			return;
		}

		if ( 'AUTHORIZED' === $status ) {
			// We save is a non captured order.
			$order->update_meta_data( '_payment_not_captured_monei', 1 );

			$order_note  = __( 'HTTP Notification received - <strong>Payment Authorized</strong> ', 'monei' ) . '. <br><br>';
			$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $monei_id . '. <br><br>';
			$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $status_message;
			$order->add_order_note( $order_note );
			$order->update_status( 'on-hold', __( 'Order On-Hold by MONEI', 'monei' ) );
			return;
		}

		if ( 'SUCCEEDED' === $status ) {
			$order_total = $order->get_total();

			/**
			 * If amounts don't match, we mark the order on-hold for manual validation.
			 * 1 cent exception, for subscriptions when 0 sign ups are done.
			 */
			if ( ( (int) $amount !== monei_price_format( $order_total ) ) && ( 1 !== $amount ) ) {
				$order->update_status(
					'on-hold',
					sprintf(
					/* translators: 1: Order amount, 2: Notification amount */
						__( 'Validation error: Order vs. Notification amounts do not match (order: %1$s - received: %2$s).', 'monei' ),
						$amount,
						monei_price_format( $order_total )
					)
				);
				return;
			}

			$order_note  = __( 'HTTP Notification received - <strong>Payment Completed</strong> ', 'monei' ) . '. <br><br>';
			$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $monei_id . '. <br><br>';
			$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $status_message;

			// Payment completed.
			$order->add_order_note( $order_note );

			$order->payment_complete();
			if ( 'completed' === monei_get_settings( 'orderdo', monei_get_option_key_from_order( $order ) ) ) {
				$order->update_status( 'completed', __( 'Order Completed by MONEI', 'monei' ) );
			}
			return;
		}

		if ( 'REFUNDED' === $status ) {
			// Payment fully refunded.
			$order_note  = __( 'HTTP Notification received - <strong>Payment Refunded</strong> ', 'monei' ) . '. <br><br>';
			$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $monei_id . '. <br><br>';
			$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $status_message;
			$order->add_order_note( $order_note );
			$order->update_status( 'refunded', __( 'Payment refunded by MONEI', 'monei' ) );
			return;
		}

		if ( 'PARTIALLY_REFUNDED' === $status ) {
			// Payment partially refunded.
			$refunded_amount = $payload['refundedAmount'] ?? 0;
			$order_note      = __( 'HTTP Notification received - <strong>Payment Partially Refunded</strong> ', 'monei' ) . '. <br><br>';
			$order_note     .= __( 'MONEI Transaction id: ', 'monei' ) . $monei_id . '. <br><br>';
			$order_note     .= __( 'Refunded amount: ', 'monei' ) . wc_price( $refunded_amount / 100 ) . '. <br><br>';
			$order_note     .= __( 'MONEI Status Message: ', 'monei' ) . $status_message;
			$order->add_order_note( $order_note );
			// Note: WooCommerce doesn't have a built-in 'partially-refunded' status.
			// The order remains in its current status with a note about the partial refund.
			return;
		}
	}

	/**
	 * Verify signature, if all good returns payload.
	 * Throws Exception if signature is not valid.
	 *
	 * @param string $request_body    The request body.
	 * @param string $monei_signature The MONEI signature header.
	 *
	 * @return array
	 * @throws \Monei\ApiException
	 */
	protected function verify_signature_get_payload( $request_body, $monei_signature ) {
		$decoded_body = json_decode( $request_body );
		if ( isset( $decoded_body->orderId ) ) {
			$this->moneiPaymentServices->set_order( $decoded_body->orderId );
		}
		return (array) $this->moneiPaymentServices->verify_signature( $request_body, $monei_signature );
	}

	/**
	 * getallheaders is only available for apache, we need a fallback in case of nginx or others,
	 * http://php.net/manual/es/function.getallheaders.php
	 *
	 * @return array
	 */
	private function get_all_headers() {
		if ( ! function_exists( 'getallheaders' ) ) {
			$headers = array();
			foreach ( $_SERVER as $name => $value ) {
				if ( substr( $name, 0, 5 ) === 'HTTP_' ) {
					$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
				}
			}
			return $headers;
		} else {
			return getallheaders() ?: array();
		}
	}

	/**
	 * @param $headers
	 * @param $raw_body
	 */
	protected function log_ipn_request( $headers, $raw_body ) {
		foreach ( $headers as $key => $value ) {
			$headers[ $key ] = $key . ': ' . $value;
		}
		$headers = implode( "\n", $headers );
		$this->logging && WC_Monei_Logger::log( 'IPN Request from ' . WC_Geolocation::get_ip_address() . ': ' . "\n\n" . $headers . "\n\n" . $raw_body . "\n", 'debug' );
	}

	/**
	 * Acquire a lock using WordPress transients.
	 *
	 * @param string $lock_key   The lock key.
	 * @param mixed  $lock_value The lock value.
	 * @param int    $timeout    Lock timeout in seconds (default 30).
	 * @return bool True if lock acquired, false otherwise.
	 */
	private function acquire_lock( $lock_key, $lock_value, $timeout = 30 ) {
		// Try to set transient. If it already exists, add_transient returns false.
		$acquired = set_transient( $lock_key, $lock_value, $timeout );

		if ( ! $acquired ) {
			// Transient already exists. Check if it's stale.
			$existing_value = get_transient( $lock_key );
			if ( false === $existing_value ) {
				// Transient expired between checks, try again.
				return set_transient( $lock_key, $lock_value, $timeout );
			}
			return false;
		}

		return true;
	}

	/**
	 * Release a lock using WordPress transients.
	 *
	 * @param string $lock_key   The lock key.
	 * @param mixed  $lock_value The lock value to verify ownership.
	 * @return void
	 */
	private function release_lock( $lock_key, $lock_value ) {
		$existing_value = get_transient( $lock_key );

		// Only delete if we own the lock.
		if ( $existing_value === $lock_value ) {
			delete_transient( $lock_key );
		}
	}
}
