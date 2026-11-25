<?php

use Monei\Core\ContainerProvider;
use Monei\ApiException;
use Monei\Services\CallbackVerifier;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\WpOptionStore;
use Monei\Services\sdk\MoneiSdkClientFactory;
use Monei\Services\ApiKeyService;
use Monei\Services\PaymentMethodFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;  // Exit if accessed directly
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
	private CallbackVerifier $callbackVerifier;

	/**
	 * Constructor.
	 */
	public function __construct( bool $logging = false ) {
		$this->logging = $logging;
		// Handles request from MONEI.
		add_action( 'woocommerce_api_monei_ipn', array( $this, 'check_ipn_request' ) );
		// TODO use the container
		$apiKeyService                = new ApiKeyService();
		$sdkClient                    = new MoneiSdkClientFactory( $apiKeyService );
		$this->moneiPaymentServices   = new MoneiPaymentServices( $sdkClient );
		$container                    = ContainerProvider::getContainer();
		$this->paymentMethodFormatter = $container->get( PaymentMethodFormatter::class );
		$this->callbackVerifier       = new CallbackVerifier(
			new WpOptionStore(),
			'time',
			function ( string $order_id ) {
				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					return null;
				}
				return array(
					'paymentId'       => (string) $order->get_meta( '_payment_order_number_monei', true ),
					'paymentStatus'   => (string) $order->get_meta( '_payment_order_status_monei', true ),
					'awaitingPayment' => $order->has_status( array( 'pending', 'on-hold', 'failed' ) ),
					'monei'           => false !== strpos( $order->get_payment_method(), 'monei' ),
				);
			},
			function ( string $mode, string $payment_id ) use ( $sdkClient ) {
				return $sdkClient->get_client_for_mode( $mode )->payments->get( $payment_id );
			}
		);
	}

	/**
	 * Check for MONEI IPN Notifications.
	 *
	 * @access public
	 * @return void
	 */
	public function check_ipn_request() {
		// Enforce POST-only webhook endpoint.
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && ( 'POST' !== wc_clean( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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

		// A shop with a scoped key verifies a callback without the header by fetching the payment.
		if ( ! isset( $_SERVER['HTTP_MONEI_SIGNATURE'] ) && ! $this->callbackVerifier->any_mode_connected() ) {
			WC_Monei_Logger::log( '[MONEI] Webhook missing signature header from IP: ' . WC_Geolocation::get_ip_address() );
			http_response_code( 401 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Unauthorized';
			exit;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$signature = isset( $_SERVER['HTTP_MONEI_SIGNATURE'] ) ? wp_unslash( $_SERVER['HTTP_MONEI_SIGNATURE'] ) : null;
		$decision  = $this->callbackVerifier->verify( (string) $raw_body, $signature );

		if ( 401 === $decision['status'] ) {
			// Signature verification failed - this is a security issue, always log.
			$e = new ApiException( '[401] Signature verification failed', 401 );
			WC_Monei_Logger::log( '[MONEI] Webhook signature verification failed: ' . $e->getMessage() );
			do_action( 'woocommerce_monei_handle_failed_ipn', null, $e );
			http_response_code( 401 );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 'Unauthorized';
			exit;
		}

		if ( null === $decision['payment'] ) {
			WC_Monei_Logger::log( '[MONEI] Webhook not processed: ' . $decision['reason'] );
			http_response_code( $decision['status'] );
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo 200 === $decision['status'] ? 'OK' : 'Not processed';
			exit;
		}

		// Only the payment the verifier returns is processed, never the request body.
		$payload = $decision['payment'];
		// A payment can hold a payment token and customer data: log only these fields.
		$this->logging && WC_Monei_Logger::logDebug( array_intersect_key( $payload, array_flip( array( 'id', 'orderId', 'status', 'statusCode', 'amount', 'currency', 'livemode' ) ) ) );

		try {
			$this->handle_valid_ipn( $payload, $decision['model'] );
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
		}

		exit;
	}

	/**
	 * MONEI IPN
	 *
	 * @access public
	 * @param array                     $payload
	 * @param \Monei\Model\Payment|null $payment The payment the verifier fetched, null when it did not fetch.
	 * @return void
	 */
	protected function handle_valid_ipn( $payload, $payment = null ) {
		$order_id = $payload['orderId'];
		$monei_id = $payload['id'];
		$status   = $payload['status'];
		// A fetched payment has no key for an empty field.
		$status_code    = $payload['statusCode'] ?? '';
		$status_message = $payload['statusMessage'] ?? '';
		$amount         = $payload['amount'];

		$order = wc_get_order( $order_id );
		// IPN can come from a non-order payment ( 0 eur order to tokenize card )
		// In this case, we just need to ignore it.
		if ( ! $order ) {
			return;
		}

		/** Saving related information into order meta. */
		$order->update_meta_data( '_payment_order_number_monei', $monei_id );
		$order->update_meta_data( '_payment_order_status_monei', $status );
		$order->update_meta_data( '_payment_order_status_code_monei', $status_code );
		$order->update_meta_data( '_payment_order_status_message_monei', $status_message );

		// Fetch payment from API to get payment method information
		try {
			$this->moneiPaymentServices->set_order( $order );
			// The fetch path already fetched it, and counted the fetch against its quota.
			$payment                = $payment ?? $this->moneiPaymentServices->get_payment( $monei_id );
			$payment_method_display = $this->paymentMethodFormatter->get_payment_method_display_from_payment( $payment );
			if ( $payment_method_display ) {
				$order->update_meta_data( '_monei_payment_method_display', $payment_method_display );
			}
		} catch ( Exception $e ) {
			// Log but don't fail - payment method display is not critical
			WC_Monei_Logger::logWarning( '[MONEI] Failed to get payment method display: ' . $e->getMessage() );
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
			if ( $this->hold_on_amount_mismatch( $order, $amount ) ) {
				return;
			}
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
			if ( $this->hold_on_amount_mismatch( $order, $amount ) ) {
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
	 * If amounts don't match (within 1 cent tolerance), mark order on-hold for manual validation.
	 * Absolute difference check allows subscription validation (0 EUR + 1 cent) while preventing
	 * replay attacks with mismatched amounts.
	 *
	 * @param WC_Order $order
	 * @param int      $amount The payment amount in cents.
	 * @return bool Whether the amounts differ.
	 */
	private function hold_on_amount_mismatch( $order, $amount ): bool {
		$expected_amount = monei_price_format( $order->get_total() );
		if ( abs( (int) $amount - $expected_amount ) <= 1 ) {
			return false;
		}
		$order->update_status(
			'on-hold',
			sprintf(
				/* translators: 1: Order amount, 2: Notification amount */
				__( 'Validation error: Order vs. Notification amounts do not match (order: %1$s - received: %2$s).', 'monei' ),
				$expected_amount,
				$amount
			)
		);
		return true;
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
		$this->logging && WC_Monei_Logger::logDebug( 'IPN Request from ' . WC_Geolocation::get_ip_address() . ': ' . "\n\n" . $headers . "\n\n" . $raw_body . "\n" );
	}
}
