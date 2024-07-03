<?php

 use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

 final class WC_Gateway_Monei_CC_Blocks extends AbstractPaymentMethodType {

 	private $gateway;
 	protected $name = 'monei';
 	private $profile_monitor;

 	public function initialize() {

 		$this->settings = get_option( 'woocommerce_monei_settings', array() );
 		$this->gateway  = new WC_Gateway_Monei_CC();

 	}


 	public function is_active() {

 		return 'yes' === $this->get_setting( 'enabled' );

 	}


 	public function get_payment_method_script_handles() {

 		$script_name = 'wc-monei-cc-blocks-integration';

 		wp_register_script(
 			$script_name,
 			WC_Monei()->plugin_url(). '/assets/js/checkout-cc.js',
 			array(
 				'wc-blocks-registry',
 				'wc-settings',
 				'wp-element',
 				'wp-html-entities',
 				'wp-i18n',
 			),
 			time(),
 			true
 		);

 		if ( function_exists( 'wp_set_script_translations' ) ) {
 			wp_set_script_translations( $script_name );
 		}

 		return array( $script_name );
 	}


 	public function get_payment_method_data() {

 		$data = array(

 			'title'       => $this->gateway->title,
 			'description' => $this->gateway->description,
 			
 			/*

 			'supports'    => array( 
 								'showSavedCards' => 'yes',
 								'showSaveOption' => 'yes',
 							),
 			*/

		// yes: test mode.
 		// no:  live,
 			'test_mode'   => $this->get_setting( 'testmode' ),

		// yes: redirect the customer to the Hosted Payment Page.
 		// no:  credit card input will be rendered directly on the checkout page

 			'redirect'    => $this->get_setting( 'cc_mode' ),
	 		'logo'        => WC_Monei()->plugin_url() . '/assets/images/monei-logo.svg',
 			
 		);

 		if ( 'yes' === $this->get_setting( 'hide_logo' ) ) {

 			unset( $data['logo'] );

 		}

 		return $data;
 	}
 }
