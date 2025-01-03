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

        $id  = $this->gateway->getAccountId() ?? false;
        $key = $this->gateway->getApiKey() ?? false;

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
 			WC_Monei()->plugin_url(). '/public/js/monei-block-checkout-cc.min.js',
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
            $supports = array(
                'features' => $this->get_supported_features(),
                'showSavedCards' => true,
                'showSaveOption' => true,
            ); 		}
        $total = isset(WC()->cart) ? WC()->cart->get_total( false ) : 0;
 		$data = array(
 			'title'       => $this->gateway->title,
 			'description' => $this->gateway->description === '&nbsp;' ? '' : $this->gateway->description,
	 		'logo'        => WC_Monei()->plugin_url() . '/public/images/monei-cards.svg',
            'logo_apple' => WC_Monei()->plugin_url() . '/public/images/apple-logo.svg',
            'logo_google' => WC_Monei()->plugin_url() . '/public/images/google-logo.svg',
 			'supports'    => $supports,
            'cardholderName' => esc_attr__('Cardholder Name', 'monei'),
            'nameErrorString' => esc_html__('Please enter a valid name. Special characters are not allowed.', 'monei'),
            'cardErrorString' => esc_html__('Please check your card details.', 'monei'),
            'tokenErrorString' => esc_html__('MONEI token could not be generated.', 'monei'),
            'redirected' => esc_html__('You will be redirected to the payment page', 'monei'),

		// yes: test mode.
 		// no:  live,
 			'test_mode'=> $this->gateway->getTestmode(),

		// yes: redirect the customer to the Hosted Payment Page.
 		// no:  credit card input will be rendered directly on the checkout page
 			'redirect' => $this->get_setting( 'cc_mode' ) ?? 'no',


		// yes: Can save credit card and use saved cards.
 		// no:  Cannot save/use
 			'tokenization' => $this->get_setting( 'tokenization' ) ?? 'no',
			'accountId' => $this->gateway->getAccountId() ?? false,
			'sessionId' => (wc()->session) ? wc()->session->get_customer_id() : '',
            'currency' => get_woocommerce_currency(),
            'total' => $total,
            'appleGooglePay' => $this->get_setting('apple_google_pay') ?? 'no',
            'language' => locale_iso_639_1_code()
 		);

 		if ( 'yes' === $this->get_setting( 'hide_logo' ) ?? 'no' ) {

 			unset( $data['logo'] );
            unset( $data['logo_apple_google'] );

 		}

 		return $data;
 	}
 }
