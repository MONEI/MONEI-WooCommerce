<?php
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

	/**
	 * Constructor.
	 */
	public function __construct(bool $logging = false) {
		$this->logging = $logging;
		// Handles request from MONEI.
		add_action( 'woocommerce_api_monei_ipn', array( $this, 'check_ipn_request' ) );
	}

	/**
	 * Check for MONEI IPN Notifications.
	 *
	 * @access public
	 * @return void
	 */
	public function check_ipn_request() {

		if ( ( 'POST' !== sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) ) ) {
			return;
		}

		$headers  = $this->get_all_headers();
		$raw_body = @file_get_contents( 'php://input' );
		$this->log_ipn_request( $headers, $raw_body );

		try {
			$payload = $this->verify_signature_get_payload( $raw_body, sanitize_text_field( $_SERVER['HTTP_MONEI_SIGNATURE'] ) );
			$this->logging && WC_Monei_Logger::log( $payload, 'debug' );
			$this->handle_valid_ipn( $payload );
			do_action( 'woocommerce_monei_handle_valid_ipn', $payload );
			http_response_code( 200 );
			exit();
		} catch ( Exception $e ) {
			do_action( 'woocommerce_monei_handle_failed_ipn', $payload, $e );
			$this->logging && WC_Monei_Logger::log( 'Failed IPN request: ' . $e->getMessage() );
			// Invalid signature
			http_response_code( 400 );
			exit();
		}
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

		/**
		 * Saving related information into order meta.
		 */
		$order->update_meta_data( '_payment_order_number_monei', $monei_id );
		$order->update_meta_data( '_payment_order_status_monei', $status );
		$order->update_meta_data( '_payment_order_status_code_monei', $status_code );
		$order->update_meta_data( '_payment_order_status_message_monei', $status_message );

		if ( 'FAILED' === $status ) {
			// Order failed.
			$order->add_order_note( __( 'HTTP Notification received - <strong>Payment Failed</strong>', 'monei' ) . $status );
			$order->update_status( 'pending', 'Failed MONEI payment: ' . $status_message );
			return;
		}

		if ( 'CANCELED' === $status ) {
			// Order cancelled.
			$order->add_order_note( __( 'HTTP Notification received - <strong>Payment Cancelled</strong>', 'monei' ) . $status );
			$order->update_status( 'cancelled', 'Cancelled by MONEI: ' . $status_message );
			return;
		}

		if ( 'AUTHORIZED' === $status ) {
			// We save is a non captured order.
			$order->update_meta_data( '_payment_not_captured_monei', 1 );

			$order_note  = __( 'HTTP Notification received - <strong>Payment Authorized</strong>', 'monei' ) . '. <br><br>';
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
			 * 1 cent exception, for subscriptions when 0 sing ups are done.
			 */
			if ( ( (int) $amount !== monei_price_format( $order_total ) ) && ( 1 !== $amount ) ) {
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: Order vs. Notification amounts do not match (order: %1$s - received: %2&s).', 'monei' ), $amount, monei_price_format( $order_total ) ) );
				exit;
			}

			$order_note  = __( 'HTTP Notification received - <strong>Payment Completed</strong>', 'monei' ) . '. <br><br>';
			$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $monei_id . '. <br><br>';
			$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $status_message;

			// Payment completed.
			$order->add_order_note( $order_note );

			$order->payment_complete();
			if ( 'completed' === monei_get_settings( 'orderdo', monei_get_option_key_from_order( $order ) ) ) {
				$order->update_status( 'completed', __( 'Order Completed by MONEI', 'monei' ) );
			}
		}
	}

	/**
	 * Verify signature, if all good returns payload.
	 * Throws Exception if Signaturit not valid.
	 *
	 * @param $request_body
	 * @param $monei_signature
	 *
	 * @return array
	 * @throws \OpenAPI\Client\ApiException
	 */
	protected function verify_signature_get_payload( $request_body, $monei_signature ) {
		$decoded_body = json_decode( $request_body );
		if ( isset( $decoded_body->orderId ) ) {
			WC_Monei_API::set_order( $decoded_body->orderId );
		}
		return (array) WC_Monei_API::verify_signature( $request_body, $monei_signature );
	}

	/**
	 * getallheaders is only available for apache, we need a fallback in case of nginx or others,
	 * http://php.net/manual/es/function.getallheaders.php
	 * @return array|false
	 */
	private function get_all_headers() {
		if ( ! function_exists( 'getallheaders' ) ) {
			$headers = array();
			foreach ( $_SERVER as $name => $value ) {
				if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
					$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
				}
			}
			return $headers;
		} else {
			return getallheaders();
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
		$this->logging &&WC_Monei_Logger::log( 'IPN Request from ' . WC_Geolocation::get_ip_address() . ': ' . "\n\n" . $headers . "\n\n" . $raw_body . "\n", 'debug' );
	}

}

