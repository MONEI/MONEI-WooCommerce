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
		$amount        = monei_price_format( $order->get_total());
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
			'orderId'     => ( string ) $order_id,
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

	function payment_fields() {

		if ( is_user_logged_in() && 'yes' === $this->tokenization ) {
			$user_id = get_current_user_id();
			$tokens = WC_Payment_Tokens::get_customer_tokens( $user_id, $this->id );
			if ( ! empty( $tokens ) ) {
				echo '<h4>Select a Credit Card</h4>';
				echo '<div class="credit-cards-monei">';
				foreach ( $tokens as $token ) {
					$is_default = $token->is_default();
					if ( $is_default ) {
						$checked = 'checked="checked"';
					} else {
						$checked = '';
					}
					echo '<div class="moneicreditcards">';
					echo '<input id="' . $token->get_id() . '" name="moneitoken" type="radio" ' . $checked . ' value="' . $token->get_id() . '"/>';
					echo '<label for="' . $token->get_id() . '">' . $token->get_card_type() . ' ended in ' . $token->get_last4() . ' ' . $token->get_expiry_month() . '/' . $token->get_expiry_year() . '</label>';
					echo '</div>';
					continue;
				}
				echo '<div class="moneicreditcards">';
				echo '<input id="yes" name="moneitoken" type="radio" value="yes"/>';
				echo '<label for="yes">Add new Credit Card</label>';
				echo '</div>';
				echo '<div class="moneicreditcards">';
				echo '<input id="no" name="moneitoken" type="radio" value="no"/>';
				echo '<label for="no">Do not use any Credit Card</label>';
				echo '</div>';
				echo '</div>';
			} else {
				echo '<div class="credit-cards-monei">
							<h4>Do we save your credit card?</h4>
							<p>We won\'t keep your card, we\'ll keep a token that MONEI will provide. It\'s totally safe.</p>
							<div class="moneicreditcards">
							<input id="yes" name="moneitoken" type="radio" value="yes"/>
							<label for="yes">Yes</label>
						</div>
						<div class="moneicreditcards">
							<input id="no" name="moneitoken" type="radio" value="no"/>
							<label for="no">No</label>
						</div>
					</div>';
			}
		} else {
			echo $this->description;
		}
	}

}
