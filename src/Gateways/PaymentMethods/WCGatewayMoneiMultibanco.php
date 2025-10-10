<?php

namespace Monei\Gateways\PaymentMethods;

use Monei\Gateways\Abstracts\WCMoneiPaymentGatewayHosted;
use Monei\Services\ApiKeyService;
use Monei\Services\MoneiStatusCodeHandler;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\PaymentMethodsService;
use Monei\Templates\TemplateManager;
use WC_Monei_IPN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handle Monei Bizum Payment method.
 *
 * Class WC_Gateway_Monei_Bizum
 */
class WCGatewayMoneiMultibanco extends WCMoneiPaymentGatewayHosted {


	const PAYMENT_METHOD = 'multibanco';

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
		MoneiStatusCodeHandler $statusCodeHandler
	) {
		parent::__construct( $paymentMethodsService, $templateManager, $apiKeyService, $moneiPaymentServices, $statusCodeHandler );

		$this->id                 = MONEI_GATEWAY_ID . '_multibanco';
		$this->method_title       = __( 'MONEI - Multibanco', 'monei' );
		$this->method_description = __( 'Accept Multibanco payments.', 'monei' );
		$this->enabled            = ( 'yes' === $this->get_option( 'enabled' ) && $this->is_valid_for_use() ) ? 'yes' : 'no';

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		// Bizum Hosted payment with redirect.
		$this->has_fields = false;
		$iconUrl          = apply_filters( 'woocommerce_monei_multibanco_icon', WC_Monei()->image_url( 'multibanco-logo.svg' ) );
		$iconMarkup       = '<img src="' . $iconUrl . '" alt="MONEI" class="monei-icons-multi" />';
		// Settings variable
		$this->hide_logo = ( ! empty( $this->get_option( 'hide_logo' ) ) && 'yes' === $this->get_option( 'hide_logo' ) ) ? true : false;
		$this->icon      = ( $this->hide_logo ) ? '' : $iconMarkup;
		$this->testmode  = $this->getTestmode();
		$hide_title      = ( ! empty( $this->get_option( 'hide_title' ) ) && 'yes' === $this->get_option( 'hide_title' ) ) ? true : false;
		$this->title     = ( ! $hide_title && ! empty( $this->get_option( 'title' ) ) ) ? $this->get_option( 'title' ) : '';
		if ( $this->testmode && ! empty( $this->title ) ) {
			$this->title .= ' (' . __( 'Test Mode', 'monei' ) . ')';
		}
		$this->description = ( ! empty( $this->get_option( 'description' ) ) ) ? $this->get_option( 'description' ) : '';
		// Backward compatible: try local setting first, then global setting
		$local_orderdo              = $this->get_option( 'orderdo' );
		$this->status_after_payment = ! empty( $local_orderdo ) ? $local_orderdo : get_option( 'monei_orderdo', 'processing' );
		$this->api_key              = $this->getApiKey();
		$this->account_id           = $this->getAccountId();
		$this->shop_name            = get_bloginfo( 'name' );
		$this->logging              = ( ! empty( get_option( 'monei_debug' ) ) && 'yes' === get_option( 'monei_debug' ) ) ? true : false;

		// IPN callbacks
		$this->notify_url = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN( $this->logging );

		$this->supports = array(
			'products',
			'refunds',
		);

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter(
			'woocommerce_save_settings_checkout_' . $this->id,
			function ( $is_post ) {
				return $this->checks_before_save( $is_post, 'woocommerce_monei_multibanco_enabled' );
			}
		);
	}

	/**
	 * Return whether or not this gateway still requires setup to function.
	 *
	 * When this gateway is toggled on via AJAX, if this returns true a
	 * redirect will occur to the settings page instead.
	 *
	 * @return bool
	 * @since 3.4.0
	 */
	public function needs_setup() {

		if ( ! $this->account_id || ! $this->api_key ) {
			return true;
		}

		return false;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 * @since 5.0
	 */
	public function init_form_fields() {
		$this->form_fields = require WC_Monei()->plugin_path() . '/includes/admin/monei-multibanco-settings.php';
	}

	/**
	 * Process the payment and return the result
	 *
	 * @access public
	 * @param int $order_id
	 * @param null|string $allowed_payment_method
	 * @return array
	 */
	public function process_payment( $order_id, $allowed_payment_method = null ) {
		return parent::process_payment( $order_id, self::PAYMENT_METHOD );
	}
}
