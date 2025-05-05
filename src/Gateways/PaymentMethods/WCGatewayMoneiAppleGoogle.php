<?php

namespace Monei\Gateways\PaymentMethods;

use Monei\Features\Subscriptions\SubscriptionService;
use Monei\Services\ApiKeyService;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\PaymentMethodsService;
use Monei\Templates\TemplateManager;
use WC_Blocks_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles Monei Apple Google method based on CC
 *
 * Class MoneiAppleGoogleGateway
 */
class WCGatewayMoneiAppleGoogle extends WCGatewayMoneiCC {
	const PAYMENT_METHOD = 'card';

	/**
	 * @var bool
	 */
	protected $redirect_flow;

	/**
	 * @var bool
	 */
	protected $apple_google_pay;

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct(
		PaymentMethodsService $paymentMethodsService,
		TemplateManager $templateManager,
		ApiKeyService $apiKeyService,
		MoneiPaymentServices $moneiPaymentServices,
		SubscriptionService $subscriptionService
	) {
		parent::__construct( $paymentMethodsService, $templateManager, $apiKeyService, $moneiPaymentServices, $subscriptionService );
		$this->id           = 'monei_apple_google';
		$this->method_title = __( 'MONEI - Apple/Google', 'monei' );
		$this->title        = __( 'Google Pay', 'monei' );
		$this->description  = __( '&nbsp;', 'monei' );
		$iconUrl            = apply_filters( 'woocommerce_monei_icon', WC_Monei()->image_url( 'google-logo.svg' ) );
		$iconMarkup         = '<img src="' . $iconUrl . '" alt="MONEI" class="monei-icons" />';

		$this->icon     = ( $this->hide_logo ) ? '' : $iconMarkup;
		$this->settings = get_option( 'woocommerce_monei_settings', array() );
		$this->enabled  = ( ! empty( isset( $this->settings['apple_google_pay'] ) && 'yes' === $this->settings['apple_google_pay'] ) ) ? 'yes' : 'no';
        $this->supports = array(
            'products',
            'refunds',
        );
		add_filter(
			'woocommerce_available_payment_gateways',
			array( $this, 'hideAppleGoogleInCheckout' ),
			11,
			1
		);
	}

	/**
	 * Hide Apple/Google Pay in WooCommerce Checkout
	 */
	public function hideAppleGoogleInCheckout( $available_gateways ) {
		return $available_gateways;
	}

	public function isBlockCheckout(): bool {
		if ( ! is_checkout() ) {
			return false;
		}
		if ( ! class_exists( 'WC_Blocks_Utils' ) ) {
			return false;
		}
		// Check if the checkout block is present
		$has_block = WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' );

		// Additional check: see if the traditional checkout shortcode is present
		$has_shortcode = has_shortcode( get_post( wc_get_page_id( 'checkout' ) )->post_content, 'woocommerce_checkout' );

		// If the block is present and the shortcode is not, we can be more confident it's a block checkout
		return $has_block && ! $has_shortcode;
	}

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 *
	 * @param int $order_id
	 * @param null $allowed_payment_method
	 *
	 * @return array
	 */
	public function process_payment( $order_id, $allowed_payment_method = null ) {
		return parent::process_payment( $order_id, self::PAYMENT_METHOD );
	}

	/**
	 * Payments fields, shown on checkout or payment method page (add payment method).
	 */
	public function payment_fields() {
		ob_start();

		// Checkout screen.
		// We show description, if tokenization available, we show saved cards and checkbox to save.
		echo esc_html( $this->description );
		if ( $this->apple_google_pay ) {
			$this->render_google_pay_form();
		}

		ob_end_flush();
	}

	/**
	 * Form where Google or Apple Pay button will be rendered.
	 * https://docs.monei.com/docs/monei-js/payment-request/#2-add-payment-request-component-to-your-payment-page-client-side
	 */
	protected function render_google_pay_form() {
		?>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-payment-request-form" class="wc-payment-request-form"
					style="background:transparent; border:none;">
			<div id="payment-request-form">
				<div id="payment-request-container">
				</div>
			</div>
		</fieldset>
		<?php
	}
}

