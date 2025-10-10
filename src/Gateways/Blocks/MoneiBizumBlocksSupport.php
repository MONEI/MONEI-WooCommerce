<?php

namespace Monei\Gateways\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Monei\Features\Subscriptions\SubscriptionService;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;
use Monei\Gateways\PaymentMethods\WCGatewayMoneiBizum;

final class MoneiBizumBlocksSupport extends AbstractPaymentMethodType {

	private $gateway;
	protected $name = 'monei_bizum';
	protected $handler;
	protected SubscriptionService $subscriptions_service;

	public function __construct( WCMoneiPaymentGateway $gateway, SubscriptionService $subscriptionService ) {
		$this->gateway               = $gateway;
		$this->subscriptions_service = $subscriptionService;
		$this->handler               = $this->subscriptions_service->getHandler();
	}

	public function initialize() {
		$this->settings = get_option( 'woocommerce_monei_bizum_settings', array() );
	}

	public function get_payment_method_script_handles() {
		// Order-pay page uses classic checkout, not blocks
		if ( is_checkout_pay_page() ) {
			return array();
		}

		// Register and enqueue blocks checkout CSS
		wp_register_style(
			'monei-blocks-checkout',
			WC_Monei()->plugin_url() . '/public/css/monei-blocks-checkout.css',
			array(),
			WC_Monei()->version,
			'all'
		);
		wp_enqueue_style( 'monei-blocks-checkout' );

		$script_name = 'wc-monei-bizum-blocks-integration';

		wp_register_script(
			$script_name,
			WC_Monei()->plugin_url() . '/public/js/monei-block-checkout-bizum.min.js',
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
		$total                 = WC()->cart !== null ? WC()->cart->get_total( false ) : 0;
		$cart_has_subscription = $this->handler ? $this->handler->cart_has_subscription() : false;
		$bizum_style           = $this->get_setting( 'bizum_style' );
		$bizum_mode            = $this->get_setting( 'mode' );
		$redirect_flow         = ( ! empty( $bizum_mode ) && 'yes' === $bizum_mode );

		if ( ! $bizum_style ) {
			$bizum_style = '{}';
		}
		$data = array(
			'title'               => $this->gateway->title,
			'logo'                => WC_Monei()->plugin_url() . '/public/images/bizum-logo.svg',
			'supports'            => $this->get_supported_features(),
			'currency'            => get_woocommerce_currency(),
			'total'               => $total,
			'language'            => locale_iso_639_1_code(),
			// yes: test mode.
			// no:  live,
			'testMode'            => $this->gateway->getTestmode() ?? false,
			'accountId'           => $this->gateway->getAccountId() ?? false,
			'sessionId'           => WC()->session !== null ? WC()->session->get_customer_id() : '',
			'cartHasSubscription' => $cart_has_subscription,
			'bizumStyle'          => json_decode( $bizum_style ),
			'redirectFlow'        => $redirect_flow,
			'description'         => $this->get_setting( 'description' ),
		);

		$hide_logo = $this->get_setting( 'hide_logo' );
		if ( 'yes' === $hide_logo ) {
			unset( $data['logo'] );
		}

		return $data;
	}
}
