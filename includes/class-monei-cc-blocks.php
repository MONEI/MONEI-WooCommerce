<?php

 use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

 final class WC_Gateway_Monei_CC_Blocks extends AbstractPaymentMethodType {

 	private $gateway;
 	protected $name = 'monei';
 	private $profile_monitor;

 	public function initialize() {

 		$this->settings = get_option( 'woocommerce_monei_settings', array() );
 		$this->gateway  = new WC_Gateway_Monei_CC();

		add_filter( 'woocommerce_saved_payment_methods_list', [ $this, 'filter_saved_payment_methods_list' ], 10, 2 );

 	}


 	public function is_active() {

		$id  = $this->get_setting( 'accountid' ) ?? 'false';
		$key = $this->get_setting( 'apikey' ) ?? 'false';

		if ( ! $id || ! $key ) {
			return false;
		}

 		return 'yes' === ( $this->get_setting( 'enabled' ) ?? 'no' );
 	}


	/**
	 * Removes all saved payment methods when the setting to save cards is disabled.
	 *
	 * @param  array $list         List of payment methods passed from wc_get_customer_saved_methods_list().
	 * @param  int   $customer_id  The customer to fetch payment methods for.
	 * @return array               Filtered list of customers payment methods.
	 */
	public function filter_saved_payment_methods_list( $list, $customer_id ) {
		
 		if ( 'no' == $this->get_setting( 'tokenization' ) ) {
			return [];
		}
		return $list;
	}


 	public function get_payment_method_script_handles() {
        wp_register_script( 'monei', 'https://js.monei.com/v1/monei.js', '', '1.0', true );
        wp_enqueue_script( 'monei' );

        $script_name = 'wc-monei-cc-blocks-integration';

 		wp_register_script(
 			$script_name,
 			WC_Monei()->plugin_url(). '/public/js/checkout-cc.min.js',
 			array(
				'wc-blocks-checkout',
 				'wc-blocks-registry',
 				'wc-settings',
 				'wp-element',
 				'wp-html-entities',
 				'wp-i18n',
                'monei'
 			),
 			WC_Monei()->version,
 			true
 		);

 		if ( function_exists( 'wp_set_script_translations' ) ) {
 			wp_set_script_translations( $script_name );
 		}

 		return array( $script_name );
 	}


 	public function get_payment_method_data() {

 		if ( 'no' == $this->get_setting( 'tokenization' ) ) {
			$supports = $this->get_supported_features();
 		} else {
 			$supports = array_merge( [ 'showSavedCards', 'showSaveOption' ], $this->get_supported_features() );
 		}

 		$data = array(

 			'title'       => $this->gateway->title,
 			'description' => $this->gateway->description,
	 		'logo'        => WC_Monei()->plugin_url() . '/assets/images/monei-logo.svg',
 			'supports'    => $supports,

		// yes: test mode.
 		// no:  live,
 			'test_mode'=> $this->get_setting( 'testmode' ) ?? 'no',

		// yes: redirect the customer to the Hosted Payment Page.
 		// no:  credit card input will be rendered directly on the checkout page
 			'redirect' => $this->get_setting( 'cc_mode' ) ?? 'no',

		// yes: Can save credit card and use saved cards.
 		// no:  Cannot save/use
 			'tokenization' => $this->get_setting( 'tokenization' ) ?? 'no',
			'accountId' => $this->get_setting( 'accountid' ),
			'sessionId' => (wc()->session) ? wc()->session->get_customer_id() : '',
 		);

 		if ( 'yes' === $this->get_setting( 'hide_logo' ) ?? 'no' ) {

 			unset( $data['logo'] );

 		}

 		return $data;
 	}
 }
