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
		// todo: remove, we are using logger class.
		$this->logger               = new WC_Logger();

		// IPN callbacks
		$this->notify_url           = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN();

		//todo: what is really supported?
		$this->supports             = array(
			'products',
			//'tokenization',
			//'refunds',
			/**'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'multiple_subscriptions',**/
		);

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
		$callback_url   = wp_sanitize_redirect( esc_url_raw( $this->notify_url ) );
		/**
		 * The URL the customer will be directed to if s/he decided to cancel the payment and return to your website.
		 */
		$fail_url       = esc_url_raw( $order->get_cancel_order_url_raw() );
		/**
		 * The URL the customer will be directed to after transaction completed (successful or failed).
		 */
		$complete_url     = wp_sanitize_redirect( esc_url_raw( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) ) );

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


	function get_monei_args( $order ) {
		global $woocommerce;

		$order_id      = $order->get_id();
		$url_challenge = get_transient( 'monei_url_challenge_' . sanitize_title( $order_id ) );
		$param_md      = get_transient( 'monei_param_md_challenge_' . sanitize_title( $order_id ) );
		$param_pareq   = get_transient( 'monei_param_pareq_challenge_' . sanitize_title( $order_id ) );
		$param_termurl = get_transient( 'monei_param_termurl_challenge_' . sanitize_title( $$order_id ) );

		if ( $url_challenge ) {

			$monei_args = array();

		} else {

			$currency           = get_woocommerce_currency();
			$account_id         = $this->account_id;
			$transaction_id     = str_pad( $order_id, 12, '0', STR_PAD_LEFT );
			$transaction_id1    = wp_rand( 1, 999 ); // lets to create a random number.
			$transaction_id2    = substr_replace( $transaction_id, $transaction_id1, 0, -9 ); // new order number.
			$amount             = $order->get_total();
			$country            = new WC_Countries();
			$shop_country       = $country->get_base_country();
			$shop_name          = $this->shop_name;
			$url_callback       = $this->notify_url;
			$url_cancel         = html_entity_decode( $order->get_cancel_order_url() );
			$url_complete       = utf8_encode( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) );
			$transaction_type   = 'sale';
			$password           = $this->password;
			$test               = $this->testmode;

			$message = 'account_id' . $account_id . 'amount' . $amount . 'currency' . $currency . 'order_id' . $transaction_id2 . 'shop_name' . $shop_name . 'test' . $test . 'transaction_type' . $transaction_type . 'url_callback' . $url_callback . 'url_cancel' . $url_cancel . 'url_complete' . $url_complete;

			$sign = hash_hmac( 'sha256', $message, $password );

			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', 'Generating payment form for order ' . $order->get_order_number() );
				$this->logger->add( 'monei', 'Helping to understand the encrypted code: ' );
				$this->logger->add( 'monei', 'account_id: ' . $account_id );
				$this->logger->add( 'monei', 'amount: ' . $amount );
				$this->logger->add( 'monei', 'currency: ' . $currency );
				$this->logger->add( 'monei', 'order_id: ' . $transaction_id2 );
				$this->logger->add( 'monei', 'shop_name: ' . $shop_name );
				$this->logger->add( 'monei', 'test: ' . $test );
				$this->logger->add( 'monei', 'url_callback: ' . $url_callback );
				$this->logger->add( 'monei', 'url_cancel: ' . $url_cancel );
				$this->logger->add( 'monei', 'url_complete: ' . $url_complete );
				$this->logger->add( 'monei', 'Password: ' . $password );
				$this->logger->add( 'monei', 'Shop country: ' . $shop_country );
				$this->logger->add( 'monei', 'concatenated: ' . $message );
				$this->logger->add( 'monei', 'sign: ' . $sign );
			}
			$monei_args = array(
				'account_id'       => $account_id,
				'amount'           => $amount,
				'currency'         => $currency,
				'order_id'         => $transaction_id2,
				'shop_name'        => $shop_name,
				'test'             => $test,
				'transaction_type' => $transaction_type,
				'url_callback'     => $url_callback,
				'url_cancel'       => $url_cancel,
				'url_complete'     => $url_complete,
				'signature'        => $sign,
			);
		}
		$monei_args = apply_filters( 'woocommerce_monei_args', $monei_args );
		return $monei_args;
	}

	/**
	 * Generate the monei form
	 *
	 * @access public
	 * @param mixed $order_id
	 * @return string
	 */
	function generate_monei_form( $order_id ) {
		global $woocommerce;

		$order       = new WC_Order( $order_id );
		$monei_args  = $this->get_monei_args( $order );
		$form_inputs = '';
		$url_challenge = get_transient( 'monei_url_challenge_' . sanitize_title( $order_id ) );
		if ( $url_challenge ) {
			$monei_adr = $url_challenge;
		} else {
			$monei_adr   = self::LIVE_URL . '?';
		}

		foreach ( $monei_args as $key => $value ) {
			$form_inputs .= '<input type="hidden" name="' . $key . '" value="' . esc_attr( $value ) . '" />';
		}
		wc_enqueue_js(
			'
				$("body").block({
					message: "<img src=\"' . esc_url( apply_filters( 'woocommerce_ajax_loader_url', $woocommerce->plugin_url() . '/assets/images/select2-spinner.gif' ) ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />' . __( 'Thank you for your order. We are now redirecting you to MONEI to make the payment.', 'monei' ) . '",
					overlayCSS:
					{
						background: "#fff",
						opacity: 1.0
					},
					css: {
						padding:		20,
						textAlign:		"center",
						color:			"#555",
						border:			"3px solid #aaa",
						backgroundColor:"#fff",
						cursor:			"wait",
						lineHeight:		"32px"
					}
				});
			jQuery("#submit_monei_payment_form").click();
			'
		);
		return '<form action="' . esc_url( $monei_adr ) . '" method="post" id="monei_payment_form" target="_top">
				' . $form_inputs . '
				<input type="submit" class="button-alt" id="submit_monei_payment_form" value="' . __( 'Pay with Credit Card via MONEI', 'monei' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'monei' ) . '</a>
			</form>';
	}

	function get_monei_users_token() {
		$customer_token = null;
		if ( is_user_logged_in() ) {
			$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), 'monei' );
			foreach ( $tokens as $token ) {
				if ( $token->get_gateway_id() === 'monei' ) {
					$customer_token = $token->get_token();
				}
			}
		}
		return $customer_token;
	}

	function get_users_token_bulk( $user_id ) {
		$customer_token = null;
		$tokens = WC_Payment_Tokens::get_customer_tokens( $user_id, 'monei' );
		foreach ( $tokens as $token ) {
			if ( $token->get_gateway_id() === 'monei' ) {
				$customer_token = $token->get_token();
			}
		}
		return $customer_token;
	}

	protected function order_contains_subscription( $order_id ) {
		if ( ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return false;
		} elseif ( wcs_order_contains_subscription( $order_id ) ) {
			return true;
		} elseif ( wcs_order_contains_resubscribe( $order_id ) ) {
			return true;
		} elseif ( wcs_order_contains_renewal( $order_id ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Output for the order received page.
	 *
	 * @access public
	 * @return void
	 */
	function receipt_page( $order ) {
		echo '<p>' . esc_html__( 'Thank you for your order, please click the button below to pay with Credit Card via MONEI.', 'monei' ) . '</p>';
		echo $this->generate_monei_form( $order );
	}


	function is_paid( $order_id ) {

		$order       = wc_get_order( $order_id );
		$status      = $order->get_status();
		$status_paid = array(
			'pending',
		);
		if ( $status_paid ) {
			foreach ( $status_paid as $spaid ) {
				if ( (string) $status === (string) $spaid ) {
					if ( 'yes' === $this->logging ) {
						$this->logger->add( 'monei', '$status: ' . $status );
						$this->logger->add( 'monei', '$spaid: ' . $spaid );
						$this->logger->add( 'monei', 'Returning false' );
					}
					return false;
				}
				continue;
			}
			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', 'Returning true' );
			}
			return true;
		} else {
			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', 'Returning false' );
			}
			return false;
		}
	}


	/**
	 * get_monei_order function.
	 *
	 * @access public
	 * @param mixed $order_id
	 * @return void
	 */
	function get_monei_order( $order_id ) {
		$order = new WC_Order( $order_id );
		return $order;
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
	/**
	 * Copyright: (C) 2013 - 2021 José Conti
	 */
	public function doing_scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {

		$order_id    = $renewal_order->get_id();
		$order       = $renewal_order;
		$amount      = $amount_to_charge;
		$user_id     = $order->get_user_id();
		$descripcion = $this->product_description( $order );

		$transaction_type = 'sale';
		$currency         = get_woocommerce_currency();
		$currency         = get_woocommerce_currency();
		$account_id       = $this->account_id;
		$transaction_id   = str_pad( $order_id, 12, '0', STR_PAD_LEFT );
		$transaction_id1  = wp_rand( 1, 999 ); // lets to create a random number.
		$transaction_id2  = substr_replace( $transaction_id, $transaction_id1, 0, -9 ); // new order number.
		$shop_name        = $this->shop_name;
		$test             = $this->testmode;
		$url_callback     = $this->notify_url;
		$url_cancel       = html_entity_decode( $order->get_cancel_order_url() );
		$url_complete     = utf8_encode( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) );
		$monei_adr        = self::CHARGE_URL;
		$amount           = $this->amount_format( $order->get_total() );
		$apikey           = $this->api_key;
		$customer_emial   = $order->billing_email;
		$token            = false;
		$token_post_id    = false;

		$token = $this->get_users_token_bulk( $user_id );

		$body             = array(
			'amount'       => $amount,
			'currency'     => $currency,
			'orderId'      => $transaction_id2,
			'description'  => $descripcion,
			'customer'     => array(
				'email' => $customer_emial,
			),
			'callbackUrl'  => $url_callback,
			'completeUrl'  => $url_complete,
			'cancelUrl'    => $url_cancel,
			'paymentToken' => $token,
		);

		if ( 'yes' === $this->logging ) {
			$this->logger->add( 'monei', '$body: ' . print_r( json_decode( $body ), true ) );
		}

		$data_string = json_encode( $body );
		$options     = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => $apikey,
			),
			'body'    => $data_string,
		);
		if ( 'yes' === $this->logging ) {
			$this->logger->add( 'monei', print_r( $options, true ) );
		}
		$monei_adr         = 'https://api.monei.com/v1/payments';
		$response          = wp_remote_post( $monei_adr, $options );
		$response_code     = wp_remote_retrieve_response_code( $response );
		$response_body     = wp_remote_retrieve_body( $response );
		$result            = json_decode( $response_body );
		$urlchallenge      = $result->redirect_url;
		$refultmonei       = $result->result;
		$status            = $result->status;
		$authorizationCode = $result->authorizationCode;
		$id                = $result->id;

		if ( 'yes' === $this->logging ) {
			$this->logger->add( 'monei', '$response_body: ' . print_r( $response, true ) );
			$this->logger->add( 'monei', 'URL: ' . print_r( $result, true ) );
			$this->logger->add( 'monei', '/*************************/' );
			$this->logger->add( 'monei', '     Get URL To redirect     ' );
			$this->logger->add( 'monei', '/*************************/' );
			$this->logger->add( 'monei', '$response_body: ' . $response_body );
			$this->logger->add( 'monei', 'URL: ' . $result->nextAction->redirectUrl );
			$this->logger->add( 'monei', '$status: ' . $status );
			$this->logger->add( 'monei', '$authorizationCode: ' . $authorizationCode );
			$this->logger->add( 'monei', '$id: ' . $id );
		}
	}
	/**
	 * Refund
	 */

	function ask_for_refund( $order_id, $transaction_id, $amount ) {

		//post code to MONEI
		$order2             = get_post_meta( $order_id, '_payment_wc_order_id_monei', true );
		$monei_order_number = $transaction_id;
		$currency_codes     = get_woocommerce_currency();
		$account_id         = $this->account_id;
		$test               = $this->testmode;
		$transaction_type   = 'refund';
		$shop_name          = $this->shop_name;
		$password           = $this->password;
		$country            = new WC_Countries();
		$shop_country       = $country->get_base_country();
		$monei_adr          = self::REFUND_URL;
		$api_apssword       = $this->api_key;

		$amount = $this->amount_format( $amount );

		if ( 'yes' === $this->logging ) {
			$this->logger->add( 'monei', ' ' );
			$this->logger->add( 'monei', '$api_apssword ' . $api_apssword );
			$this->logger->add( 'monei', '$monei_order_number ' . $monei_order_number );
			$this->logger->add( 'monei', '$amount: ' . $amount );
		}

		$monei   = new Monei\MoneiClient( $api_apssword );
		$message = $monei->payments->refund(
			$monei_order_number,
			[
				'amount' => (int) $amount,
				'refundReason' => 'requested_by_customer',
			]
		);
		$json   = json_decode( $message, true );
		$status = $json['status'];

		//$sign = hash_hmac('sha256', $message, $password );

		if ( 'yes' === $this->logging ) {
			$this->logger->add( 'monei', ' ' );
			$this->logger->add( 'monei', '$message ' . $message );
			$this->logger->add( 'monei', '$status ' . $status );
			$this->logger->add( 'monei', __( 'Order Number MONEI : ', 'monei' ) . $monei_order_number );
		}

		if ( 'REFUNDED' === $status || 'PARTIALLY_REFUNDED' === $status ) {
			return true;
		} else {
			return $status;
		}
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		// Do your refund here. Refund $amount for the order with ID $order_id _transaction_id
		set_time_limit( 0 );
		$order              = $this->get_monei_order( $order_id );
		$order2             = get_post_meta( $order_id, '_payment_wc_order_id_monei', true );
		$monei_order_number = get_post_meta( $order_id, '_payment_order_number_monei', true );

		if ( ! $amount ) {
			$order_total_sign  = $order->get_total();
		} else {
			$order_total_sign = $amount;
		}

		if ( ! empty( $order2 ) ) {
			if ( 'yes' === $this->logging ) {
				$this->logger->add( 'monei', ' ' );
				$this->logger->add( 'monei', '/****************************/' );
				$this->logger->add( 'monei', '       Once upon a time       ' );
				$this->logger->add( 'monei', '/****************************/' );
				$this->logger->add( 'monei', ' ' );
				$this->logger->add( 'monei', __( 'check_monei_refund Asking for order #: ', 'monei' ) . $order_id );
			}

			$refund_asked = $this->ask_for_refund( $order_id, $monei_order_number, $order_total_sign );

			if ( $refund_asked ) {
				if ( 'yes' === $this->logging && $result ) {
					$this->logger->add( 'monei', __( 'check_monei_refund = true ', 'monei' ) );
					$this->logger->add( 'monei', ' ' );
					$this->logger->add( 'monei', '/********************************/' );
					$this->logger->add( 'monei', '  Refund complete by MONEI   ' );
					$this->logger->add( 'monei', '/********************************/' );
					$this->logger->add( 'monei', ' ' );
					$this->logger->add( 'monei', '/******************************************/' );
					$this->logger->add( 'monei', '  The final has come, this story has ended  ' );
					$this->logger->add( 'monei', '/******************************************/' );
					$this->logger->add( 'monei', ' ' );
				}
				return true;
			} else {
				if ( is_wp_error( $refund_asked ) ) {
					if ( 'yes' === $this->logging ) {
						$this->logger->add( 'monei', __( 'Refund Failed: ', 'monei' ) . $refund_asked->get_error_message() );
					}
					return new WP_Error( 'error', $refund_asked->get_error_message() );
				}
			}

			if ( is_wp_error( $refund_asked ) ) {
				if ( 'yes' === $this->logging ) {
					$this->logger->add( 'monei', __( 'Refund Failed: ', 'monei' ) . $refund_asked->get_error_message() );
				}
				return new WP_Error( 'error', $refund_asked->get_error_message() );
			}
		} else {
			if ( 'yes' === $this->logging && $result ) {
				$this->logger->add( 'monei', __( 'Refund Failed: No transaction ID', 'monei' ) );
			}
			return new WP_Error( 'monei', __( 'Refund Failed: No transaction ID', 'monei' ) );
		}
	}
}
