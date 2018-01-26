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
add_action('plugins_loaded', 'init_woocommerce_monei', 0);
function init_woocommerce_monei() {
  if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
  class woocommerce_monei extends WC_Payment_Gateway {
    public function __construct() {
      global $woocommerce;
      $this->id			= 'monei';
      $this->method_title = __( 'MONEI Payment Gateway', 'woo-monei-gateway' );
      $this->icon			= plugins_url( 'monei.png', __FILE__ );
      $this->screen 		= plugins_url( 'screen.png', __FILE__ );
      $this->has_fields 	= false;
      // Load the form fields.
      $this->init_form_fields();
      // Load the settings.
      $this->init_settings();
      // Define user set variables
      $this->title 		= $this->settings['title'];
      $this->description 	= $this->settings['description'];
      $this->operation 	= $this->settings['operation_mode'];
      $this->supports     = array( 'refunds' );
      $this->token  	= $this->settings['token'];
      $this->cards = implode(' ', $this->settings['card_supported']);
      $this->popup  	= $this->settings['popup'];
      $this->woocommerce_version 	= $woocommerce->version;
      $this->return_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'monei_payment', home_url( '/' ) ) );
      // Actions
      add_action( 'init', array($this, 'monei_process') );
      add_action( 'woocommerce_api_monei_payment', array( $this, 'monei_process' ) );
      add_action( 'woocommerce_receipt_monei', array($this, 'receipt_page') );
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      // add the action
      add_action( 'woocommerce_order_refunded', array($this, 'action_woocommerce_order_refunded'), 10, 2 );
      // Lets check for SSL
      add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
    }
    /**
    * Woocommerce Admin Panel Option
    * - Manage MONEI Settings here.
    *
    */
    public function admin_options() {
      echo '<h2>'. __('MONEI Payment Gateway.', 'woo-monei-gateway') .' </h2>';
      echo '<p>'. __('MONEI Configuration Settings.', 'woo-monei-gateway') .'</p>';
      echo '<table class="form-table">';
      $this->generate_settings_html();
      echo '</table>';
    }
    /**
    * Initialise MONEI Payment Gateway Settings Form Fields
    */
    function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable/Disable', 'woo-monei-gateway' ),
          'type' => 'checkbox',
          'label' => __( 'Enable MONEI', 'woo-monei-gateway' ),
          'default' => 'yes'
        ),
        'operation_mode' => array(
          'title' => __("Operation Mode", 'woo-monei-gateway'),
          'default' => 'test',
          'description' => __("You can switch between different environments, by selecting the corresponding operation mode.", 'woo-monei-gateway'),
          'type' => 'select',
          'class' => 'monei_mode',
          'options' => array(
            'test' => __("Test mode", 'woo-monei-gateway'),
            'live' => __("Live Mode", 'woo-monei-gateway'),
            )
          ),
          'title' => array(
            'title' => __( 'Title', 'woo-monei-gateway' ),
            'type' => 'text',
            'description' => __( 'This controls the title which the user sees during checkout.', 'woo-monei-gateway' ),
            'default' => __( 'MONEI Payment gateway', 'woo-monei-gateway' ),
            'desc_tip'    => true
          ),
          'description' => array(
            'title' => __( 'Description', 'woo-monei-gateway' ),
            'type' => 'textarea',
            'description' => __( 'This controls the description which the user sees during checkout.', 'woo-monei-gateway' ),
            'default' => __("Pay via MONEI payment gateway.", 'woo-monei-gateway'),
            'desc_tip'    => true
          ),
          'token' => array(
            'title' => __( 'Token', 'woo-monei-token' ),
            'type' => 'text',
            'description' => __( 'Please enter your MONEI Token; this is needed in order to take the payment.', 'woo-monei-gateway' ),
            'default' => '',
            'desc_tip'    => true
          ),
          'popup' => array(
            'title' => __( 'Popup mode', 'woo-monei-gateway' ),
            'type' => 'checkbox',
            'label' => __( 'Popup mode', 'woo-monei-gateway' ),
            'default' => 'no'
          ),
          'card_supported' => array(
            'title' => __("Accepted Cards", 'woo-monei-gateway'),
            'default' => array('AMEX', 'JCB', 'MAESTRO', 'MASTER', 'MASTER DEBIT', 'VISA', 'VISA DEBIT', 'VISA ELECTRON'),
            'description' => sprintf( __( 'Contact support at %ssupport@monei.net%s if you want to accept AMEX transactions.', 'woo-monei-gateway' ), '<a href="mailto:support@monei.net" target="_blank">', '</a>' ),
            'type' => 'multiselect',
            'options' => array(
              'AMEX' => __("AMEX", 'woo-monei-gateway'),
              'JCB'  => __("JCB", 'woo-monei-gateway'),
              'MAESTRO' => __("MAESTRO", 'woo-monei-gateway'),
              'MASTER' => __("MASTER", 'woo-monei-gateway'),
              'MASTERDEBIT' => __("MASTER DEBIT", 'woo-monei-gateway'),
              'VISA' => __("VISA", 'woo-monei-gateway'),
              'VISADEBIT' => __("VISA DEBIT", 'woo-monei-gateway'),
              'VISAELECTRON' => __("VISA ELECTRON", 'woo-monei-gateway'),
              )
              )
            );
          } // End init_form_fields()
          /**
          * Adding MONEI Payment Gateway Button in checkout page.
          **/
          function payment_fields() {
            if ($this->description) echo wpautop(wptexturize($this->description));
          }
          /**
          *	Creating MONEI Payment Form.
          **/
          public function generate_monei_payment_form( $order_id ){
            global $woocommerce;
            $order = new WC_Order( $order_id );
            //return $order_id; die();
            //Required Order Details
            $amount 	= $order->get_total();
            $currency 	= get_woocommerce_currency();
            $test = ($this->operation == 'test' ? 'true' : 'false');
            $popup = ($this->operation == 'yes' ? 'true' : 'false');

            echo '<script src="https://widget.monei.net/widget2.js"></script>';
            echo '<div
              class="monei-widget"
              data-token="'.$this->style.'"
              data-brands="'.$this->cards.'"
              data-redirect-url="'.$this->return_url.'"
              data-amount="'.$amount.'"
              data-currency="'.$currency.'"
              data-popup="'.$this->popup.'"
              data-test="'.$test.'" >
            </div>';
          }
          /**
          *	Updating the Payment Status and redirect to success/Failes Page
          **/
          public function monei_process(){
            global $woocommerce;
            if(isset($_GET['resourcePath'])) {
              $url = $this->monei_url.$_GET['resourcePath'];
              $url .= "?authentication.userId=".$this->USER_ID;
              $url .= "&authentication.password=".$this->PASSWORD;
              $url .= "&authentication.entityId=".$this->CHANNEL_ID;
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, $url);
              curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
              $responseData = curl_exec($ch);
              if(curl_errno($ch)) {
                return curl_error($ch);
              }
              curl_close($ch);
              $response = json_decode($responseData);
              $success_code = array('000.000.000', '000.000.100', '000.100.110', '000.100.111', '000.100.112', '000.300.000');
              $order = new WC_Order( $response->merchantInvoiceId );
              if(in_array($response->result->code, $success_code)){
                $order->payment_complete( $response->id );
                $order->add_order_note(sprintf(__('MONEI Transaction Successful. The Transaction ID was %s and Payment Status %s.', 'woo-monei-gateway'), $response->id, $response->result->description ));
                wp_redirect($this->get_return_url( $order )); exit();
              } else {
                $order->add_order_note(sprintf(__('MONEI Transaction Failed. The Transaction Status %s.', 'woo-monei-gateway'), $response->result->description ));
                wp_redirect($order->get_cancel_order_url()); exit();
              }
            }
          }
          /**
          * Process the payment and return the result
          **/
          function process_payment( $order_id ) {
            $order = new WC_Order( $order_id );
            if($this->woocommerce_version >= 2.1){
              $redirect = $order->get_checkout_payment_url( true );
            } else{
              $redirect = add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))));
            }
            return array(
              'result' 	=> 'success',
              'redirect'	=> $redirect
            );
          }
          /**
          * Process the payment and return the result
          **/
          function process_refund( $order_id, $amount = null, $reason = ''  ) {
            global $woocommerce;
            $order 	= new WC_Order( $order_id );
            $trx_id		= get_post_meta( $order_id , '_transaction_id', true );
            $amount 	= $order->get_total();
            $currency 	= get_woocommerce_currency();
            $response = json_decode($this->refund_request($trx_id, $amount, $currency));
            $success_code = array('000.000.000', '000.000.100', '000.100.110', '000.100.111', '000.100.112', '000.300.000');
            if(in_array($response->result->code, $success_code)){
              $order->add_order_note(sprintf(__('MONEI Refund Processed Successful. The Refund ID was %s and Request Status => %s.', 'woo-monei-gateway'), $response->id, $response->result->description ));
              $order->update_status('wc-refunded');
              return true;
            } else {
              $order->add_order_note(sprintf(__('MONEI Refund Request Failed. The Refund Status => %s.', 'woo-monei-gateway'), $response->result->description ));
              return false;
            }
            return false;
          }
          /**
          * receipt_page
          **/
          function receipt_page( $order ) {
            //Generating Payment Form.
            $this->generate_monei_payment_form( $order );
          }
          function refund_request($id, $amount, $currency) {
            $url = $this->monei_url."/v1/payments/".$id;
            $data = "authentication.userId=".$this->USER_ID .
            "&authentication.password=".$this->PASSWORD .
            "&authentication.entityId=".$this->CHANNEL_ID .
            "&amount=".$amount .
            "&currency=".$currency .
            "&paymentType=DB";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if(curl_errno($ch)) {
              return curl_error($ch);
            }
            curl_close($ch);
            return $responseData;
          }
          // Custom function not required by the Gateway
          public function do_ssl_check() {
            if( $this->enabled == "yes" ) {
              if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
                echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";
              }
            }
          }
        }
        /**
        * Add the gateway to WooCommerce
        **/
        function add_monei_gateway( $methods ) {
          $methods[] = 'woocommerce_monei'; return $methods;
        }
        add_filter('woocommerce_payment_gateways', 'add_monei_gateway' );
      }
