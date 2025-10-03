<?php

namespace Monei\Gateways\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;

final class MoneiPaypalBlocksSupport extends AbstractPaymentMethodType {


	private $gateway;
	protected $name = 'monei_paypal';

	public function __construct( WCMoneiPaymentGateway $gateway ) {
		$this->gateway = $gateway;
	}

	public function initialize() {
		$this->settings = get_option( 'woocommerce_monei_paypal_settings', array() );
	}

	public function get_payment_method_script_handles() {
		// Order-pay page uses classic checkout, not blocks
		if ( is_checkout_pay_page() ) {
			return array();
		}

		$script_name = 'wc-monei-paypal-blocks-integration';

		wp_register_script(
			$script_name,
			WC_Monei()->plugin_url() . '/public/js/monei-block-checkout-paypal.min.js',
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

	public function is_active() {
		// Order-pay page always uses classic checkout
		if ( is_checkout_pay_page() ) {
			return false;
		}

		$id = $this->gateway->getAccountId() ?? false;

		$key = $this->gateway->getApiKey() ?? false;

		if ( ! $id || ! $key ) {
			return false;
		}

		return 'yes' === ( $this->get_setting( 'enabled' ) ?? 'no' );
	}

	public function get_payment_method_data() {
		$total         = WC()->cart !== null ? WC()->cart->get_total( false ) : 0;
		$paypal_style  = $this->get_setting( 'paypal_style' );
		$paypal_mode   = $this->get_setting( 'paypal_mode' );
		$redirect_flow = ( ! empty( $paypal_mode ) && 'yes' === $paypal_mode );

		if ( ! $paypal_style ) {
			$paypal_style = '{}';
		}
		$data = array(

			'title'        => $this->gateway->title,
			'logo'         => WC_Monei()->plugin_url() . '/public/images/paypal-logo.svg',
			'supports'     => $this->get_supported_features(),
			'currency'     => get_woocommerce_currency(),
			'total'        => $total,
			'language'     => locale_iso_639_1_code(),

			// yes: test mode.
			// no:  live,
			'test_mode'    => $this->gateway->getTestmode() ?? false,
			'accountId'    => $this->gateway->getAccountId() ?? false,
			'sessionId'    => wc()->session !== null ? wc()->session->get_customer_id() : '',
			'paypalStyle'  => json_decode( $paypal_style ),
			'redirectFlow' => $redirect_flow,
			'description'  => $this->get_setting( 'description' ),
		);

		$hide_logo = $this->get_setting( 'hide_logo' );
		if ( 'yes' === $hide_logo ) {
			unset( $data['logo'] );
		}

		return $data;
	}
}
