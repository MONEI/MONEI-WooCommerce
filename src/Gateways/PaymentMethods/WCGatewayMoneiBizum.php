<?php

namespace Monei\Gateways\PaymentMethods;

use Monei\Gateways\Abstracts\WCMoneiPaymentGatewayHosted;
use Monei\Services\ApiKeyService;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\PaymentMethodsService;
use Monei\Templates\TemplateManager;
use WC_Monei_IPN;
use WC_Monei_Payment_Gateway_Hosted;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handle Monei Bizum Payment method.
 *
 * Class WC_Gateway_Monei_Bizum
 */
class WCGatewayMoneiBizum extends WCMoneiPaymentGatewayHosted {


	const PAYMENT_METHOD = 'bizum';

	/**
	 * @var bool
	 */
	protected $redirect_flow;

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
		MoneiPaymentServices $moneiPaymentServices
	) {
		parent::__construct( $paymentMethodsService, $templateManager, $apiKeyService, $moneiPaymentServices );

		$this->id                 = MONEI_GATEWAY_ID . '_bizum';
		$this->method_title       = __( 'MONEI - Bizum', 'monei' );
		$this->method_description = __( 'Accept Bizum payments.', 'monei' );
		$this->enabled            = ( ! empty( $this->get_option( 'enabled' ) && 'yes' === $this->get_option( 'enabled' ) ) && $this->is_valid_for_use() ) ? 'yes' : false;

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		// Bizum Hosted payment with redirect.
		$this->has_fields = false;
		$iconUrl          = apply_filters( 'woocommerce_monei_bizum_icon', WC_Monei()->image_url( 'bizum-logo.svg' ) );
		$iconMarkup       = '<img src="' . $iconUrl . '" alt="MONEI" class="monei-icons" />';
		// Settings variable
		$this->hide_logo     = ( ! empty( $this->get_option( 'hide_logo' ) && 'yes' === $this->get_option( 'hide_logo' ) ) ) ? true : false;
		$this->icon          = ( $this->hide_logo ) ? '' : $iconMarkup;
		$this->redirect_flow = ( ! empty( $this->get_option( 'bizum_mode' ) && 'yes' === $this->get_option( 'bizum_mode' ) ) ) ? true : false;
		$this->testmode      = $this->getTestmode();
		$hide_title          = ( ! empty( $this->get_option( 'hide_title' ) && 'yes' === $this->get_option( 'hide_title' ) ) ) ? true : false;
		$this->title         = ( ! $hide_title && ! empty( $this->get_option( 'title' ) ) ) ? $this->get_option( 'title' ) : '';
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
				return $this->checks_before_save( $is_post, 'woocommerce_monei_bizum_enabled' );
			}
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'bizum_scripts' ) );
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
		$this->form_fields = require WC_Monei()->plugin_path() . '/includes/admin/monei-bizum-settings.php';
	}

	/**
	 * Validate bizum_style field
	 *
	 * @param string $key
	 * @param string $value
	 * @return string
	 */
	public function validate_bizum_style_field( $key, $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		// WordPress adds slashes to $_POST data, we need to remove them before validating JSON
		$value = stripslashes( $value );

		// Try to decode JSON
		$decoded = json_decode( $value, true );

		// Check for JSON errors
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			\WC_Admin_Settings::add_error(
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Bizum Style field contains invalid JSON: %s', 'monei' ),
					json_last_error_msg()
				)
			);
			return $this->get_option( 'bizum_style', '{"height": "50px"}' );
		}

		// Re-encode with pretty print for better readability in admin
		return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
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

	/**
	 * Frontend MONEI payment-request token generated when Bizum.
	 *
	 * @return false|string
	 */
	protected function get_frontend_generated_token() {
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return ( isset( $_POST['monei_payment_request_token'] ) ) ? wc_clean( wp_unslash( $_POST['monei_payment_request_token'] ) ) : false; // WPCS: CSRF ok.
	}

	public function payment_fields() {
		// Show description only in redirect mode
		if ( $this->redirect_flow && $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}
		// Only render Bizum button if not using redirect flow
		if ( ! $this->redirect_flow ) {
			echo '<fieldset id="monei-bizum-form" class="monei-fieldset monei-payment-request-fieldset">
					<div
						id="bizum-container"
						class="monei-payment-request-container"
	                        >
					</div>
				</fieldset>';
		}
	}

	public function bizum_scripts() {
		if ( ! is_checkout() ) {
			return;
		}
		if ( 'no' === $this->enabled ) {
			return;
		}
		// Don't enqueue scripts if using redirect flow
		if ( $this->redirect_flow ) {
			return;
		}
		if ( ! wp_script_is( 'monei', 'registered' ) ) {
			wp_register_script( 'monei', 'https://js.monei.com/v2/monei.js', '', '1.0', true );
		}
		if ( ! wp_script_is( 'monei', 'enqueued' ) ) {
			wp_enqueue_script( 'monei' );
		}
		wp_register_script(
			'woocommerce_monei-bizum',
			plugins_url( 'public/js/monei-bizum-classic.min.js', MONEI_MAIN_FILE ),
			array(
				'jquery',
				'monei',
			),
			MONEI_VERSION,
			true
		);
		wp_enqueue_script( 'woocommerce_monei-bizum' );

		// Determine the total amount to be passed
		$total       = $this->determineTheTotalAmountToBePassed();
		$bizum_style = $this->get_option( 'bizum_style', '{}' );

		wp_localize_script(
			'woocommerce_monei-bizum',
			'wc_bizum_params',
			array(
				'account_id'  => $this->getAccountId(),
				'session_id'  => WC()->session->get_customer_id(),
				'total'       => monei_price_format( $total ),
				'currency'    => get_woocommerce_currency(),
				'language'    => locale_iso_639_1_code(),
				'bizum_style' => json_decode( $bizum_style ),
			)
		);
	}
}
