<?php
/**
 * Plugin Name: MONEI Gateway for WooCommerce
 * Plugin URI: https://monei.net
 * Description: WooCommerce Plugin for accepting payments through MONEI Payment Gateway.
 * Requires at least: 4.0
 * Tested up to: 4.6
 *
 * @package MONEI Payment Gateway for WooCommerce
 */

include dirname( __FILE__ ) . '/utils.php';

add_action( 'plugins_loaded', 'init_woocommerce_monei', 0 );
add_action( 'admin_enqueue_scripts', 'add_color_picker' );
function add_color_picker( $hook ) {

	if ( is_admin() ) {

		// Add the color picker css file
		wp_enqueue_style( 'wp-color-picker' );

		// Include our custom jQuery file with WordPress Color Picker dependency
		wp_enqueue_script( 'custom-script-handle', plugins_url( 'custom-script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
	}
}

function init_woocommerce_monei() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	class woocommerce_monei extends WC_Payment_Gateway {
		private $test_mode;
		private $monei_url;
		private $USER_ID;
		private $CHANNEL_ID;
		private $PASSWORD;

		public function __construct() {
			$this->id           = 'monei';
			$this->method_title = __( 'MONEI Payment Gateway', 'woo-monei-gateway' );
			$this->icon         = plugins_url( 'monei.png', __FILE__ );
			$this->screen       = plugins_url( 'screen.png', __FILE__ );
			$this->has_fields   = false;

			// Load the form fields.
			$this->init_form_fields();
			// Load the settings.
			$this->init_settings();
			// Define user set variables

			$this->title       = $this->settings['title'];
			$this->description = $this->settings['description'];
			$this->supports    = array( 'refunds' );

			$token            = $this->settings['token'];
			$credentials      = json_decode( _base64_decode( $token ) );
			$this->test_mode  = $this->settings['production'] == 'no';
			$this->monei_url  = $this->test_mode ? "https://test.monei-api.net" : "https://monei-api.net";
			$this->USER_ID    = $credentials->l;
			$this->PASSWORD   = $credentials->p;
			$this->CHANNEL_ID = $credentials->c;

			// Actions
			add_action( 'init', array( $this, 'monei_process' ) );
			add_action( 'woocommerce_api_monei_payment', array( $this, 'monei_process' ) );
			add_action( 'woocommerce_receipt_monei', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
			// add the action
			add_action( 'woocommerce_order_refunded', array( $this, 'action_woocommerce_order_refunded' ), 10, 2 );
			// Lets check for SSL
			add_action( 'admin_notices', array( $this, 'do_ssl_check' ) );
		}

		/**
		 * Woocommerce Admin Panel Option
		 * - Manage MONEI Settings here.
		 *
		 */
		public function admin_options() {
			echo '<h2>' . __( 'MONEI Payment Gateway.', 'woo-monei-gateway' ) . ' </h2>';
			echo '<p>' . __( 'The easiest way to accept payments from your customers.', 'woo-monei-gateway' ) . '</p>';
			echo '<p>' . sprintf( __( 'To use this payment method you need to be registered in %sMONEI Payment Gateway%s', 'woo-monei-gateway' ), '<a href="https://monei.net" target="_blank"><b>', '</b></a>' ) . '</p>';
			echo '<table class="form-table">';
			wc_enqueue_js( "
		      $('#woocommerce_monei_primary_color').wpColorPicker();
		        console.log($('#woocommerce_monei_popup'));
		        $('#woocommerce_monei_popup').change(function(){
		          if (this.checked) {
		            $( '#woocommerce_monei_popup_config, #woocommerce_monei_popup_config + .form-table' ).show();
		          } else {
		            $( '#woocommerce_monei_popup_config, #woocommerce_monei_popup_config + .form-table' ).hide();
		          }
		        }).change();
            " );
			$this->generate_settings_html();
			echo '</table>';
		}

		/**
		 * Initialise MONEI Payment Gateway Settings Form Fields
		 */
		function init_form_fields() {
			$this->form_fields = array(
				'enabled'           => array(
					'title'   => __( 'Enable', 'woo-monei-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable MONEI Payment Gateway', 'woo-monei-gateway' ),
					'default' => 'yes'
				),
				'payment'           => array(
					'title' => __( 'Payment configuration', 'woo-monei-gateway' ),
					'type'  => 'title'
				),
				'token'             => array(
					'title'       => __( 'Secret Token', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => sprintf( __( 'secret token generated for sub account in %sMONEI dashboard%s', 'woo-monei-gateway' ), '<a href="https://dashboard.monei.net" target="_blank">', '</a>' ),
					'default'     => ''
				),
				'production'        => array(
					'title'       => __( 'Production mode', 'woo-monei-gateway' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable production mode', 'woo-monei-gateway' ),
					'description' => __( 'to use production mode you need to provide production token', 'woo-monei-gateway' ),
					'default'     => 'no'
				),
				'card_supported'    => array(
					'title'       => __( "Accepted Cards", 'woo-monei-gateway' ),
					'default'     => array(
						'AMEX',
						'JCB',
						'MAESTRO',
						'MASTER',
						'MASTER DEBIT',
						'VISA',
						'VISA DEBIT',
						'VISA ELECTRON'
					),
					'description' => sprintf( __( 'Contact support at %ssupport@monei.net%s if you want to accept AMEX cards.', 'woo-monei-gateway' ), '<a href="mailto:support@monei.net" target="_blank">', '</a>' ),
					'type'        => 'multiselect',
					'options'     => array(
						'AMEX'         => __( "AMEX", 'woo-monei-gateway' ),
						'JCB'          => __( "JCB", 'woo-monei-gateway' ),
						'MAESTRO'      => __( "MAESTRO", 'woo-monei-gateway' ),
						'MASTER'       => __( "MASTER", 'woo-monei-gateway' ),
						'MASTERDEBIT'  => __( "MASTER DEBIT", 'woo-monei-gateway' ),
						'VISA'         => __( "VISA", 'woo-monei-gateway' ),
						'VISADEBIT'    => __( "VISA DEBIT", 'woo-monei-gateway' ),
						'VISAELECTRON' => __( "VISA ELECTRON", 'woo-monei-gateway' ),
					)
				),
				'descriptor'        => array(
					'title'       => __( 'Descriptor', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'Descriptor that will be shown in customer\'s bank statement', 'woo-monei-gateway' )
				),
				'appearance'        => array(
					'title' => __( 'Appearance configuration', 'woo-monei-gateway' ),
					'type'  => 'title'
				),
				'title'             => array(
					'title'       => __( 'Title', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'title of payment method which the user sees during checkout', 'woo-monei-gateway' ),
					'default'     => __( 'MONEI Payment gateway', 'woo-monei-gateway' )
				),
				'description'       => array(
					'title'       => __( 'Description', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'description of payment method which the user sees during checkout', 'woo-monei-gateway' ),
					'default'     => __( "Pay via MONEI payment gateway.", 'woo-monei-gateway' )
				),
				'submit_text'       => array(
					'title'       => __( 'Submit text', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'submit button text, {amount} will be replaced with amount value with currency. Default: Pay now', 'woo-monei-gateway' )
				),
				'show_cardholder'   => array(
					'title'   => __( 'Show cardholder', 'woo-monei-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Shows cardholder field in payment form', 'woo-monei-gateway' ),
					'default' => 'no'
				),
				'mask_cvv'          => array(
					'title'   => __( 'Mask CVV field', 'woo-monei-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Mask CVV field', 'woo-monei-gateway' ),
					'default' => 'no'
				),
				'show_cvv_hint'     => array(
					'title'       => __( 'Show cvv hint', 'woo-monei-gateway' ),
					'type'        => 'checkbox',
					'label'       => __( 'Show cvv hint', 'woo-monei-gateway' ),
					'description' => __( 'if set to true then the credit card form will display a hint on where the CVV is located when the mouse is hovering over the CVV field.', 'woo-monei-gateway' ),
					'default'     => 'no'
				),
				'show_labels'       => array(
					'title'   => __( 'Show labels', 'woo-monei-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Shows input labels.', 'woo-monei-gateway' ),
					'default' => 'no'
				),
				'show_placeholders' => array(
					'title'   => __( 'Show placeholders', 'woo-monei-gateway' ),
					'type'    => 'checkbox',
					'label'   => __( 'Shows input placeholders.', 'woo-monei-gateway' ),
					'default' => 'yes'
				),
				'primary_color'     => array(
					'title'       => __( 'Primary color', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'a color for checkout and submit button', 'woo-monei-gateway' ),
					'default'     => ''
				),
				'popup'             => array(
					'title'       => __( 'Popup mode', 'woo-monei-gateway' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable popup mode', 'woo-monei-gateway' ),
					'description' => __( 'renders a button and shows payment form in a popup when button is clicked', 'woo-monei-gateway' ),
					'default'     => 'no'
				),
				'popup_config'      => array(
					'title' => __( 'Popup configuration', 'woo-monei-gateway' ),
					'type'  => 'title'
				),
				'checkout_text'     => array(
					'title'       => __( 'Checkout text', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'checkout button text in popup mode, {amount} will be replaced with amount value with currency. Default: Pay {amount}', 'woo-monei-gateway' )
				),
				'popup_name'        => array(
					'title' => __( 'Popup header name', 'woo-monei-gateway' ),
					'type'  => 'text',
				),
				'popup_description' => array(
					'title' => __( 'Popup header description', 'woo-monei-gateway' ),
					'type'  => 'text',
				)
			);
		} // End init_form_fields()

		/**
		 * Adding MONEI Payment Gateway Button in checkout page.
		 **/
		function payment_fields() {
			if ( $this->description ) {
				echo wpautop( wptexturize( $this->description ) );
			}
		}

		/**
		 *    Creating MONEI Payment Form.
		 **/
		public function generate_monei_payment_form( $order_id ) {

			$order       = new WC_Order( $order_id );
			$amount      = $order->get_total();
			$currency    = $order->get_currency();
			$customer_id = $order->get_customer_id();
			$order_data  = $order->get_data();
			$billing     = $order_data['billing'];
			$shipping    = $order_data['shipping'];

			$url  = $this->monei_url . "/v1/checkouts";
			$data = http_build_query( array(
				'authentication.userId'       => $this->USER_ID,
				'authentication.password'     => $this->PASSWORD,
				'authentication.entityId'     => $this->CHANNEL_ID,
				'amount'                      => $amount,
				'currency'                    => $currency,
				'merchantInvoiceId'           => $order_id,
				'paymentType'                 => 'DB',
				'customer.merchantCustomerId' => $customer_id,
				'customer.email'              => $billing['email'],
				'customer.givenName'          => $billing['first_name'],
				'customer.surname'            => $billing['last_name'],
				'customer.phone'              => $billing['phone'],
				'customer.companyName'        => $billing['company'],
				'billing.country'             => $billing['country'],
				'billing.state'               => $billing['state'],
				'billing.city'                => $billing['city'],
				'billing.postcode'            => $billing['postcode'],
				'billing.street1'             => $billing['address_1'],
				'billing.street2'             => $billing['address_2'],
				'shipping.country'            => $shipping['country'],
				'shipping.state'              => $shipping['state'],
				'shipping.city'               => $shipping['city'],
				'shipping.postcode'           => $shipping['postcode'],
				'shipping.street1'            => $shipping['address_1'],
				'shipping.street2'            => $shipping['address_2'],
				'customParameters'            => array(
					'customerNote'      => $order_data['customer_note'],
					'customerUserAgent' => $order_data['customer_user_agent']
				)
			) );
			$ch   = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$responseData = curl_exec( $ch );
			if ( curl_errno( $ch ) ) {
				return curl_error( $ch );
			}
			curl_close( $ch );
			$status = json_decode( $responseData );

			if ( $status->id ) {
				$locale     = get_locale();
				$brands     = implode( ' ', $this->settings['card_supported'] );
				$return_url = add_query_arg( 'wc-api', 'monei_payment', home_url( '/' ) );
				$config     = array(
					'checkoutId'       => $status->id,
					'brands'           => $brands,
					'redirectUrl'      => $return_url,
					'amount'           => $amount,
					'currency'         => $currency,
					'popup'            => $this->settings['popup'] === 'yes',
					'test'             => $this->test_mode,
					'primaryColor'     => $this->settings['primary_color'],
					'name'             => $this->settings['popup_name'],
					'description'      => $this->settings['popup_description'],
					'showCardHolder'   => $this->settings['show_cardholder'] === 'yes',
					'submitText'       => $this->settings['submit_text'],
					'checkoutText'     => $this->settings['checkout_text'],
					'showCVVHint'      => $this->settings['show_cvv_hint'] === 'yes',
					'maskCvv'          => $this->settings['mask_cvv'] === 'yes',
					'showLabels'       => $this->settings['show_labels'] === 'yes',
					'showPlaceholders' => $this->settings['show_placeholders'] === 'yes',
					'showEmail'        => false,
					'locale'           => $locale,
				);

				$json_config = json_encode( $config );

				echo '<script src="https://widget.monei.net/widget2.js"></script>';
				echo "<script>
					moneiWidget.disableAutoSetup();
					document.addEventListener('DOMContentLoaded', function() {
					  moneiWidget.setup('monei-payment-widget', $json_config)
					})
				</script>";
				echo '<div id="monei-payment-widget"></div>';
			} else {
				return false;
			}
		}

		/**
		 *    Updating the Payment Status and redirect to success/Failes Page
		 **/
		public function monei_process() {
			if ( isset( $_GET['resourcePath'] ) ) {
				$url = add_query_arg( array(
					'authentication.userId'   => $this->USER_ID,
					'authentication.password' => $this->PASSWORD,
					'authentication.entityId' => $this->CHANNEL_ID
				), $this->monei_url . $_GET['resourcePath'] );
				$ch  = curl_init();
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				$responseData = curl_exec( $ch );
				if ( curl_errno( $ch ) ) {
					return curl_error( $ch );
				}
				curl_close( $ch );
				$response     = json_decode( $responseData );
				$success_code = array(
					'000.000.000',
					'000.000.100',
					'000.100.110',
					'000.100.111',
					'000.100.112',
					'000.300.000'
				);
				$order        = new WC_Order( $response->merchantInvoiceId );
				if ( in_array( $response->result->code, $success_code ) ) {
					$order->payment_complete( $response->id );
					$order->add_order_note( sprintf( __( 'MONEI Transaction Successful. The Transaction ID was %s and Payment Status %s.', 'woo-monei-gateway' ), $response->id, $response->result->description ) );
					wp_redirect( $this->get_return_url( $order ) );
					exit();
				} else {
					$order->add_order_note( sprintf( __( 'MONEI Transaction Failed. The Transaction Status %s.', 'woo-monei-gateway' ), $response->result->description ) );
					wp_redirect( $order->get_cancel_order_url() );
					exit();
				}
			}
		}

		/**
		 * Process the payment and return the result
		 **/
		function process_payment( $order_id ) {
			global $woocommerce;
			$order = new WC_Order( $order_id );
			if ( $woocommerce->version >= 2.1 ) {
				$redirect = $order->get_checkout_payment_url( true );
			} else {
				$redirect = add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( get_option( 'woocommerce_pay_page_id' ) ) ) );
			}

			return array(
				'result'   => 'success',
				'redirect' => $redirect
			);
		}

		/**
		 * Process the payment and return the result
		 **/
		function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order        = new WC_Order( $order_id );
			$trx_id       = get_post_meta( $order_id, '_transaction_id', true );
			$amount       = $order->get_total();
			$currency     = get_woocommerce_currency();
			$response     = json_decode( $this->refund_request( $trx_id, $amount, $currency ) );
			$success_code = array(
				'000.000.000',
				'000.000.100',
				'000.100.110',
				'000.100.111',
				'000.100.112',
				'000.300.000'
			);
			if ( in_array( $response->result->code, $success_code ) ) {
				$order->add_order_note( sprintf( __( 'MONEI Refund Processed Successful. The Refund ID was %s and Request Status => %s.', 'woo-monei-gateway' ), $response->id, $response->result->description ) );
				$order->update_status( 'wc-refunded' );

				return true;
			} else {
				$order->add_order_note( sprintf( __( 'MONEI Refund Request Failed. The Refund Status => %s.', 'woo-monei-gateway' ), $response->result->description ) );

				return false;
			}
		}

		/**
		 * receipt_page
		 **/
		function receipt_page( $order ) {
			//Generating Payment Form.
			$this->generate_monei_payment_form( $order );
		}

		function refund_request( $id, $amount, $currency ) {
			$url  = $this->monei_url . "/v1/payments/" . $id;
			$data = http_build_query( array(
					'authentication.userId'   => $this->USER_ID,
					'authentication.password' => $this->PASSWORD,
					'authentication.entityId' => $this->CHANNEL_ID,
					'amount'                  => $amount,
					'currency'                => $currency,
					'paymentType'             => 'RF',
				)
			);
			$ch   = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$responseData = curl_exec( $ch );
			if ( curl_errno( $ch ) ) {
				return curl_error( $ch );
			}
			curl_close( $ch );

			return $responseData;
		}

		// Custom function not required by the Gateway
		public function do_ssl_check() {
			if ( $this->enabled == "yes" ) {
				if ( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
					echo "<div class=\"error\"><p>" . sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . "</p></div>";
				}
			}
		}
	}

	/**
	 * Add the gateway to WooCommerce
	 **/
	function add_monei_gateway( $methods ) {
		$methods[] = 'woocommerce_monei';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_monei_gateway' );
}
