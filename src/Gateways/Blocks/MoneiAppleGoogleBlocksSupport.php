<?php

namespace Monei\Gateways\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;
use Monei\Gateways\PaymentMethods\WCGatewayMoneiAppleGoogle;

final class MoneiAppleGoogleBlocksSupport extends AbstractPaymentMethodType {

	protected $name = 'monei_apple_google';
	/**
	 * @var WCGatewayMoneiAppleGoogle
	 */
	public WCMoneiPaymentGateway $gateway;

	/**
	 * @param WCGatewayMoneiAppleGoogle $gateway
	 */
	public function __construct( WCMoneiPaymentGateway $gateway ) {
		$this->gateway = $gateway;
	}

	public function initialize() {
        $this->settings = get_option( 'woocommerce_monei_apple_google_settings', array() );
    }


	public function is_active() {
        $id = $this->gateway->getAccountId() ?? false;

        $key = $this->gateway->getApiKey() ?? false;

        if ( ! $id || ! $key ) {
            return false;
        }

        return 'yes' === ( $this->get_setting( 'enabled' ) ?? 'no' );
	}


    public function get_payment_method_script_handles() {
        wp_register_script( 'monei', 'https://js.monei.com/v2/monei.js', '', '2.0', true );
        wp_enqueue_script( 'monei' );

        $script_name = 'wc-monei-apple-google-blocks-integration';

        wp_register_script(
            $script_name,
            WC_Monei()->plugin_url() . '/public/js/monei-block-checkout-apple-google.min.js',
            array(
                'wc-blocks-checkout',
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
                'monei',
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
        $supports = $this->gateway->supports;
        $total                 = WC()->cart !== null ? WC()->cart->get_total( false ) : 0;
        $isGoogleEnabled       = $this->gateway->isGoogleAvailable();
        $isAppleEnabled        = $this->gateway->isAppleAvailable();
        $logoApple             = WC_Monei()->plugin_url() . '/public/images/apple-logo.svg';
        $logoGoogle            = WC_Monei()->plugin_url() . '/public/images/google-logo.svg';
        $payment_request_style = $this->get_setting( 'payment_request_style' ) ?? '{"height": "42"}';
        $data                  = array(
            'title'                 => $this->gateway->title,
            'description'           => $this->gateway->description === '&nbsp;' ? '' : $this->gateway->description,
            'logo_google'           => $isGoogleEnabled ? $logoGoogle : false,
            'logo_apple'            => $isAppleEnabled ? $logoApple : false,
            'supports'              => $supports,

            // yes: test mode.
            // no:  live,
            'test_mode'             => $this->gateway->getTestmode(),
            'accountId'             => $this->gateway->getAccountId() ?? false,
            'sessionId'             => wc()->session !== null ? wc()->session->get_customer_id() : '',
            'currency'              => get_woocommerce_currency(),
            'total'                 => $total,
            'language'              => locale_iso_639_1_code(),
            'paymentRequestStyle'   => json_decode( $payment_request_style ),
        );

        return $data;
    }
}
