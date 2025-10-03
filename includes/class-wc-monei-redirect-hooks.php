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
 * Redirect Helper Class
 * This class will handle different redirect urls from monei.
 * failUrl : The URL the customer will be directed to after transaction has failed, instead of completeUrl (used in hosted payment page). This allows to provide two different URLs for successful and failed payments.
 * cancelUrl : The URL the customer will be directed to if they decide to cancel payment and return to your website (used in hosted payment page).
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_Redirect_Hooks {
	private MoneiPaymentServices $moneiPaymentServices;
	private PaymentMethodFormatter $paymentMethodFormatter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_cancelled_order', array( $this, 'add_notice_monei_order_cancelled' ) );
		add_action( 'template_redirect', array( $this, 'add_notice_monei_order_failed' ) );
		add_action( 'wp', array( $this, 'save_payment_token' ) );
		//TODO use the container
		$apiKeyService                = new ApiKeyService();
		$sdkClient                    = new MoneiSdkClientFactory( $apiKeyService );
		$this->moneiPaymentServices   = new MoneiPaymentServices( $sdkClient );
		$container                    = ContainerProvider::getContainer();
		$this->paymentMethodFormatter = $container->get( PaymentMethodFormatter::class );
	}

	/**
	 * When MONEI send us back to orderFailed
	 * We need to show message to the customer.
	 *
	 * @param $order_id
	 * @return void
	 */
	public function add_notice_monei_order_failed() {
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_GET['status'] ) ) {
			return;
		}
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$status = wc_clean( wp_unslash( $_GET['status'] ) );
		if ( $status === 'FAILED' ) {
			wc_add_notice( __( 'The payment failed. Please try again', 'monei' ), 'error' );
		}
		add_filter( 'woocommerce_payment_gateway_get_new_payment_method_option_html', '__return_empty_string' );
	}

	/**
	 * When MONEI send us back to get_cancel_order_url_raw()
	 * We need to show message to the customer + save it into the order.
	 *
	 * @param $order_id
	 * @return void
	 */
	public function add_notice_monei_order_cancelled( $order_id ) {
        // phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['status'] ) && isset( $_GET['message'] ) && 'FAILED' === wc_clean( wp_unslash( $_GET['status'] ) ) ) {
			$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : false;
			$order    = $order_id ? wc_get_order( $order_id ) : false;
			if ( ! $order ) {
				return;
			}

			$order->add_order_note( __( 'MONEI Status: ', 'monei' ) . esc_html( wc_clean( wp_unslash( $_GET['status'] ) ) ) );
			$order->add_order_note( __( 'MONEI message: ', 'monei' ) . esc_html( wc_clean( wp_unslash( $_GET['message'] ) ) ) );

			wc_add_notice( esc_html( wc_clean( wp_unslash( $_GET['message'] ) ) ), 'error' );

			WC_Monei_Logger::log( __( 'Order Cancelled: ', 'monei' ) . $order_id );
			WC_Monei_Logger::log( __( 'MONEI Status: ', 'monei' ) . esc_html( wc_clean( wp_unslash( $_GET['status'] ) ) ) );
			WC_Monei_Logger::log( __( 'MONEI message: ', 'monei' ) . esc_html( wc_clean( wp_unslash( $_GET['message'] ) ) ) );
		}
        // phpcs:enable
	}

	/**
	 * Triggered in is_add_payment_method_page && is_order_received_page.
	 *
	 * When customer adds a CC on its profile, we need to make a 0 EUR payment in order to generate the payment.
	 * This means, we need to send them to MONEI, and in the callback on success, we end up in payment_method_page.
	 * Once we are in payment_method_page, we need to actually get the token from the API and save it in Woo.
	 *
	 * We trigger this same behaviour in order received page. After a successful payment in MONEI we are redirected
	 * to order_received_page. If there is a token available, we need to save it.
	 * We don't do this at IPN level, since right now, token doesn't come thru.
	 *
	 * Also, we verify the payment status from the API to complete the order if the IPN hasn't processed yet (race condition).
	 *
	 * todo: refactor and split code for is_add_payment_method_page and is_order_received_page to make it more readable.
	 */
	public function save_payment_token() {
		if ( ! is_add_payment_method_page() && ! is_order_received_page() ) {
			return;
		}
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_GET['id'] ) ) {
			return;
		}

		/**
		 * In the redirect back (from add payment method), the payment could have been failed, the only way to check is the url $_GET['status']
		 * We should remove the "Payment method successfully added." notice and add a 'Unable to add payment method to your account.' manually.
		 */
		$accepted_statuses = array( 'SUCCEEDED', 'AUTHORIZED' );
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_add_payment_method_page() && ( ! isset( $_GET['status'] ) || ! in_array( wc_clean( wp_unslash( $_GET['status'] ) ), $accepted_statuses, true ) ) ) {
			wc_clear_notices();
			wc_add_notice( __( 'Unable to add payment method to your account.', 'woocommerce' ), 'error' );
			$error_message = filter_input( INPUT_GET, 'message', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );
			if ( $error_message ) {
				wc_add_notice( $error_message, 'error' );
			}
			return;
		}

		$payment_id = filter_input( INPUT_GET, 'id', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );
		$order_id   = filter_input( INPUT_GET, 'orderId', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field' ) );
		try {
			$this->moneiPaymentServices->set_order( $order_id );
			$payment       = $this->moneiPaymentServices->get_payment( $payment_id );
			$payment_token = $payment->getPaymentToken();

			// Verify payment status and complete order if needed (race condition fix)
			// If user arrives before IPN webhook processes, we complete the order here
			if ( $order_id && is_order_received_page() ) {
				$this->verify_and_complete_order( $order_id, $payment );
			}

			// A payment can come without token, user didn't check on save payment method.
			// We just ignore it then and do nothing.
			if ( ! $payment_token ) {
				return;
			}

			/**
			 * If redirect is coming from an actual order, we will have the payment method available in order.
			 */
			if ( $order_id ) {
				$order                 = new WC_Order( $order_id );
				$payment_method_woo_id = $order->get_payment_method();
			} else {
				$payment_method_woo_id = MONEI_GATEWAY_ID;
			}

			$payment_method = $payment->getPaymentMethod();

			// If Token already saved into DB, we just ignore this.
			if ( monei_token_exits( $payment_token, $payment_method_woo_id ) ) {
				return;
			}

			WC_Monei_Logger::log( 'saving tokent into DB', 'debug' );
			WC_Monei_Logger::log( $payment_method, 'debug' );

			$expiration = new DateTime( gmdate( 'm/d/Y', $payment_method->getCard()->getExpiration() ) );

			$token = new WC_Payment_Token_CC();
			$token->set_token( $payment_token );
			$token->set_gateway_id( $payment_method_woo_id );
			$token->set_card_type( $payment_method->getCard()->getBrand() );
			$token->set_last4( $payment_method->getCard()->getLast4() );
			$token->set_expiry_month( $expiration->format( 'm' ) );
			$token->set_expiry_year( $expiration->format( 'Y' ) );
			$token->set_user_id( get_current_user_id() );
			$token->save();

		} catch ( Exception $e ) {
			wc_add_notice( __( 'Error while adding your payment method to MONEI. Payment ID: ', 'monei' ) . $payment_id, 'error' );
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
		}
	}

	/**
	 * Verify payment status and complete order if needed (race condition fix).
	 * When user returns from MONEI before IPN webhook processes, we need to complete the order here.
	 *
	 * @param int    $order_id Order ID.
	 * @param object $payment MONEI payment object.
	 * @return void
	 */
	private function verify_and_complete_order( $order_id, $payment ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Check if payment was already processed (prevent duplicate processing)
		$processed_payment_id = $order->get_meta( '_monei_payment_id_processed', true );
		if ( $processed_payment_id === $payment->getId() ) {
			WC_Monei_Logger::log( sprintf( '[MONEI] Payment already processed via IPN [payment_id=%s, order_id=%s]', $payment->getId(), $order_id ), 'debug' );
			return;
		}

		$payment_status = $payment->getStatus();
		$order_status   = $order->get_status();

		WC_Monei_Logger::log( sprintf( '[MONEI] Redirect verification [payment_id=%s, order_id=%s, payment_status=%s, order_status=%s]', $payment->getId(), $order_id, $payment_status, $order_status ), 'debug' );

		// Only process if order is still pending/on-hold and payment succeeded
		if ( ! in_array( $order_status, array( 'pending', 'on-hold' ), true ) ) {
			WC_Monei_Logger::log( sprintf( '[MONEI] Order already processed, skipping [order_id=%s, status=%s]', $order_id, $order_status ), 'debug' );
			return;
		}

		// If payment is SUCCEEDED or AUTHORIZED, complete the order
		if ( 'SUCCEEDED' === $payment_status || 'AUTHORIZED' === $payment_status ) {
			$amount      = $payment->getAmount();
			$order_total = $order->get_total();

			// Verify amounts match (with 1 cent exception for subscriptions)
			if ( ( (int) $amount !== monei_price_format( $order_total ) ) && ( 1 !== $amount ) ) {
				$order->update_status(
					'on-hold',
					sprintf(
						/* translators: 1: Order amount, 2: Payment amount */
						__( 'Validation error: Order vs. Payment amounts do not match (order: %1$s - received: %2$s).', 'monei' ),
						monei_price_format( $order_total ),
						$amount
					)
				);
				WC_Monei_Logger::log( sprintf( '[MONEI] Amount mismatch [order_id=%s, order_amount=%s, payment_amount=%s]', $order_id, monei_price_format( $order_total ), $amount ), 'error' );
				return;
			}

			// Mark payment as processed to prevent duplicate processing by IPN
			$order->update_meta_data( '_monei_payment_id_processed', $payment->getId() );
			$order->update_meta_data( '_payment_order_number_monei', $payment->getId() );
			$order->update_meta_data( '_payment_order_status_monei', $payment_status );
			$order->update_meta_data( '_payment_order_status_code_monei', $payment->getStatusCode() );
			$order->update_meta_data( '_payment_order_status_message_monei', $payment->getStatusMessage() );

			// Store formatted payment method display
			$payment_method_display = $this->paymentMethodFormatter->get_payment_method_display_from_payment( $payment );
			if ( $payment_method_display ) {
				$order->update_meta_data( '_monei_payment_method_display', $payment_method_display );
			}

			if ( 'AUTHORIZED' === $payment_status ) {
				$order->update_meta_data( '_payment_not_captured_monei', 1 );
				$order_note  = __( 'Payment verified via redirect - <strong>Payment Authorized</strong>', 'monei' ) . '. <br><br>';
				$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $payment->getId() . '. <br><br>';
				$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $payment->getStatusMessage();
				$order->add_order_note( $order_note );
				$order->update_status( 'on-hold', __( 'Order On-Hold by MONEI', 'monei' ) );
			} else {
				// SUCCEEDED
				$order_note  = __( 'Payment verified via redirect - <strong>Payment Completed</strong>', 'monei' ) . '. <br><br>';
				$order_note .= __( 'MONEI Transaction id: ', 'monei' ) . $payment->getId() . '. <br><br>';
				$order_note .= __( 'MONEI Status Message: ', 'monei' ) . $payment->getStatusMessage();
				$order->add_order_note( $order_note );
				$order->payment_complete();

				$payment_method_woo_id = $order->get_payment_method();
				if ( 'completed' === monei_get_settings( 'orderdo', $payment_method_woo_id ) ) {
					$order->update_status( 'completed', __( 'Order Completed by MONEI', 'monei' ) );
				}
			}

			$order->save();
			WC_Monei_Logger::log( sprintf( '[MONEI] Order completed via redirect verification [order_id=%s, payment_status=%s]', $order_id, $payment_status ), 'debug' );
		}
	}
}

new WC_Monei_Redirect_Hooks();
