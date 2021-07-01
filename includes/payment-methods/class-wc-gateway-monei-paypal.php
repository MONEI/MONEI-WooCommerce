<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handle Monei Paypal Payment method.
 *
 * Class WC_Gateway_Monei_Paypal
 */
class WC_Gateway_Monei_Paypal extends WC_Monei_Payment_Gateway {

	const TRANSACTION_TYPE = 'SALE';
	const PAYMENT_METHOD = 'paypal';

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id = MONEI_GATEWAY_ID . '_paypal';
		$this->method_title  = __( 'MONEI - PayPal', 'monei' );
		$this->method_description = __( 'Best payment gateway rates. The perfect solution to manage your digital payments.', 'monei' );
		$this->enabled = ( ! empty( $this->get_option( 'enabled' ) && 'yes' === $this->get_option( 'enabled' ) ) && $this->is_valid_for_use() ) ? 'yes' : false;

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		// Bizum Hosted payment with redirect.
		$this->has_fields = false;

		// Settings variable
		$this->icon                 = ( ! empty( $this->get_option( 'logo' ) ) ) ? $this->get_option( 'logo' ) : apply_filters( 'woocommerce_monei_icon', WC_Monei()->image_url( 'MONEI-logo.png' ) );
		$this->title                = ( ! empty( $this->get_option( 'title' ) ) ) ? $this->get_option( 'title' ) : '';
		$this->description          = ( ! empty( $this->get_option( 'description' ) ) ) ? $this->get_option( 'description' ) : '';
		$this->status_after_payment = ( ! empty( $this->get_option( 'orderdo' ) ) ) ? $this->get_option( 'orderdo' ) : '';
		$this->api_key              = ( ! empty( $this->get_option( 'apikey' ) ) ) ? $this->get_option( 'apikey' ) : '';
		$this->logging              = ( ! empty( $this->get_option( 'debug' ) ) && 'yes' === $this->get_option( 'debug' ) ) ? true : false;

		// IPN callbacks
		$this->notify_url           = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN();

		$this->supports             = array(
			'products',
			'refunds',
		);

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @since 5.0
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = require WC_Monei()->plugin_path() . '/includes/admin/monei-paypal-settings.php';
	}

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {

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
			'transactionType' => self::TRANSACTION_TYPE,
			'sessionDetails'  => [
				'ip'        => WC_Geolocation::get_ip_address(),
				'userAgent' => wc_get_user_agent(),
			],
			'allowedPaymentMethods' => [ self::PAYMENT_METHOD ],
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

		try {
			$payment = WC_Monei_API::create_payment( $payload );
			WC_Monei_Logger::log( 'WC_Monei_API::create_payment', 'debug' );
			WC_Monei_Logger::log( $payload, 'debug' );
			WC_Monei_Logger::log( $payment, 'debug' );
			do_action( 'wc_gateway_monei_process_payment_success', $payload, $payment, $order );

			return array(
				'result'   => 'success',
				'redirect' => $payment->getNextAction()->getRedirectUrl(),
			);
		} catch ( Exception $e ) {
			WC_Monei_Logger::log( $e->getMessage(), 'error' );
			wc_add_notice( $e->getMessage(), 'error' );
			do_action( 'wc_gateway_monei_process_payment_error', $e, $order );
			return;
		}
	}

}

