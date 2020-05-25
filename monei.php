<?php

/**
 * WooCommerce MONEI Gateway
 *
 * @package WooCommerce MONEI Gateway
 * @author José Conti
 * @copyright 2020-2020 MONEI Conti
 * @license GPL-3.0+
 *
 * Plugin Name: WooCommerce MONEI Gateway
 * Plugin URI: https://wordpress.org/plugins/monei/
 * Description: Extends WooCommerce with a MONEI gateway. Best payment gateway rates. The perfect solution to manage your digital payments.
 * Version: 3.0.0
 * Author: MONEI
 * Author URI: https://www.monei.net/
 * Tested up to: 5.4
 * WC requires at least: 3.0
 * WC tested up to: 4.2
 * Text Domain: monei
 * Domain Path: /languages/
 * Copyright: (C) 2017 MONEI.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'MONEI_VERSION', '3.0.0' );
define( 'MONEI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MONEI_SIGNUP', 'https://dashboard.monei.net/?action=signUp' );
define( 'MONEI_WEB', 'https://monei.net/' );
define( 'MONEI_REVIEW', 'https://wordpress.org/support/plugin/monei/reviews/?rate=5#new-post' );
define( 'MONEI_SUPPORT', 'https://support.monei.net/' );

add_action( 'plugins_loaded', 'woocommerce_gateway_monei_init', 0 );


function woocommerce_gateway_monei_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}
	/**
	 * Localisation
	 */
	load_plugin_textdomain( 'monei', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	/**
	 * Gateway class
	 */
	class WC_Gateway_Monei extends WC_Payment_Gateway {
		var $notify_url;
		/**
		 * Constructor for the gateway.
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			global $woocommerce;
			$this->id = 'monei';
			$logo_url = $this->get_option( 'logo' );
			if ( ! empty( $logo_url ) ) {
				$logo_url   = $this->get_option( 'logo' );
				$this->icon = apply_filters( 'woocommerce_monei_icon', $logo_url );
			} else {
				$this->icon = apply_filters( 'woocommerce_monei_icon', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/assets/images/MONEI-logo.png' );
			}
			$this->has_fields           = true;
			$this->liveurl              = 'https://pay.monei.net/checkout';
			$this->refund_url           = 'https://api.monei.net/v1/refund';
			$this->charge_url           = 'https://api.monei.net/v1/charge';
			$this->testmode             = $this->get_option( 'testmode' );
			$this->method_title         = __( 'MONEI', 'monei' );
			$this->notify_url           = add_query_arg( 'wc-api', 'WC_Gateway_monei', home_url( '/' ) );
			$this->notify_url_not_https = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_monei', home_url( '/' ) ) );
			// Define user set variables.
			$this->title                = $this->get_option( 'title' );
			$this->description          = $this->get_option( 'description' );
			$this->logo                 = $this->get_option( 'logo' );
			$this->orderdo              = $this->get_option( 'orderdo' );
			$this->accountid            = $this->get_option( 'accountid' );
			$this->commercename         = $this->get_option( 'commercename' );
			$this->secret               = $this->get_option( 'secret' );
			$this->password             = $this->get_option( 'password' );
			$this->redorcc              = $this->get_option( 'redorcc' );
			$this->debug                = $this->get_option( 'debug' );
			$this->log                  = new WC_Logger();
			if ( 'onsite' === $this->redorcc ) {
				$this->supports = array(
					'products',
					'refunds',
					'default_credit_card_form',
				);
			} else {
				$this->supports = array(
					'products',
					'refunds',
				);
			}
			$this->init_form_fields();
			$this->init_settings();
			// Actions.
			add_action( 'valid_monei_standard_ipn_request', array( $this, 'successful_request' ) );
			add_action( 'woocommerce_receipt_monei', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// Payment listener/API hook.
			add_action( 'woocommerce_api_wc_gateway_' . $this->id, array( $this, 'check_ipn_response' ) );
			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = false;
			}
		}

		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @access public
		 * @return bool
		 */
	function is_valid_for_use() {
		
		if ( ! in_array( get_woocommerce_currency(), array( 'EUR', 'USD', 'GBP' ), true ) ) {
			return false;
		} else {
			return true;
		}
	}
		/**
		 * Admin Panel Options
		 *
		 * @since 1.0.0
		 */
		public function admin_options() {
			?>
			<h3><?php esc_html_e( 'MONEI', 'monei' ); ?></h3>
			<p><?php esc_html_e( 'Best payment gateway rates. The perfect solution to manage your digital payments.', 'monei' ); ?></p>
			<?php if ( $this->is_valid_for_use() ) : ?>
				<table class="form-table">
				<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
				?>
				</table><!--/.form-table-->
			<?php else : ?>
				<div class="inline error"><p><strong><?php esc_html_e( 'Gateway Disabled', 'monei' ); ?></strong>: <?php esc_html_e( 'MONEI only support EUROS, USD & GBP currencies.', 'monei' ); ?></p></div>
				<?php
			endif;
		}
		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields() {
			$this->form_fields = array(
				'enabled'        => array(
					'title'   => __( 'Enable/Disable', 'monei' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable MONEI', 'monei' ),
					'default' => 'no',
				),
				'title'          => array(
					'title'       => __( 'Title', 'monei' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'monei' ),
					'default'     => __( 'MONEI', 'monei' ),
					'desc_tip'    => true,
				),
				'description'    => array(
					'title'       => __( 'Description', 'monei' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'monei' ),
					'default'     => __( 'Pay via MONEI; you can pay with your credit card.', 'monei' ),
				),
				'logo'           => array(
					'title'       => __( 'Logo', 'monei' ),
					'type'        => 'text',
					'description' => __( 'Add link to image logo.', 'monei' ),
					'desc_tip'    => true,
				),
				'commercename'   => array(
					'title'       => __( 'Shop Name', 'monei' ),
					'type'        => 'text',
					'description' => __( 'Shop Name', 'monei' ),
					'desc_tip'    => true,
				),
				'accountid'       => array(
					'title'       => __( 'Account ID', 'monei' ),
					'type'        => 'text',
					'description' => __( 'Account ID', 'monei' ),
					'desc_tip'    => true,
				),
				'password'   => array(
					'title'       => __( 'Password', 'monei' ),
					'type'        => 'text',
					'description' => __( 'MONEI Password', 'monei' ),
					'desc_tip'    => true,
				),
				'redorcc'     => array(
					'title'       => __( 'Select Type', 'monei' ),
					'type'        => 'select',
					'description' => __( 'Select the type of payment you want your customers do.', 'monei' ),
					'default'     => 'offsite',
					'options'     => array(
						'redirection' => __( 'Payment Off-site (redirection to MONEI)', 'monei' ),
						'onsite'  => __( 'Payment On-site (Credit Card in Checkout)', 'monei' ),
					),
				),
				'orderdo'     => array(
					'title'       => __( 'What to do after payment?', 'monei' ),
					'type'        => 'select',
					'description' => __( 'Chose what to do after the customer pay the order.', 'monei' ),
					'default'     => 'processing',
					'options'     => array(
						'processing' => __( 'Mark as Processing (default & recomended)', 'monei' ),
						'completed'  => __( 'Mark as Complete', 'monei' ),
					),
				),
				'testmode'       => array(
					'title'       => __( 'Running in test mode', 'monei' ),
					'type'        => 'checkbox',
					'label'       => __( 'Running in test mode', 'monei' ),
					'default'     => 'yes',
					'description' => sprintf( __( 'Select this option for the initial testing required by MONEI, deselect this option once you pass the required test phase and your production environment is active.', 'monei' ) ),
				),
				'debug'          => array(
					'title'       => __( 'Debug Log', 'monei' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable logging', 'monei' ),
					'default'     => 'no',
					'description' => __( 'Log MONEY events, such as notifications requests, inside <code>WooCommerce > Status > Logs > Select MONEI Logs</code>', 'monei' ),
				),
			);
		}
		
		function test_mode() {
			if ( 'yes' === $this->testmode ) {
				$test = 'true';
			} else {
				$test = 'false';
			}
			return $test;
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
				$account_id         = $this->accountid;
				$transaction_id     = str_pad( $order_id, 12, '0', STR_PAD_LEFT );
				$transaction_id1    = wp_rand( 1, 999 ); // lets to create a random number.
				$transaction_id2    = substr_replace( $transaction_id, $transaction_id1, 0, -9 ); // new order number.
				$amount             = $order->get_total();
				$country            = new WC_Countries();
				$shop_country       = $country->get_base_country();
				$shop_name          = $this->commercename;
				$url_callback       = $this->notify_url;
				$url_cancel         = html_entity_decode( $order->get_cancel_order_url() );
				$url_complete       = utf8_encode( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) );
				$transaction_type   = 'sale';
				$password           = $this->password;
				$test               = $this->test_mode();
				
				$message = 'account_id' . $account_id . 'amount' . $amount . 'currency' . $currency . 'order_id' . $transaction_id2 . 'shop_name' . $shop_name . 'test' . $test . 'transaction_type' . $transaction_type . 'url_callback' . $url_callback . 'url_cancel' . $url_cancel . 'url_complete' . $url_complete;
				
				$sign = hash_hmac('sha256', $message, $password );
				
				if ( 'yes' === $this->debug ) {
					$this->log->add( 'monei', 'Generating payment form for order ' . $order->get_order_number() );
					$this->log->add( 'monei', 'Helping to understand the encrypted code: ' );
					$this->log->add( 'monei', 'account_id: ' . $account_id );
					$this->log->add( 'monei', 'amount: ' . $amount );
					$this->log->add( 'monei', 'currency: ' . $currency );
					$this->log->add( 'monei', 'order_id: ' . $transaction_id2 );
					$this->log->add( 'monei', 'shop_name: ' . $shop_name );
					$this->log->add( 'monei', 'test: ' . $test );
					$this->log->add( 'monei', 'url_callback: ' . $url_callback );
					$this->log->add( 'monei', 'url_cancel: ' . $url_cancel );
					$this->log->add( 'monei', 'url_complete: ' . $url_complete );
					$this->log->add( 'monei', 'Password: ' . $password );
					$this->log->add( 'monei', 'Shop country: ' . $shop_country );
					$this->log->add( 'monei', 'concatenated: ' . $message );
					$this->log->add( 'monei', 'sign: ' . $sign );
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
					$monei_adr   = $this->liveurl . '?';
				}
				
				foreach ( $monei_args as $key => $value ) {
					$form_inputs .= '<input type="hidden" name="' . $key . '" value="' . esc_attr( $value ) . '" />';
				}
				wc_enqueue_js( '
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
			' );
				return '<form action="' . esc_url( $monei_adr ) . '" method="post" id="monei_payment_form" target="_top">
				' . $form_inputs . '
				<input type="submit" class="button-alt" id="submit_monei_payment_form" value="' . __( 'Pay with Credit Card via MONEI', 'monei' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'monei' ) . '</a>
			</form>';
		}
		
		function monei_send_cc( $order, $card_num, $card_code, $exp_date ) {
			
			$card_num         = str_replace( array(' ', '-' ), '', $_POST['monei-card-number'] );
			$card_code        = ( isset( $_POST['monei-card-cvc'] ) ) ? $_POST['monei-card-cvc'] : '';
			$exp_date         = str_replace( array( '/', ' '), '', $_POST['monei-card-expiry'] );
			$order_id         = $order->get_id();
			$currency         = get_woocommerce_currency();
			$account_id       = $this->accountid;
			$transaction_id   = str_pad( $order_id, 12, '0', STR_PAD_LEFT );
			$transaction_id1  = wp_rand( 1, 999 ); // lets to create a random number.
			$transaction_id2  = substr_replace( $transaction_id, $transaction_id1, 0, -9 ); // new order number.
			$amount           = $order->get_total();
			$country          = new WC_Countries();
			$shop_country     = $country->get_base_country();
			$shop_name        = $this->commercename;
			$url_callback     = $this->notify_url;
			$url_cancel       = html_entity_decode( $order->get_cancel_order_url() );
			$url_complete     = utf8_encode( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) );
			$transaction_type = 'sale';
			$password         = $this->password;
			$test             = $this->test_mode();
			$monei_url        = $this->charge_url;
			$month            = substr( $exp_date, 0, 2);
			$year             = substr($exp_date, 2, 2);
			$monei_adr        = $this->charge_url;
			$userip           = WC_Geolocation::get_ip_address();
			$useragent        = wc_get_user_agent();
			
			$message = 'account_id' . $account_id . 'amount' . $amount . 'currency' . $currency . 'order_id' . $transaction_id2 . 'payment_card_cvc' . $card_code .  'payment_card_exp_month' . $month . 'payment_card_exp_year' . $year . 'payment_card_number' . $card_num . 'shop_name' . $shop_name . 'test' . $test . 'transaction_type' . $transaction_type . 'url_callback' . $url_callback . 'url_cancel' . $url_cancel . 'url_complete' . $url_complete;
			
			$sign = hash_hmac('sha256', $message, $password );
			
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', 'account_id: ' . $account_id );
				$this->log->add( 'monei', 'amount: ' . $amount );
				$this->log->add( 'monei', 'currency: ' . $currency );
				$this->log->add( 'monei', 'order_id: ' . $transaction_id2 );
				$this->log->add( 'monei', 'shop_name: ' . $shop_name );
				$this->log->add( 'monei', 'test: ' . $test );
				$this->log->add( 'monei', 'url_callback: ' . $url_callback );
				$this->log->add( 'monei', 'url_cancel: ' . $url_cancel );
				$this->log->add( 'monei', 'url_complete: ' . $url_complete );
				$this->log->add( 'monei', 'Password: ' . $password );
				$this->log->add( 'monei', 'Shop country: ' . $shop_country );
				$this->log->add( 'monei', 'concatenated: ' . $message );
				$this->log->add( 'monei', 'sign: ' . $sign );
				$this->log->add( 'monei', '$message: ' . $message );
			}

			$body = array(
				'charge' => array(
					'account_id'             => $account_id,
					'amount'                 => $amount,
					'currency'               => $currency,
					'order_id'               => $transaction_id2,
					'payment_card_cvc'       => $card_code,
					'payment_card_exp_month' => $month,
					'payment_card_exp_year'  => $year,
					'payment_card_number'    => $card_num,
					'shop_name'              => $shop_name,
					'signature'              => $sign,
					'test'                   => $test,
					'transaction_type'       => $transaction_type,
					'url_callback'           => $url_callback,
					'url_cancel'             => $url_cancel,
					'url_complete'           => $url_complete,
				),
				'context' => array(
					'ip'        => $userip,
					'userAgent' => $useragent,
				),
			);
			
			$data_string = json_encode( $body );
 
			$options = array(
				'headers' => array(
					'Content-Type' => 'application/json',
					),
				'body' => $data_string,
				);
			
			$response      = wp_remote_post( $monei_adr, $options );
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			$result       = json_decode( $response_body );
			$urlchallenge = $result->redirect_url;

			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', '$response_body: ' . $response_body );
				$this->log->add( 'monei', '$urlchallenge: ' . $urlchallenge );
			}
			if ( ! $urlchallenge ) {
				return 'yes';
			} else {
				set_transient( 'monei_url_challenge_' . sanitize_title( $order_id ), $urlchallenge, 600 );
				return 'challenge';
			}
		}
		
		/**
		 * Process the payment and return the result
		 *
		 * @access public
		 * @param int $order_id
		 * @return array
		 */
		function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );
			
			if ( 'onsite' === $this->redorcc ) {
				if ( isset( $_POST['monei-card-number'] ) && ! empty( $_POST['monei-card-number'] ) ) {
					$card_num  = str_replace( array(' ', '-' ), '', $_POST['monei-card-number'] );
				} else {
					wc_add_notice( 'Fill the Credit Card Number', 'error' );
					
				}
				if ( isset( $_POST['monei-card-cvc'] ) && ! empty( $_POST['monei-card-cvc'] ) ) {
					$card_code = ( isset( $_POST['monei-card-cvc'] ) ) ? $_POST['monei-card-cvc'] : '';
				} else {
					wc_add_notice( 'Fill the CVC Credit Card', 'error' );
				}
				if ( isset( $_POST['monei-card-expiry'] ) && ! empty( $_POST['monei-card-expiry'] ) ) {
					$exp_date  = str_replace( array( '/', ' '), '', $_POST['monei-card-expiry'] );
				} else {
					wc_add_notice( 'Fill the Credit Card Expiration Date', 'error' );
				}
				
				if ( $card_num && $card_code && $exp_date ) {
				
					/*
					if ( 'yes' === $this->debug ) {
						$this->log->add( 'monei', '$card_num: ' . $card_num );
						$this->log->add( 'monei', '$card_code: ' . $card_code );
						$this->log->add( 'monei', '$exp_date: ' . $exp_date );
					}
					*/
					
					$response = $this->monei_send_cc( $order, $card_num, $card_code, $exp_date );
					
					if ( 'yes' === $response ) {
						$order->payment_complete();
						if ( 'yes' === $this->debug ) {
							$this->log->add( 'monei', 'Redirecting to: ' . utf8_encode( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) ) );
						}
						return array(
							'result'   => 'success',
							'redirect' => utf8_encode( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) )
						);
					} elseif ( 'challenge' === $response ) {
						$url_challenge = get_transient( 'monei_url_challenge_' . sanitize_title( $order_id ) );
						return array(
							'result'   => 'success',
							'redirect' => $url_challenge,
						);
					}
				}
			} else {
				return array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url( true ),
				);
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
		/**
		 * Check monei IPN validity
		 **/
		function check_ipn_request_is_valid() {
			
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', 'checking notification' );
			}
			
			if ( isset( $_POST['signature'] ) ) {
				$signature = $_POST['signature'];
				$posted    = array();
				foreach ( $_POST as $key => $value ) {
					if ( 'signature' !== $key ) {
						$posted[$key] = $value;
					}
					continue;
				}
				ksort( $posted );
				
				foreach ( $posted as $key => $value ) {
						$content .= $key . $value;
				}
				$password = $this->password;
				
				if ( ! empty( $content ) &&  ! empty( $password ) && ! empty( $signature ) ) {
					$sign = hash_hmac('sha256', $content, $password );
					if ( 'yes' === $this->debug ) {
						$this->log->add( 'monei', 'data: ' .  $content );
						$this->log->add( 'monei', 'signature form MONEI: ' .  $signature );
						$this->log->add( 'monei', 'signature at Plugin: ' .  $sign );
					}
					if ( $sign !== $signature ) {
						if ( 'yes' === $this->debug ) {
							$this->log->add( 'monei', 'Received INVALID notification from MONEI' );
						}
						return false;
					} else {
						if ( 'yes' === $this->debug ) {
							$this->log->add( 'monei', 'Correct signature' );
						}
						$amountmonei = $_POST['amount'];
						$order_id    = $_POST['order_id'];
						$order2      = substr( $order_id, 3 ); //cojo los 9 digitos del final
						$order       = new WC_Order( $order2 );
						$amountwoo   = $order->get_total();
						
						if ( $amountmonei === $amountwoo ) {
							if ( 'yes' === $this->debug ) {
								$this->log->add( 'monei', 'The amount match' );
							}
							return true;
						} else {
							if ( 'yes' === $this->debug ) {
								$this->log->add( 'monei', 'The amount does not match' );
							}
							return false;
						}
					}
				} else {
					if ( 'yes' === $this->debug ) {
						$this->log->add( 'monei', 'Received INVALID notification from MONEI' );
					}
					return false;
				}
			} else {
				if ( 'yes' === $this->debug ) {
					$this->log->add( 'monei', 'Received INVALID notification from MONEI' );
				}
				return false;
			}
		}
		/**
		 * Check for Monei HTTP Notification
		 *
		 * @access public
		 * @return void
		 */
		function check_ipn_response() {
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', '/****************************/' );
				$this->log->add( 'monei', '      check_ipn_response      ' );
				$this->log->add( 'monei', '/****************************/' );
				$this->log->add( 'monei', ' ' );
			}
			@ob_clean();
			$_POST = stripslashes_deep( $_POST );
			
			if ( $this->check_ipn_request_is_valid() ) {
				header( 'HTTP/1.1 200 OK' );
				do_action( 'valid_monei_standard_ipn_request', $_POST );
			} else {
				wp_die( 'MONEI Notification Request Failure' );
			}
		}
		/**
		 * Successful Payment!
		 *
		 * @access public
		 * @param array $posted
		 * @return void
		 */
		function successful_request( $posted ) {
			global $woocommerce;
			
			$account_id       = sanitize_text_field( $_POST['account_id'] );
			$amount           = sanitize_text_field( $_POST['amount'] );
			$currency         = sanitize_text_field( $_POST['currency'] );
			$monei_order_id   = sanitize_text_field( $_POST['monei_order_id'] );
			$order_id         = sanitize_text_field( $_POST['order_id'] );
			$result           = sanitize_text_field( $_POST['result'] ); // completed, failed and pending
			$signature        = sanitize_text_field( $_POST['signature'] );
			$test             = sanitize_text_field( $_POST['test'] );
			$timestamp        = sanitize_text_field( $_POST['timestamp'] );
			$message          = sanitize_text_field( $_POST['message'] );
			$transaction_type = sanitize_text_field( $_POST['transaction_type'] ); //sale, authorization, capture, refund and void
			$order2           = substr( $order_id, 3 ); // cojo los 9 digitos del final.
			$order            = $this->get_monei_order( (int) $order2 );
			
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', '$account_id: ' . $account_id );
				$this->log->add( 'monei', '$amount: ' . $amount );
				$this->log->add( 'monei', '$currency: ' . $currency );
				$this->log->add( 'monei', '$monei_order_id: ' . $monei_order_id );
				$this->log->add( 'monei', '$order_id: ' . $order_id );
				$this->log->add( 'monei', '$result: ' . $result );
				$this->log->add( 'monei', '$signature: ' . $signature );
				$this->log->add( 'monei', '$test: ' . $test );
				$this->log->add( 'monei', '$timestamp: ' . $timestamp );
				$this->log->add( 'monei', '$message: ' . $message );
				$this->log->add( 'monei', '$transaction_type: ' . $transaction_type );
			}

			if ( 'Transaction Approved' === $message ) {
				// authorized.
				$order2    = substr( $order_id, 3 ); //cojo los 9 digitos del final
				$order     = new WC_Order( $order2 );
				$amountwoo = $order->get_total();

				if ( $amountwoo !== $amount ) {
					// amount does not match.
					if ( 'yes' === $this->debug ) {
						$this->log->add( 'monei', 'Payment error: Amounts do not match (order: ' . $amountwoo . ' - received: ' . $amount . ')' );
					}
					// Put this order on-hold for manual checking.
					/* translators: order an received are the amount */
					$order->update_status( 'on-hold', sprintf( __( 'Validation error: Order vs. Notification amounts do not match (order: %1$s - received: %2&s).', 'monei' ), $amountwoo, $amount ) );
					exit;
				}

				$parts = explode( 'T', $timestamp );
				$part2 = explode( '.', $parts[1] );
				$date  = $parts[0];
				$hour  = $part2[0];
				
				if ( 'yes' === $this->debug ) {
					$this->log->add( 'monei', 'Timestamp: ' . $timestamp );
					$this->log->add( 'monei', 'Date: ' . $date );
					$this->log->add( 'monei', 'Hour: ' . $hour );
				}
				
				if ( ! empty( $monei_order_id ) ) {
					update_post_meta( $order->get_id(), '_payment_order_number_monei', $monei_order_id );
				}
				
				if ( ! empty( $date ) ) {
					update_post_meta( $order->get_id(), '_payment_date_monei', $date );
				}
				
				if ( ! empty( $hour ) ) {
					update_post_meta( $order->get_id(), '_payment_hour_monei', $hour );
				}
				
				if ( ! empty( $order_id ) ) {
					update_post_meta( $order->get_id(), '_payment_wc_order_id_monei', $order_id );
				}

				// Payment completed.
				$order->add_order_note( __( 'HTTP Notification received - payment completed', 'monei' ) );
				$order->add_order_note( __( 'MONEI Order Number: ', 'monei' ) . $monei_order_id );
				$order->payment_complete();
				if ( 'completed' === $this->orderdo ) {
					$order->update_status( 'completed', __( 'Order Completed by MONEI', 'monei' ) );
				}

				if ( 'yes' === $this->debug ) {
					$this->log->add( 'monei', 'Payment complete.' );
				}
			} else {
				// Tarjeta caducada.
				if ( 'yes' === $this->debug ) {
					$this->log->add( 'monei', 'Order cancelled by MONEI: ' . $message );
				}
				// Order cancelled.
				$order->update_status( 'cancelled', 'Cancelled by MONEI: ' . $message);
				$order->add_order_note( 'Order cancelled by MONEI: ' . $message );
				WC()->cart->empty_cart();
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
		
		/**
		* Refund
		**/
		
		function ask_for_refund( $order_id, $transaction_id, $amount ) {

			//post code to MONEI
			$order2             = get_post_meta( $order_id, '_payment_wc_order_id_monei', true );
			$monei_order_number = $transaction_id;
			$currency_codes     = get_woocommerce_currency();
			$account_id         = $this->accountid;
			$test               = $this->test_mode();
			$transaction_type   = 'refund';
			$shop_name          = $this->commercename;
			$password           = $this->password;
			$country            = new WC_Countries();
			$shop_country       = $country->get_base_country();
			$monei_adr          = $this->refund_url;
			
			$message = 'account_id' . $account_id .
			'amount' . $amount .
			'currency' . $currency_codes .
			'monei_order_id' . $monei_order_number .
			'order_id' . $order2 .
			'shop_name' . $shop_name .
			'test' . $test .
			'transaction_type' . $transaction_type;
			
			$sign = hash_hmac('sha256', $message, $password );
			
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', '$message ' . $message );
				$this->log->add( 'monei', '$password ' . $password );
				$this->log->add( 'monei', '$sign: ' . $sign );
				$this->log->add( 'monei', __( 'Order Number MONEI : ', 'monei' ) . $monei_order_number );
			}
			
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', '/**************************/' );
				$this->log->add( 'monei', __( 'Starting asking for Refund', 'monei' ) );
				$this->log->add( 'monei', '/**************************/' );
				$this->log->add( 'monei', ' ' );
			}
	
			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', __( 'All data from meta', 'monei' ) );
				$this->log->add( 'monei', '**********************' );
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', __( 'If something is empty, the data was not saved', 'monei' ) );
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', __( 'All data from meta', 'monei' ) );
				$this->log->add( 'monei', __( 'Order Number MONEI : ', 'monei' ) . $monei_order_number );
			}

			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', __( 'Data sent to MONEI for refund', 'monei' ) );
				$this->log->add( 'monei', '*********************************' );
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', __( 'URL to MONEI : ', 'monei' ) . $monei_adr );
				$this->log->add( 'monei', __( 'account_id : ', 'monei' ) . $account_id );
				$this->log->add( 'monei', __( 'amount : ', 'monei' ) . $amount );
				$this->log->add( 'monei', __( 'currency : ', 'monei' ) . $currency_codes );
				$this->log->add( 'monei', __( 'order_id : ', 'monei' ) . $order2 );
				$this->log->add( 'monei', __( 'monei_order_id : ', 'monei' ) . $monei_order_number );
				$this->log->add( 'monei', __( 'signature : ', 'monei' ) . $sign );
				$this->log->add( 'monei', __( 'test : ', 'monei' ) . $test );
				$this->log->add( 'monei', __( 'transaction_type : refund', 'monei' ) );
				$this->log->add( 'monei', __( 'ask_for_refund Asking for order #: ', 'monei' ) . $order_id );
				$this->log->add( 'monei', ' ' );
			}
			
			$body = array(
				'account_id'       => $account_id,
				'amount'           => $amount,
				'currency'         => $currency_codes,
				'monei_order_id'   => $monei_order_number,
				'order_id'         => $order2,
				'shop_name'        => $shop_name,
				'signature'        => $sign,
				'test'             => $test,
				'transaction_type' => 'refund',
			);
				
			$data_string = json_encode( $body );
 
			$options = array(
				'headers' => array(
					'Content-Type' => 'application/json',
					),
				'body' => $data_string,
				);
			
			$response      = wp_remote_post( $monei_adr, $options );
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			if ( is_wp_error( $response ) ) {
				$error_string = $response->get_error_message();
				if ( 'yes' === $this->debug ) {
					$this->log->add( 'monei', ' ' );
					$this->log->add( 'monei', __( 'There is an error', 'monei' ) );
					$this->log->add( 'monei', '*********************************' );
					$this->log->add( 'monei', ' ' );
					$this->log->add( 'monei', __( 'The error is : ', 'monei' ) . $error_string );
				}
				return $error_string;
			}
			
			$result = json_decode( $response_body );
			$end    = $result->result;

			if ( 'yes' === $this->debug ) {
				$this->log->add( 'monei', ' ' );
				$this->log->add( 'monei', '$result: ' . $end );
				$this->log->add( 'monei', ' ' );
			}
			
			if ( 'completed' === $end ) {
				return true;
			} else {
				return $end;
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
				if ( 'yes' === $this->debug ) {
					$this->log->add( 'monei', ' ' );
					$this->log->add( 'monei', '/****************************/' );
					$this->log->add( 'monei', '       Once upon a time       ' );
					$this->log->add( 'monei', '/****************************/' );
					$this->log->add( 'monei', ' ' );
					$this->log->add( 'monei', __( 'check_monei_refund Asking for order #: ', 'monei' ) . $order_id );
				}
	
				$refund_asked = $this->ask_for_refund( $order_id, $monei_order_number, $order_total_sign );
				
				if ( $refund_asked ) {
					if ( 'yes' === $this->debug && $result ) {
						$this->log->add( 'monei', __( 'check_monei_refund = true ', 'monei' ) );
						$this->log->add( 'monei', ' ' );
						$this->log->add( 'monei', '/********************************/' );
						$this->log->add( 'monei', '  Refund complete by MONEI   ' );
						$this->log->add( 'monei', '/********************************/' );
						$this->log->add( 'monei', ' ' );
						$this->log->add( 'monei', '/******************************************/' );
						$this->log->add( 'monei', '  The final has come, this story has ended  ' );
						$this->log->add( 'monei', '/******************************************/' );
						$this->log->add( 'monei', ' ' );
					}
					return true;
				} else {
					if ( is_wp_error( $refund_asked ) ) {
						if ( 'yes' === $this->debug ) {
							$this->log->add( 'redsys', __( 'Refund Failed: ', 'monei' ) . $refund_asked->get_error_message() );
						}
						return new WP_Error( 'error', $refund_asked->get_error_message() );
					}
				}
	
				if ( is_wp_error( $refund_asked ) ) {
					if ( 'yes' === $this->debug ) {
						$this->log->add( 'redsys', __( 'Refund Failed: ', 'monei' ) . $refund_asked->get_error_message() );
					}
					return new WP_Error( 'error', $refund_asked->get_error_message() );
				}
			} else {
				if ( 'yes' === $this->debug && $result ) {
					$this->log->add( 'monei', __( 'Refund Failed: No transaction ID', 'monei' ) );
				}
				return new WP_Error( 'monei', __( 'Refund Failed: No transaction ID', 'monei' ) );
			}
		}
	}

	function monei_add_notice_new_version() {

		$version = get_option( 'hide-new-version-monei-notice' );

		if ( ! $version ) {
			if ( isset( $_REQUEST['monei-hide-new-version'] ) &&  'hide-new-version-monei' === $_REQUEST['monei-hide-new-version'] ) {
				$nonce = sanitize_text_field( $_REQUEST['_monei_hide_new_version_nonce'] );
				if ( wp_verify_nonce( $nonce, 'monei_hide_new_version_nonce' ) ) {
					update_option( 'hide-new-version-monei-notice', MONEI_VERSION );
				}
			} else {
				?>
				<div id="message" class="updated woocommerce-message woocommerce-monei-messages">
					<div class="contenido-monei-notice">
						<a class="woocommerce-message-close notice-dismiss" style="top:0;" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'monei-hide-new-version', 'hide-new-version-monei' ), 'monei_hide_new_version_nonce', '_monei_hide_new_version_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'monei' ); ?></a>
						<p>
							<h3>
								<?php esc_html_e( 'Thank you for install MONEI for WooCommerce. Version: ', 'monei' ) . ' ' . esc_html_e( MONEI_VERSION ); ?>
							</h3>
						</p>
						<p>
							<?php esc_html_e( 'The best payment gateway rates. The perfect solution to manage your digital payments.,', 'monei' ); ?>
						</p>
						<p class="submit">
							<a href="<?php esc_html_e( MONEI_SIGNUP ); ?>" class="button-primary" target="_blank"><?php esc_html_e( 'Signup', 'monei' );  ?></a>
							<a href="<?php esc_html_e( MONEI_WEB ); ?>" class="button-primary" target="_blank"><?php esc_html_e( 'MONEI website', 'monei' );  ?></a>
							<a href="<?php esc_html_e( MONEI_REVIEW ); ?>" class="button-primary" target="_blank"><?php esc_html_e( 'Leave a review', 'monei' );  ?></a>
							<a href="<?php esc_html_e( MONEI_SUPPORT ); ?>" class="button-primary" target="_blank"><?php esc_html_e( 'Support', 'monei' );  ?></a>
						</p>
					</div>
				</div>
			<?php }
		}
	}
	add_action( 'admin_notices', 'monei_add_notice_new_version' );

	function monei_notice_style() {
		wp_register_style( 'monei_notice_css', MONEI_PLUGIN_URL . 'assets/css/monei-notice.css', false, MONEI_VERSION );
		wp_enqueue_style( 'monei_notice_css' );
	}
	add_action( 'admin_enqueue_scripts', 'monei_notice_style' );
	
	function monei_style_checkout() {
		wp_register_style( 'monei_checkput_css', MONEI_PLUGIN_URL . 'assets/css/monei-checkout-card.css', false, MONEI_VERSION );
		wp_enqueue_style( 'monei_checkput_css' );
	}
	//add_action( 'wp_enqueue_scripts', 'monei_style_checkout' );

	function woocommerce_add_gateway_monei_gateway( $methods ) {
		$methods[] = 'WC_Gateway_monei';
		return $methods;
	}
	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_gateway_monei_gateway' );

	function add_monei_meta_box() {
		$date_decoded = get_post_meta( get_the_ID(), '_payment_date_monei', true );
		$hour_decoded = get_post_meta( get_the_ID(), '_payment_hour_monei', true );
		echo '<h4>' . esc_html__( 'Payment Details', 'monei' ) . '</h4>';
		echo '<p><strong>' . esc_html__( 'MONEI Date', 'monei' ) . ': </strong><br />' . esc_html( $date_decoded ) . '</p>';
		echo '<p><strong>' . esc_html__( 'MONEI Hour', 'monei' ) . ': </strong><br />' . esc_html( $hour_decoded ) . '</p>';
		echo '<p><strong>' . esc_html__( 'MONEI Order Number', 'monei' ) . ': </strong><br />' . esc_attr( get_post_meta( get_the_ID(), '_payment_order_number_monei', true ) ) . '</p>';
	}
	add_action( 'woocommerce_admin_order_data_after_billing_address', 'add_monei_meta_box' );
}
