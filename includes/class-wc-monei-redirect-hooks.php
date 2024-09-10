<?php
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

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_cancelled_order', array( $this, 'add_notice_monei_order_cancelled' ) );
		add_action('template_redirect', [$this, 'add_notice_monei_order_failed']);
		add_action( 'wp', array( $this, 'save_payment_token' ) );
	}

	/**
	 * When MONEI send us back to orderFailed
	 * We need to show message to the customer.
	 *
	 * @param $order_id
	 * @return void
	 */
	public function add_notice_monei_order_failed() {
		if ( !isset( $_GET['status'] )) {
			return;
		}
		$status = wc_clean( $_GET['status'] );
		if ( $status === 'FAILED' ) {
			wc_add_notice(__('The payment failed. Please try again', 'monei'), 'error');
		}
		add_filter('woocommerce_payment_gateway_get_new_payment_method_option_html', '__return_empty_string');
	}

	/**
	 * When MONEI send us back to get_cancel_order_url_raw()
	 * We need to show message to the customer + save it into the order.
	 *
	 * @param $order_id
	 * @return void
	 */
	public function add_notice_monei_order_cancelled( $order_id ) {
		if ( isset( $_GET['status'] ) && isset( $_GET['message'] ) && 'FAILED' === sanitize_text_field( $_GET['status'] ) ) {
			$order_id = absint( $_GET['order_id'] );
			$order    = wc_get_order( $order_id );

			$order->add_order_note( __( 'MONEI Status: ', 'monei' ) . esc_html( sanitize_text_field( $_GET['status'] ) ) );
			$order->add_order_note( __( 'MONEI message: ', 'monei' ) . esc_html( sanitize_text_field( $_GET['message'] ) ) );

			wc_add_notice( esc_html( sanitize_text_field( $_GET['message'] ) ), 'error' );

			WC_Monei_Logger::log( __( 'Order Cancelled: ', 'monei' ) . $order_id );
			WC_Monei_Logger::log( __( 'MONEI Status: ', 'monei' ) . esc_html( sanitize_text_field( $_GET['status'] ) ) );
			WC_Monei_Logger::log( __( 'MONEI message: ', 'monei' ) . esc_html( sanitize_text_field( $_GET['message'] ) ) );
		}
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
	 * todo: refactor and split code for is_add_payment_method_page and is_order_received_page to make it more readable.
	 */
	public function save_payment_token() {
		if ( ! is_add_payment_method_page() && ! is_order_received_page() ) {
			return;
		}

		if ( ! isset( $_GET['id'] ) ) {
			return;
		}

		/**
		 * In the redirect back (from add payment method), the payment could have been failed, the only way to check is the url $_GET['status']
		 * We should remove the "Payment method successfully added." notice and add a 'Unable to add payment method to your account.' manually.
		 */
		if ( is_add_payment_method_page() && ( ! isset( $_GET['status'] ) || 'SUCCEEDED' !== sanitize_text_field( $_GET['status'] ) ) ) {
			wc_clear_notices();
			wc_add_notice( __( 'Unable to add payment method to your account.', 'woocommerce' ), 'error' );
			$error_message = filter_input( INPUT_GET, 'message', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field') );
			if ( $error_message ) {
				wc_add_notice( $error_message, 'error' );
			}
			return;
		}

		$payment_id = filter_input( INPUT_GET, 'id', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field') );
		$order_id   = filter_input( INPUT_GET, 'orderId', FILTER_CALLBACK, array( 'options' => 'sanitize_text_field') );
		try {
			WC_Monei_API::set_order( $order_id );
			$payment       = WC_Monei_API::get_payment( $payment_id );
			$payment_token = $payment->getPaymentToken();

			// A payment can come without token, user didn't check on save payment method.
			// We just ignore it then and do nothing.
			if ( ! $payment_token || empty( $payment_token ) ) {
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

			$expiration = new DateTime( date( 'm/d/Y', $payment_method->getCard()->getExpiration() ) );

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

}

new WC_Monei_Redirect_Hooks();

