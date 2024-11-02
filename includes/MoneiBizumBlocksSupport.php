<?php

 use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

 final class MoneiBizumBlocksSupport extends AbstractPaymentMethodType {

 	private $gateway;
 	protected $name = 'monei_bizum';

 	public function initialize() {
 		$this->settings = get_option( 'woocommerce_monei_bizum_settings', array() );
 		$this->gateway  = new WC_Gateway_Monei_Bizum();
 	}
 	public function get_payment_method_script_handles() {

        $script_name = 'wc-monei-bizum-blocks-integration';

 		wp_register_script(
 			$script_name,
 			WC_Monei()->plugin_url(). '/public/js/monei-block-checkout-bizum.min.js',
 			array(
				'wc-blocks-checkout',
 				'wc-blocks-registry',
 				'wc-settings',
 				'wp-element',
 				'wp-html-entities',
 				'wp-i18n'
 			),
 			WC_Monei()->version,
 			true
 		);

 		if ( function_exists( 'wp_set_script_translations' ) ) {
 			wp_set_script_translations( $script_name );
 		}

 		return array( $script_name );
 	}

     public function is_active() {

         $id  = $this->gateway->getAccountId() ?? false;;
         $key = $this->gateway->getApiKey() ?? false;

         if ( ! $id || ! $key ) {
             return false;
         }

         return 'yes' === ( $this->get_setting( 'enabled' ) ?? 'no' );
     }

 	public function get_payment_method_data() {
        $total = isset(WC()->cart) ? WC()->cart->get_total( false ) : 0;
         $data = array(

 			'title'       => $this->gateway->title,
 			'description' => $this->gateway->description,
	 		'logo'        => WC_Monei()->plugin_url() . '/assets/images/bizum-logo.svg',
 			'supports'    => $this->get_supported_features(),
             'currency' => get_woocommerce_currency(),
             'total' => $total,
             'language' => locale_iso_639_1_code(),

		// yes: test mode.
 		// no:  live,
 			'test_mode'=> $this->gateway->getTestmode() ?? false,
			'accountId' => $this->gateway->getAccountId() ?? false,
			'sessionId' => (wc()->session) ? wc()->session->get_customer_id() : '',
 		);

 		if ( 'yes' === $this->get_setting( 'hide_logo' ) ?? 'no' ) {

 			unset( $data['logo'] );

 		}

 		return $data;
 	}
 }
