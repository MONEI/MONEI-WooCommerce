<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handle Monei Payment method by default (HOSTED / Form based ) for retro compatibility.
 * Form based: This is where the user must click a button on a form that then redirects them to the payment processor on the gateway’s own website.
 * https://docs.monei.com/docs/integrations/use-prebuilt-payment-page/
 *
 * Class WC_Gateway_Monei
 */
class WC_Gateway_Monei extends WC_Monei_Payment_Gateway {

	const TRANSACTION_TYPE = 'SALE';

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id = MONEI_GATEWAY_ID;
		$this->method_title  = __( 'MONEI - Hosted Version', 'monei' );
		$this->method_description = __( 'Best payment gateway rates. The perfect solution to manage your digital payments.', 'monei' );
		$this->enabled = ( ! empty( $this->get_option( 'enabled' ) && 'yes' === $this->get_option( 'enabled' ) ) && $this->is_valid_for_use() ) ? 'yes' : false;

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		// Hosted payment with redirect.
		$this->has_fields = false;

		// Settings variable
		$this->icon                 = ( ! empty( $this->get_option( 'logo' ) ) ) ? $this->get_option( 'logo' ) : apply_filters( 'woocommerce_monei_icon', WC_Monei()->image_url( 'MONEI-logo.png' ) );
		$this->testmode             = ( ! empty( $this->get_option( 'testmode' ) && 'yes' === $this->get_option( 'testmode' ) ) ) ? true : false;
		$this->title                = ( ! empty( $this->get_option( 'title' ) ) ) ? $this->get_option( 'title' ) : '';
		$this->description          = ( ! empty( $this->get_option( 'description' ) ) ) ? $this->get_option( 'description' ) : '';
		$this->status_after_payment = ( ! empty( $this->get_option( 'orderdo' ) ) ) ? $this->get_option( 'orderdo' ) : '';
		$this->account_id           = ( ! empty( $this->get_option( 'accountid' ) ) ) ? $this->get_option( 'accountid' ) : '';
		$this->api_key              = ( ! empty( $this->get_option( 'apikey' ) ) ) ? $this->get_option( 'apikey' ) : '';
		$this->shop_name            = ( ! empty( $this->get_option( 'commercename' ) ) ) ? $this->get_option( 'commercename' ) : '';
		$this->password             = ( ! empty( $this->get_option( 'password' ) ) ) ? $this->get_option( 'password' ) : '';
		$this->tokenization         = ( ! empty( $this->get_option( 'tokenization' ) && 'yes' === $this->get_option( 'tokenization' ) ) ) ? true : false;
		$this->logging              = ( ! empty( $this->get_option( 'debug' ) ) && 'yes' === $this->get_option( 'debug' ) ) ? true : false;

		// IPN callbacks
		$this->notify_url           = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN();

		$this->supports             = array(
			'products',
			'refunds',
		);

		if ( $this->tokenization ) {
			$this->supports[] = 'tokenization';
		}

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_monei', array( $this, 'receipt_page' ) );

	}

	/**
	 * Admin Panel Options
	 *
	 * @access public
	 * @since 5.0
	 * @return void
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			woocommerce_gateway_monei_get_template( 'notice-admin-gateway-not-available.php' );
		}
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @since 5.0
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = require WC_Monei()->plugin_path() . '/includes/admin/monei-settings.php';
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

	/**
	 * Add payment method in Account add_payment_method_page.
	 *
	 * @return array
	 */
	public function add_payment_method() {

		if ( ! wp_verify_nonce( $_POST['woocommerce-add-payment-method-nonce'], 'woocommerce-add-payment-method' ) ) {
			return array(
				'result'   => 'failure',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}

		// Since it is a hosted version, we need to create a 0 EUR payment and send customer to MONEI.
		try {
			$zero_payload = $this->create_zero_eur_payload();
			$payment = WC_Monei_API::create_payment( $zero_payload );
			WC_Monei_Logger::log( 'WC_Monei_API::add_payment_method', 'debug' );
			WC_Monei_Logger::log( $zero_payload, 'debug' );
			WC_Monei_Logger::log( $payment, 'debug' );
			do_action( 'wc_gateway_monei_add_payment_method_success', $zero_payload, $payment );
			return array(
				'result'   => 'success',
				'redirect' => $payment->getNextAction()->getRedirectUrl(),
			);
		} catch ( Exception $e ) {
			WC_Monei_Logger::log( $e, 'error' );
			wc_add_notice( $e->getMessage(), 'error' );
			return array(
				'result'   => 'failure',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}
	}

	protected function create_zero_eur_payload() {
		$current_user_id = (string) get_current_user_id();
		/**
		 * Create 0 EUR Payment Payload
		 */
		return [
			'amount'      => 0,
			'currency'    => get_woocommerce_currency(),
			'orderId'     => $current_user_id . 'generatetoken' . rand( 0, 1000000 ),
			'description' => "User $current_user_id creating empty transaction to generate token",
			'callbackUrl' => wp_sanitize_redirect( esc_url_raw( $this->notify_url ) ),
			'completeUrl' => wc_get_endpoint_url( 'payment-methods' ),
			'cancelUrl'   => wc_get_endpoint_url( 'payment-methods' ),
			'failUrl'     => wc_get_endpoint_url( 'payment-methods' ),
			'transactionType' => self::TRANSACTION_TYPE,
			'sessionDetails'  => [
				'ip'        => WC_Geolocation::get_ip_address(),
				'userAgent' => wc_get_user_agent(),
			],
			'generatePaymentToken' => true,
		];
	}

	/**
	 * Payments fields, shown on checkout or payment method page (add payment method).
	 */
	function payment_fields() {
		if ( is_add_payment_method_page() ) {
			_e( 'Pay via MONEI: you can add your payment method for future payments.', 'monei' );
		} else {
			// Checkout screen. We show description, if tokenization available, we show saved cards and checkbox to save.
			echo $this->description;
			if ( $this->tokenization ) {
				$this->tokenization_script();
				$this->saved_payment_methods();
				$this->save_payment_method_checkbox();
			}
		}
	}

}

