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

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Handles request from MONEI.
		add_action( 'woocommerce_api_monei_ipn', array( $this, 'check_ipn_request' ) );

		// Handle valid IPN message.
		add_action( 'woocommerce_monei_handle_valid_ipn', array( $this, 'handle_valid_ipn' ) );
	}

	/**
	 * Check for MONEI IPN Notifications.
	 *
	 * @access public
	 * @return void
	 */
	public function check_ipn_request() {

		if ( ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) ) {
			return;
		}

		$headers  = $this->get_all_headers();
		$raw_body = @file_get_contents( 'php://input' );
		$this->log_ipn_request( $headers, $raw_body );

		try {
			$payload = $this->verify_signature_get_payload( $raw_body, $_SERVER['HTTP_MONEI_SIGNATURE'] );
			WC_Monei_Logger::log( $payload, 'debug' );
			http_response_code( 200 );
			do_action( 'woocommerce_monei_handle_valid_ipn', $payload );
		} catch ( Exception $e ) {
			WC_Monei_Logger::log( 'Failed IPN request: ' . $e->getMessage() );
			// Invalid signature
			http_response_code( 400 );
			exit();
		}
	}

	/**
	 * Verify signature, if all good returns payload.
	 * Throws Exception if Signaturit not valid.
	 *
	 * @param $request_body
	 * @param $monei_signature
	 *
	 * @return object
	 * @throws \OpenAPI\Client\ApiException
	 */
	protected function verify_signature_get_payload( $request_body, $monei_signature ) {
		return WC_Monei_API::verify_signature( $request_body, $monei_signature );
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
		WC_Monei_Logger::log( 'IPN Request from ' . WC_Geolocation::get_ip_address() . ': ' . "\n\n" . $headers . "\n\n" . $raw_body . "\n", 'debug' );
	}

	/**
	 * todo: refactor this.
	 * Successful Payment!
	 *
	 * @access public
	 * @param array $posted
	 * @return void
	 */
	function handle_valid_ipn( $data ) {
		global $woocommerce;

		$monei_order_id   = sanitize_text_field( $data->id );
		$order_id         = sanitize_text_field( $data->orderId );
		$message          = sanitize_text_field( $data->message );
		$order2           = substr( $order_id, 3 ); // cojo los 9 digitos del final.
		$order            = $this->get_monei_order( (int) $order2 );
		$status           = sanitize_text_field( $data->status );
		$amount           = floatval( $data->amount ) / 100;
		$json             = file_get_contents( 'php://input' );
		$data             = json_decode( $json );

		if ( 'yes' === $this->logging ) {
			$this->logger->add( 'monei', '$monei_order_id: ' . $monei_order_id );
			$this->logger->add( 'monei', '$order_id: ' . $order_id );
			$this->logger->add( 'monei', '$status: ' . $status );
			$this->logger->add( 'monei', '$message: ' . $message );
		}

		if ( 'SUCCEEDED' === $status ) {
			// authorized.
			$order2    = substr( $order_id, 3 ); //cojo los 9 digitos del final
			$order     = new WC_Order( $order2 );
			$amountwoo = floatval( $order->get_total() );

			if ( $amountwoo !== $amount ) {
				// amount does not match.
				if ( 'yes' === $this->logging ) {
					$this->logger->add( 'monei', 'Payment error: Amounts do not match (order: ' . $amountwoo . ' - received: ' . $amount . ')' );
				}
				// Put this order on-hold for manual checking.
				/* translators: order an received are the amount */
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: Order vs. Notification amounts do not match (order: %1$s - received: %2&s).', 'monei' ), $amountwoo, $amount ) );
				exit;
			}

			if ( ! empty( $monei_order_id ) ) {
				update_post_meta( $order->get_id(), '_payment_order_number_monei', $monei_order_id );
			}

			if ( ! empty( $order_id ) ) {
				update_post_meta( $order->get_id(), '_payment_wc_order_id_monei', $order_id );
			}

			// Payment completed.
			$order->add_order_note( __( 'HTTP Notification received - payment completed', 'monei' ) );
			$order->add_order_note( __( 'MONEI Order Number: ', 'monei' ) . $monei_order_id );
			$is_paid = $this->is_paid( $order2 );
			if ( $is_paid ) {
				return;
			}
			$order->payment_complete();
			if ( 'completed' === $this->status_after_payment ) {
				$order->update_status( 'completed', __( 'Order Completed by MONEI', 'monei' ) );
			}

			$get_token = get_post_meta( $order->get_id(), 'get_token', true );

			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', '$get_token: ' . $get_token );
			}

			if ( 'yes' === $get_token ) {
				$monei        = new Monei\MoneiClient( $this->api_key );
				$data_payment = $monei->payments->get( $monei_order_id );

				$data_array   = json_decode( $data_payment );

				if ( isset( $data_array->paymentToken ) ) {
					if ( 'yes' === $this->logging ) {
						$this->logger->add( 'monei', '$token: ' . $data_array->paymentToken );
						$this->logger->add( 'monei', '$brand: ' . $data->paymentMethod->card->brand );
						$this->logger->add( 'monei', '$lastfour: ' . $data->paymentMethod->card->last4 );
					}
					$token_n  = $data_array->paymentToken;
					$brand    = $data->paymentMethod->card->brand;
					$lastfour = $data->paymentMethod->card->last4;
					$token    = new WC_Payment_Token_CC();
					$token->set_token( $token_n );
					$token->set_gateway_id( 'monei' );
					$token->set_user_id( $order->get_user_id() );
					$token->set_card_type( $brand );
					$token->set_last4( $lastfour );
					$token->set_expiry_month( '12' );
					$token->set_expiry_year( '2040' );
					$token->set_default( true );
					$token->save();
				}
			}

			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', '$data_payment: ' . $data_payment );
			}

			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', 'Payment complete.' );
			}
		} else {
			// Tarjeta caducada.
			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', 'Order cancelled by MONEI: ' . $message );
			}
			// Order cancelled.
			$order->update_status( 'cancelled', 'Cancelled by MONEI: ' . $message );
			$order->add_order_note( 'Order cancelled by MONEI: ' . $message );
			WC()->cart->empty_cart();
		}
	}

}

