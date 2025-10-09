<?php

namespace Monei\Gateways\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;
use Monei\Gateways\PaymentMethods\WCGatewayMoneiCC;
use Monei\Helpers\CardBrandHelper;

final class MoneiCCBlocksSupport extends AbstractPaymentMethodType {
	private $gateway;
	protected $name = 'monei';
	private CardBrandHelper $cardBrandHelper;

	public function __construct( WCMoneiPaymentGateway $gateway, CardBrandHelper $cardBrandHelper ) {
		$this->gateway         = $gateway;
		$this->cardBrandHelper = $cardBrandHelper;
	}

	public function initialize() {
		$this->settings = get_option( 'woocommerce_monei_settings', array() );
		add_filter( 'woocommerce_saved_payment_methods_list', array( $this, 'filter_saved_payment_methods_list' ), 10, 2 );
	}


	public function is_active() {
		// Order-pay page always uses classic checkout
		if ( is_checkout_pay_page() ) {
			return false;
		}

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
	 * @param array $paymentMethods List of payment methods passed from wc_get_customer_saved_methods_list().
	 * @param int $customer_id The customer to fetch payment methods for.
	 * @return array               Filtered list of customers payment methods.
	 */
	public function filter_saved_payment_methods_list( $paymentMethods, $customer_id ) {
		if ( 'no' === $this->get_setting( 'tokenization' ) ) {
			return array();
		}
		return $paymentMethods;
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

		wp_register_script( 'monei', 'https://js.monei.com/v2/monei.js', '', '2.0', true );
		wp_enqueue_script( 'monei' );

		$script_name = 'wc-monei-cc-blocks-integration';

		wp_register_script(
			$script_name,
			WC_Monei()->plugin_url() . '/public/js/monei-block-checkout-cc.min.js',
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
		if ( 'no' === $this->get_setting( 'tokenization' ) ) {
			$supports = $this->gateway->supports;
		} else {
			$supports = array(
				'features'       => $this->gateway->supports,
				'showSavedCards' => true,
				'showSaveOption' => true,
			);
		}
		$total            = WC()->cart->get_total( false );
		$card_input_style = $this->get_setting( 'card_input_style' );
		if ( ! $card_input_style ) {
			$card_input_style = '{"base": {"height": "50"}, "input": {"background": "none"}}';
		}

		$redirect_mode = $this->get_setting( 'cc_mode' ) ?? 'no';
		$description   = '';
		if ( 'yes' === $redirect_mode && $this->gateway->description !== '&nbsp;' ) {
			$description = $this->gateway->description;
		}

		$data = array(
			'title'            => $this->gateway->title,
			'description'      => $description,
			'logo'             => WC_Monei()->plugin_url() . '/public/images/monei-cards.svg',
			'cardholderName'   => esc_attr__( 'Cardholder Name', 'monei' ),
			'nameErrorString'  => esc_html__( 'Please enter a valid name. Special characters are not allowed.', 'monei' ),
			'cardErrorString'  => esc_html__( 'Please check your card details.', 'monei' ),
			'tokenErrorString' => esc_html__( 'MONEI token could not be generated.', 'monei' ),
			'redirected'       => esc_html__( 'You will be redirected to the payment page', 'monei' ),
			'supports'         => $supports,

			// yes: test mode.
			// no:  live,
			'testMode'         => $this->gateway->getTestmode(),

			// yes: redirect the customer to the Hosted Payment Page.
			// no:  credit card input will be rendered directly on the checkout page
			'redirect'         => $redirect_mode,

			// yes: Can save credit card and use saved cards.
			// no:  Cannot save/use
			'tokenization'     => $this->get_setting( 'tokenization' ) ?? 'no',
			'accountId'        => $this->gateway->getAccountId() ?? false,
			'sessionId'        => wc()->session !== null ? wc()->session->get_customer_id() : '',
			'currency'         => get_woocommerce_currency(),
			'total'            => $total,
			'language'         => locale_iso_639_1_code(),
			'cardInputStyle'   => json_decode( $card_input_style ),
			'cardBrands'       => $this->cardBrandHelper->getCardBrandsConfig(),
		);

		if ( 'yes' === $this->get_setting( 'hide_logo' ) ) {
			unset( $data['logo'] );
		}

		// Remove logo when card brands are available
		if ( ! empty( $data['cardBrands'] ) && count( array_filter( $data['cardBrands'], fn( $b ) => $b['title'] !== 'Card' ) ) > 0 ) {
			unset( $data['logo'] );
		}

		return $data;
	}
}
