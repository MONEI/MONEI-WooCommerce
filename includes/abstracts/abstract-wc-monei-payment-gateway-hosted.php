<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class that will be inherited by all Hosted payment methods.
 * Class WC_Monei_Payment_Gateway_Hosted
 *
 * @extends WC_Monei_Payment_Gateway
 * @since 5.0
 */
abstract class WC_Monei_Payment_Gateway_Hosted extends WC_Monei_Payment_Gateway {

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @param string $allowed_payment_method
	 * @return array
	 */
	public function process_payment( $order_id, $allowed_payment_method = null ) {

		$order       = new WC_Order( $order_id );
		$amount      = monei_price_format( $order->get_total() );
		$currency    = get_woocommerce_currency();
		$user_email  = $order->get_billing_email();
		$description   = $this->shop_name . ' - #' . $order_id;

		/**
		 * The URL to which a payment result should be sent asynchronously.
		 */
		$callback_url = wp_sanitize_redirect( esc_url_raw( $this->notify_url ) );
		/**
		 * The URL the customer will be directed to if the payment failed.
		 */
		$fail_url = esc_url_raw( $order->get_checkout_payment_url(false) );
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
				'phone' => ( $order->get_billing_phone() ) ?: null,
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
			'billingDetails' => [
				'name'  => ( $order->get_formatted_billing_full_name() ) ?: null,
				'email' => ( $order->get_billing_email() ) ?: null,
				'phone' => ( $order->get_billing_phone() ) ?: null,
				'company' => ( $order->get_billing_company() ) ?: null,
				'address' => [
					'country' => ( $order->get_billing_country() ) ?: null,
					'city'    => ( $order->get_billing_city() ) ?: null,
					'line1'   => ( $order->get_billing_address_1() ) ?: null,
					'line2'   => ( $order->get_billing_address_2() ) ?: null,
					'zip'     => ( $order->get_billing_postcode() ) ?? null,
					'state'   => ( $order->get_billing_state() ) ?: null,
				],
			],
			'shippingDetails' => [
				'name'  => ( $order->get_formatted_shipping_full_name() ) ?: null,
				'email' => $user_email,
				'phone' => ( $order->get_shipping_phone() ) ?: null,
				'company' => ( $order->get_shipping_company() ) ?: null,
				'address' => [
					'country' => ( $order->get_shipping_country() ) ?: null,
					'city'    => ( $order->get_shipping_city() ) ?: null,
					'line1'   => ( $order->get_shipping_address_1() ) ?: null,
					'line2'   => ( $order->get_shipping_address_2() ) ?: null,
					'zip'     => ( $order->get_shipping_postcode() ) ?: null,
					'state'   => ( $order->get_shipping_state() ) ?: null,
				],
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
		
		// Filter to enable external changes on payload.
		$payload = apply_filters( 'wc_gateway_monei_create_payload', $payload );

		try {
			// We set the order, so we can use the right api key configuration.
			WC_Monei_API::set_order( $order );
			$payment = WC_Monei_API::create_payment( $payload );

			$this->log( 'WC_Monei_API::create_payment ' . $allowed_payment_method, 'debug' );
			$this->log( $payload, 'debug' );
			$this->log( $payment, 'debug' );
			do_action( 'wc_gateway_monei_process_payment_success', $payload, $payment, $order );

			return array(
				'result'   => 'success',
				'redirect' => $payment->getNextAction()->getRedirectUrl(),
			);
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), 'error' );
			wc_add_notice( $e->getMessage(), 'error' );
			do_action( 'wc_gateway_monei_process_payment_error', $e, $order );
			return;
		}
	}

}

