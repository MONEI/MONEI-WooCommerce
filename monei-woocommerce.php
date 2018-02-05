<?php
/**
 * Plugin Name: MONEI WooCommerce
 * Plugin URI: https://monei.net
 * Version: 1.0.1
 * Author:       MONEI
 * Author URI:   https://monei.net/
 * Description: WooCommerce Plugin for accepting payments through MONEI Payment Gateway.
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.0
 * Tested up to: 4.9
 *
 * @package MONEI Payment Gateway for WooCommerce
 */

add_action( 'plugins_loaded', 'woo_monei_init_plugin', 0 );
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'woo_monei_add_settings_link' );
add_action( 'admin_init', 'woo_monei_wc_active_check' );

/**
 * Checks if WooCommerce plugin is active before activating MONEI
 */
function woo_monei_wc_active_check() {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		add_action( 'admin_notices', 'woo_monei_wp_not_active_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}

/**
 * Shows a worning if WooCommerce plugin is not active
 */
function woo_monei_wp_not_active_notice() {
	$install_url = admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' );
	echo '<div class="error"><p>MONEI WooCommerce requires the <a href="' . $install_url . '">WooCommerce plugin</a> to be installed and active.</p></div>';
}

/**
 * Adds a settings link on plugins page
 *
 * @param $links - set of existing links
 *
 * @return mixed modified links set
 */
function woo_monei_add_settings_link( $links ) {
	$url           = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=monei' );
	$settings_link = '<a href="' . $url . '">' . __( 'Settings' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

/**
 * Initializes MONEI WooCommerce plugin
 */
function woo_monei_init_plugin() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	include_once dirname( __FILE__ ) . '/monei-utils.php';
	include_once dirname( __FILE__ ) . '/class-monei-api-handler.php';

	class WC_Monei_Gateway extends WC_Payment_Gateway {
		private $test_mode;
		private $api_handler;

		public function __construct() {
			$this->id                   = 'monei';
			$this->method_title         = __( 'MONEI Payment Gateway', 'woo-monei-gateway' );
			$this->view_transaction_url = 'https://dashboard.monei.net/transactions/%s';
			$this->has_fields           = false;

			// Load the form fields.
			$this->init_form_fields();
			// Load the settings.
			$this->init_settings();

			// Define user set variables
			$this->title       = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->supports    = array( 'products', 'refunds' );

			$token             = $this->get_option( 'token' );
			$preauth           = $this->get_option( 'preauth' ) === 'yes';
			$credentials       = json_decode( woo_monei_decode_token( $token ) );
			$this->test_mode   = $credentials->t;
			$this->api_handler = new Monei_API_Handler( $credentials, $preauth );

			$this->do_ssl_check();

			// Actions
			add_action( 'init', array( $this, 'complete_payment' ) );
			add_action( 'woocommerce_api_monei_payment', array( $this, 'complete_payment' ) );
			add_action( 'woocommerce_receipt_monei', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'process_capture' ) );
			add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'process_capture' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_options_scripts' ) );
		}

		/**
		 * Adds color picker and chosen to plugin admin settings
		 */
		public function enqueue_admin_options_scripts() {
			if ( is_admin() ) {
				wp_enqueue_style( 'chosen', plugins_url( 'assets/chosen.min.css', __FILE__ ) );
				wp_enqueue_script( 'chosen', plugins_url( 'assets/chosen.jquery.min.js', __FILE__ ) );

				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'monei-admin-scripts', plugins_url( 'monei-admin.js', __FILE__ ), array(
					'wp-color-picker',
					'chosen'
				), false, true );
			}
		}

		/**
		 * Woocommerce Admin Panel Settings
		 */
		public function admin_options() {
			echo '<h2>' . __( 'MONEI Payment Gateway.', 'woo-monei-gateway' ) . ' </h2>';
			echo '<p>' . __( 'The easiest way to accept payments from your customers.', 'woo-monei-gateway' ) . '</p>';
			echo '<p>' . sprintf( __( 'To use this payment method you need to be registered in %sMONEI Payment Gateway%s', 'woo-monei-gateway' ), '<a href="https://monei.net" target="_blank"><b>', '</b></a>' ) . '</p>';
			echo '<table class="form-table">';
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
					'title' => __( 'Payment settings', 'woo-monei-gateway' ),
					'type'  => 'title'
				),
				'token'             => array(
					'title'       => __( 'Secret Token', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Secret token generated for your sub account in %sMONEI dashboard%s', 'woo-monei-gateway' ), '<a href="https://dashboard.monei.net/sub-accounts" target="_blank">', '</a>' ),
					'default'     => ''
				),
				'card_supported'    => array(
					'title'       => __( "Accepted Cards", 'woo-monei-gateway' ),
					'default'     => array(
						'MASTER',
						'VISA'
					),
					'description' => sprintf( __( 'Contact support at %ssupport@monei.net%s if you want to accept AMEX cards', 'woo-monei-gateway' ), '<a href="mailto:support@monei.net" target="_blank">', '</a>' ),
					'type'        => 'multiselect',
					'options'     => array(
						'AMEX'         => __( "American Express", 'woo-monei-gateway' ),
						'JCB'          => __( "JCB", 'woo-monei-gateway' ),
						'MAESTRO'      => __( "Maestro", 'woo-monei-gateway' ),
						'MASTER'       => __( "MasterCard", 'woo-monei-gateway' ),
						'MASTERDEBIT'  => __( "MasterCard Debit", 'woo-monei-gateway' ),
						'VISA'         => __( "Visa", 'woo-monei-gateway' ),
						'VISADEBIT'    => __( "Visa Debit", 'woo-monei-gateway' ),
						'VISAELECTRON' => __( "Visa Electron", 'woo-monei-gateway' ),
						'PAYPAL'       => __( "PayPal", 'woo-monei-gateway' ),
						'BITCOIN'      => __( "Bitcoin", 'woo-monei-gateway' ),
						'ALIPAY'       => __( "Alipay", 'woo-monei-gateway' )
					)
				),
				'preauth'           => array(
					'title'       => __( 'Preauthorization payment', 'woo-monei-gateway' ),
					'type'        => 'checkbox',
					'description' => __( 'Makes a preauthorization payment that is captured when order status is changed to processing or completed', 'woo-monei-gateway' ),
					'default'     => 'no'
				),
				'descriptor'        => array(
					'title'       => __( 'Descriptor', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'Descriptor that will be shown in customer\'s bank statement', 'woo-monei-gateway' )
				),
				'appearance'        => array(
					'title' => __( 'Appearance settings', 'woo-monei-gateway' ),
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
					'description' => __( 'Description of payment method which the user sees during checkout', 'woo-monei-gateway' ),
					'default'     => __( "Pay via MONEI payment gateway.", 'woo-monei-gateway' )
				),
				'submit_text'       => array(
					'title'       => __( 'Submit text', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'Submit button text, {amount} will be replaced with amount value with currency. Default: Pay now', 'woo-monei-gateway' )
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
					'title'       => __( 'Show CVV hint', 'woo-monei-gateway' ),
					'type'        => 'checkbox',
					'label'       => __( 'Show CVV hint', 'woo-monei-gateway' ),
					'description' => __( 'If set to true then the credit card form will display a hint on where the CVV is located when the mouse is hovering over the CVV field.', 'woo-monei-gateway' ),
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
					'description' => __( 'A color for checkout and submit button', 'woo-monei-gateway' ),
					'default'     => ''
				),
				'custom_css_class'  => array(
					'title'       => __( 'Widget css class', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'CSS class of the root widget element', 'woo-monei-gateway' ),
					'default'     => 'monei-payment-widget'
				),
				'popup'             => array(
					'title'       => __( 'Popup mode', 'woo-monei-gateway' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable popup mode', 'woo-monei-gateway' ),
					'description' => __( 'Renders a button and shows payment form in a popup when button is clicked', 'woo-monei-gateway' ),
					'default'     => 'no'
				),
				'popup_config'      => array(
					'title' => __( 'Popup configuration', 'woo-monei-gateway' ),
					'type'  => 'title'
				),
				'checkout_text'     => array(
					'title'       => __( 'Checkout text', 'woo-monei-gateway' ),
					'type'        => 'text',
					'description' => __( 'Checkout button text in popup mode, {amount} will be replaced with amount value with currency. Default: Pay {amount}', 'woo-monei-gateway' )
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
		}

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
		public function generatewoo_monei_payment_form( $order_id ) {
			$order    = wc_get_order( $order_id );
			$amount   = $order->get_total();
			$currency = $order->get_currency();
			$response = $this->api_handler->prepare_checkout( $order );
			if ( $response && $response->id ) {
				$locale     = get_locale();
				$brands     = implode( ' ', $this->get_option( 'card_supported' ) );
				$return_url = add_query_arg( 'wc-api', 'monei_payment', home_url( '/' ) );
				$config     = array(
					'checkoutId'       => $response->id,
					'brands'           => $brands,
					'redirectUrl'      => $return_url,
					'amount'           => $amount,
					'currency'         => $currency,
					'popup'            => $this->get_option( 'popup' ) === 'yes',
					'test'             => $this->test_mode,
					'primaryColor'     => $this->get_option( 'primary_color' ),
					'name'             => $this->get_option( 'popup_name' ),
					'description'      => $this->get_option( 'popup_description' ),
					'showCardHolder'   => $this->get_option( 'show_cardholder' ) === 'yes',
					'submitText'       => $this->get_option( 'submit_text' ),
					'checkoutText'     => $this->get_option( 'checkout_text' ),
					'showCVVHint'      => $this->get_option( 'show_cvv_hint' ) === 'yes',
					'maskCvv'          => $this->get_option( 'mask_cvv' ) === 'yes',
					'showLabels'       => $this->get_option( 'show_labels' ) === 'yes',
					'showPlaceholders' => $this->get_option( 'show_placeholders' ) === 'yes',
					'showEmail'        => false,
					'locale'           => $locale
				);

				$json_config      = json_encode( $config );
				$custom_css_class = $this->get_option( 'custom_css_class' );

				echo '<script src="https://widget.monei.net/widget2.js"></script>';
				echo "<script>
					moneiWidget.disableAutoSetup();
					document.addEventListener('DOMContentLoaded', function() {
					  moneiWidget.setup('monei-payment-widget', $json_config);
					})
				</script>";
				echo '<div id="monei-payment-widget" class="' . $custom_css_class . '"></div>';

				return true;
			} else {
				return false;
			}
		}

		/**
		 *    Updating payment status and redirect to success/fail Page
		 **/
		public function complete_payment() {
			if ( isset( $_GET['resourcePath'] ) ) {
				$response = $this->api_handler->get_transaction_status( $_GET['resourcePath'] );
				if ( ! $response ) {
					return false;
				}
				$order_id       = $response->merchantInvoiceId;
				$order          = wc_get_order( $order_id );
				$transaction_id = $response->id;
				if ( $this->api_handler->is_transaction_successful( $response ) ) {
					if ( $response->paymentType === 'PA' ) {
						update_post_meta( $order_id, '_transaction_id', $transaction_id );
						update_post_meta( $order_id, 'woo_monei_status', 'pending' );
						$order->update_status( 'wc-on-hold' );
					} else {
						$order->payment_complete( $transaction_id );
						update_post_meta( $order_id, 'woo_monei_status', 'success' );
					}
					$order->add_order_note( $this->api_handler->get_payment_message( $order, $response ) );
					wp_redirect( $this->get_return_url( $order ) );
					exit();
				} else {
					update_post_meta( $order_id, 'woo_monei_status', 'fail' );
					$order->add_order_note( sprintf( __( 'Payment fail with status: "%s."', 'woo-monei-gateway' ), $response->result->description ) );
					wp_redirect( $order->get_cancel_order_url() );
					exit();
				}
			}
		}


		/**
		 * Process payment and return the result
		 *
		 * @param int $order_id - an order to process payment for
		 *
		 * @return array - redirect array
		 */
		function process_payment( $order_id ) {
			global $woocommerce;
			$order = wc_get_order( $order_id );
			if ( $woocommerce->version >= 2.1 ) {
				$redirect = $order->get_checkout_payment_url( true );
			} else {
				$redirect = add_query_arg( 'order', $order->get_id(), add_query_arg( 'key', $order->get_order_key(), get_permalink( get_option( 'woocommerce_pay_page_id' ) ) ) );
			}

			return array(
				'result'   => 'success',
				'redirect' => $redirect
			);
		}

		/**
		 * @param int $order_id - an order to process refund for
		 * @param int $amount - amount to refund
		 * @param string $reason
		 *
		 * @return bool
		 */
		function process_refund( $order_id, $amount = null, $reason = '' ) {
			$order = wc_get_order( $order_id );

			if ( $amount === 0 || $amount === null ) {
				new WP_Error( 'monei_gateway_error', __( 'Refund Error: You need to specify a refund amount.', 'woo-monei-gateway' ) );

				return false;
			}

			$response = $this->api_handler->refund_transaction( $order, $amount, $reason );
			if ( ! $response ) {
				return false;
			}
			if ( $this->api_handler->is_transaction_successful( $response ) ) {
				$status = $order->get_remaining_refund_amount() > 0 ? 'partial_refund' : 'full_refund';
				update_post_meta( $order_id, 'woo_monei_status', $status );
				$order->update_status( 'wc-refunded' );
				$order->add_order_note( $this->api_handler->get_payment_message( $order, $response ) );

				return true;
			} else {
				$order->add_order_note( sprintf( __( 'Refund fail with status: "%s."', 'woo-monei-gateway' ), $response->result->description ) );

				return false;
			}
		}

		/**
		 * Capture payment when the order is changed from on-hold to complete or processing
		 *
		 * @param $order_id - an order to process capture for
		 *
		 * @return bool
		 * @throws WC_Data_Exception
		 */
		public function process_capture( $order_id ) {
			$order    = wc_get_order( $order_id );
			$response = $this->api_handler->capture_transaction( $order );
			if ( ! $response ) {
				return false;
			}
			if ( $this->api_handler->is_transaction_successful( $response ) ) {
				update_post_meta( $order_id, 'woo_monei_status', 'success' );
				$order->set_date_paid( current_time( 'timestamp', true ) );
				$order->add_order_note( $this->api_handler->get_payment_message( $order, $response ) );

				return true;
			} else {
				update_post_meta( $order_id, 'woo_monei_status', 'fail' );
				$order->add_order_note( sprintf( __( 'Refund fail with status: "%s."', 'woo-monei-gateway' ), $response->result->description ) );

				return false;
			}
		}

		/**
		 * Generates receipt page
		 **/
		function receipt_page( $order ) {
			//Generating Payment Form.
			$this->generatewoo_monei_payment_form( $order );
		}


		/**
		 * Checks is WooCommerce is forcing ssl
		 */
		public function do_ssl_check() {
			if ( $this->enabled === "yes" && ! $this->test_mode && $_GET['section'] !== 'monei' && get_option( 'woocommerce_force_ssl_checkout' ) === "no" ) {
				echo "<div class=\"error\"><p>" . sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . "</p></div>";

			}
		}
	}

	/**
	 * Add the gateway to WooCommerce
	 **/
	function add_woo_monei_gateway( $methods ) {
		$methods[] = 'WC_Monei_Gateway';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_woo_monei_gateway' );
}
