<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class that will be inherited by all integrated components payment methods.
 * Class WC_Monei_Payment_Gateway_Component
 *
 * @extends WC_Monei_Payment_Gateway
 * @since 5.0
 */
abstract class WC_Monei_Payment_Gateway_Component extends WC_Monei_Payment_Gateway {

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @param string $allowed_payment_method
	 * @return array
	 */
	public function process_payment( $order_id, $allowed_payment_method = null ) {

		$order         = new WC_Order( $order_id );
		$amount        = monei_price_format( $order->get_total() );
		$currency      = get_woocommerce_currency();
		$user_email    = $order->get_billing_email();
		$description   = "user_email: $user_email order_id: $order_id";

		/**
		 * The URL to which a payment result should be sent asynchronously.
		 */
		$callback_url = wp_sanitize_redirect( esc_url_raw( $this->notify_url ) );
		/**
		 * The URL the customer will be directed to if s/he decided to cancel the payment and return to your website.
		 */
		$fail_url     = esc_url_raw( $order->get_cancel_order_url_raw() );
		/**
		 * The URL the customer will be directed to after transaction completed (successful or failed).
		 */
		$complete_url = wp_sanitize_redirect( esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) ) );

		/**
		 * Create Payment Payload
		 */
		$payload = [
			'amount'      => $amount,
			'currency'    => $currency,
			'orderId'     => (string) $order_id,
			'description' => $description,
			'customer' => [
				'email' => $user_email,
				'name'  => $order->get_formatted_billing_full_name(),
			],
			'callbackUrl' => $callback_url,
			'completeUrl' => $complete_url,
			'cancelUrl'   => wc_get_checkout_url(),
			'failUrl'     => $fail_url,
			'transactionType' => ( $this->pre_auth ) ? self::PRE_AUTH_TRANSACTION_TYPE : self::SALE_TRANSACTION_TYPE,
			'sessionDetails'  => [
				'ip'        => WC_Geolocation::get_ip_address(),
				'userAgent' => wc_get_user_agent(),
			],
			'allowedPaymentMethods' => [ $allowed_payment_method ],
		];

		// If customer has selected a saved payment method, we get the token from $_POST and we add it to the payload.
		if ( $token_id = $this->get_payment_token_id_if_selected() ) {
			$wc_token = WC_Payment_Tokens::get( $token_id );
			$payload['paymentToken'] = $wc_token->get_token();
		}

		// If customer has checkboxed "Save payment information to my account for future purchases."
		if ( $this->tokenization && $this->get_save_payment_card_checkbox() ) {
			$payload['generatePaymentToken'] = true;
		}

		// If merchant is not using redirect flow (means component CC), there is a generated frontend token paymentToken
		if ( ! $this->redirect_flow && MONEI_GATEWAY_ID === $this->id && $monei_token = $this->get_frontend_generated_monei_token() ) {
			$payload['paymentToken'] = $monei_token;
			$payload['sessionId']    = ( string ) WC()->session->get_customer_id();
		}

		try {
			$payment = WC_Monei_API::create_payment( $payload );
			do_action( 'wc_gateway_monei_process_payment_success', $payload, $payment, $order );

			WC_Monei_Logger::log( 'WC_Monei_API::create_payment ' . $allowed_payment_method, 'debug' );
			WC_Monei_Logger::log( $payload, 'debug' );
			WC_Monei_Logger::log( $payment, 'debug' );
			return array(
				'result'   => 'success',
				'redirect' => $payment->getNextAction()->getRedirectUrl(),
			);
		} catch ( Exception $e ) {
			do_action( 'wc_gateway_monei_process_payment_error', $e, $order );
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
			wc_add_notice( $e->getMessage(), 'error' );
			return;
		}
	}

	/**
	 * Frontend MONEI generated token.
	 *
	 * @return false|string
	 */
	public function get_frontend_generated_monei_token() {
		return ( isset( $_POST['monei_payment_token'] ) ) ? filter_var( $_POST['monei_payment_token'], FILTER_SANITIZE_STRING ) : false; // WPCS: CSRF ok.
	}

    /**
     * Setting checks when saving.
     *
     * @param $is_post
     * @return bool
     */
    public function checks_before_save( $is_post ) {
        if ( $is_post ) {
            if ( empty( $_POST['woocommerce_monei_accountid']) || empty( $_POST['woocommerce_monei_apikey'] ) ) {
                WC_Admin_Settings::add_error( __( 'Please, MONEI needs Account ID and API Key in order to work. Disabling the gateway.', 'monei' ) );
                unset( $_POST['woocommerce_monei_enabled'] );
            }
        }
        return $is_post;
    }

}

