<?php

namespace Monei\Gateways\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;
use Monei\Gateways\PaymentMethods\WCGatewayMoneiMultibanco;

final class MoneiMultibancoBlocksSupport extends AbstractPaymentMethodType {


	private $gateway;
	protected $name = 'monei_multibanco';

	public function __construct( WCMoneiPaymentGateway $gateway ) {
		$this->gateway = $gateway;
	}
	public function initialize() {
		$this->settings = get_option( 'woocommerce_monei_multibanco_settings', array() );
	}

	public function get_payment_method_script_handles() {

		$script_name = 'wc-monei-multibanco-blocks-integration';

		wp_register_script(
			$script_name,
			WC_Monei()->plugin_url() . '/public/js/monei-block-checkout-multibanco.min.js',
			array(
				'wc-blocks-checkout',
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
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

		$id = $this->gateway->getAccountId() ?? false;

		$key = $this->gateway->getApiKey() ?? false;

		if ( ! $id || ! $key ) {
			return false;
		}

		return 'yes' === ( $this->get_setting( 'enabled' ) ?? 'no' );
	}

	public function get_payment_method_data() {
		$total = WC()->cart !== null ? WC()->cart->get_total( false ) : 0;
		$data  = array(

			'title'       => $this->gateway->title,
			'description' => $this->gateway->description,
			'logo'        => WC_Monei()->plugin_url() . '/public/images/multibanco-logo.svg',
			'supports'    => $this->get_supported_features(),
			'currency'    => get_woocommerce_currency(),
			'total'       => $total,
			'language'    => locale_iso_639_1_code(),

			// yes: test mode.
			// no:  live,
			'test_mode'   => $this->gateway->getTestmode() ?? false,
			'accountId'   => $this->gateway->getAccountId() ?? false,
			'sessionId'   => wc()->session !== null ? wc()->session->get_customer_id() : '',
		);

		$hide_logo = $this->get_setting( 'hide_logo' ) ?? 'no';
		if ( 'yes' === $hide_logo ) {

			unset( $data['logo'] );

		}

		return $data;
	}
}
