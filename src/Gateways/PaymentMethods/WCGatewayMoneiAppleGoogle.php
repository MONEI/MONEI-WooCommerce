<?php

namespace Monei\Gateways\PaymentMethods;

use Monei\Features\Subscriptions\SubscriptionService;
use Monei\Gateways\Abstracts\WCMoneiPaymentGatewayComponent;
use Monei\Services\ApiKeyService;
use Monei\Services\MoneiStatusCodeHandler;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\PaymentMethodsService;
use Monei\Templates\TemplateManager;
use WC_Blocks_Utils;
use WC_Monei_IPN;
use WC_Admin_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles Monei Apple Google method based on CC
 *
 * Class MoneiAppleGoogleGateway
 */
class WCGatewayMoneiAppleGoogle extends WCMoneiPaymentGatewayComponent {
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
	 * @var bool
	 */
	protected static $scripts_enqueued = false;

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @param PaymentMethodsService $paymentMethodsService
	 * @param TemplateManager $templateManager
	 * @param ApiKeyService $apiKeyService
	 * @param MoneiPaymentServices $moneiPaymentServices
	 * @param SubscriptionService $subscriptionService Injected by DI container but not used (Apple/Google Pay doesn't support subscriptions)
	 * @return void
	 * @phpstan-ignore-next-line
	 */
	public function __construct(
		PaymentMethodsService $paymentMethodsService,
		TemplateManager $templateManager,
		ApiKeyService $apiKeyService,
		MoneiPaymentServices $moneiPaymentServices,
		MoneiStatusCodeHandler $statusCodeHandler,
		SubscriptionService $subscriptionService
	) {
		parent::__construct( $paymentMethodsService, $templateManager, $apiKeyService, $moneiPaymentServices, $statusCodeHandler );
		$this->id                 = 'monei_apple_google';
		$this->method_title       = __( 'MONEI - Apple Pay / Google Pay', 'monei' );
		$this->method_description = __( 'Accept Apple Pay and Google Pay payments.', 'monei' );
		$hide_title               = ( ! empty( $this->get_option( 'hide_title' ) ) && 'yes' === $this->get_option( 'hide_title' ) ) ? true : false;
		$default_title            = __( 'Apple Pay / Google Pay', 'monei' );
		$saved_title              = $this->get_option( 'title' );
		$this->title              = $hide_title ? '' : ( ! empty( $saved_title ) ? $saved_title : $default_title );
		$this->hide_logo          = ( ! empty( $this->get_option( 'hide_logo' ) ) && 'yes' === $this->get_option( 'hide_logo' ) ) ? true : false;
		$this->description        = ( ! empty( $this->get_option( 'description' ) ) ) ? $this->get_option( 'description' ) : '';
		$iconUrl                  = apply_filters( 'woocommerce_monei_icon', WC_Monei()->image_url( 'google-logo.svg' ) );
		$iconMarkup               = '<img src="' . $iconUrl . '" alt="MONEI" class="monei-icons" />';
		$this->testmode           = $this->getTestmode();
		if ( $this->testmode && ! empty( $this->title ) ) {
			$this->title .= ' (' . __( 'Test Mode', 'monei' ) . ')';
		}
		$this->icon          = ( $this->hide_logo ) ? '' : $iconMarkup;
		$this->settings      = get_option( 'woocommerce_monei_apple_google_settings', array() );
		$this->enabled       = ( ! empty( $this->get_option( 'enabled' ) ) && 'yes' === $this->get_option( 'enabled' ) && $this->is_valid_for_use() ) ? 'yes' : false;
		$this->account_id    = $this->getAccountId();
		$this->api_key       = $this->getApiKey();
		$this->shop_name     = get_bloginfo( 'name' );
		$this->redirect_flow = false;
		$this->tokenization  = false;
		$this->pre_auth      = false;
		$this->logging       = ( ! empty( get_option( 'monei_debug' ) ) && 'yes' === get_option( 'monei_debug' ) ) ? true : false;
		$this->supports      = array(
			'products',
			'refunds',
		);
		$this->notify_url    = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN( $this->logging );
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_action( 'wp_enqueue_scripts', array( $this, 'monei_scripts' ) );
		add_filter(
			'woocommerce_available_payment_gateways',
			array( $this, 'hideAppleGoogleInCheckout' ),
			11,
			1
		);
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 * @since 5.0
	 */
	public function init_form_fields() {
		$this->form_fields = require WC_Monei()->plugin_path() . '/includes/admin/monei-apple-google-settings.php';
	}

	/**
	 * Validate payment_request_style field
	 *
	 * @param string $key
	 * @param string $value
	 * @return string
	 */
	public function validate_payment_request_style_field( $key, $value ) {
		if ( empty( $value ) ) {
			return $value;
		}

		// WordPress adds slashes to $_POST data, we need to remove them before validating JSON
		$value = stripslashes( $value );

		// Try to decode JSON
		$decoded = json_decode( $value, true );

		// Check for JSON errors
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			WC_Admin_Settings::add_error(
				sprintf(
					/* translators: %s: JSON error message */
					__( 'Apple Pay / Google Pay Style field contains invalid JSON: %s', 'monei' ),
					json_last_error_msg()
				)
			);
			return $this->get_option( 'payment_request_style', '{"height": "50px"}' );
		}

		// Re-encode with pretty print for better readability in admin
		return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Registering MONEI JS library and plugin js.
	 */
	public function monei_scripts() {

		if ( 'no' === $this->enabled ) {
			return;
		}
		if ( self::$scripts_enqueued || ! $this->should_load_scripts() ) {
			return;
		}

		// Don't load classic CSS on blocks checkout
		if ( $this->is_block_checkout_page() ) {
			return;
		}

		// Register and enqueue classic checkout CSS
		wp_register_style(
			'monei-classic-checkout',
			plugins_url( 'public/css/monei-classic-checkout.css', MONEI_MAIN_FILE ),
			array(),
			MONEI_VERSION,
			'all'
		);
		wp_enqueue_style( 'monei-classic-checkout' );

		if ( ! wp_script_is( 'monei', 'registered' ) ) {
			wp_register_script( 'monei', 'https://js.monei.com/v2/monei.js', '', '1.0', true );

		}
		wp_register_script(
			'woocommerce_monei_apple_google',
			plugins_url( 'public/js/monei-apple-google-classic.min.js', MONEI_MAIN_FILE ),
			array(
				'jquery',
				'monei',
			),
			MONEI_VERSION,
			true
		);
		wp_enqueue_script( 'monei' );
		// Determine the total amount to be passed
		$total                 = $this->determineTheTotalAmountToBePassed();
		$payment_request_style = $this->get_option( 'payment_request_style', '{"height": "50px"}' );

		// Get separate titles with fallback to defaults
		$apple_pay_title  = $this->get_option( 'apple_pay_title', __( 'Apple Pay', 'monei' ) );
		$google_pay_title = $this->get_option( 'google_pay_title', __( 'Google Pay', 'monei' ) );

		// Use specific titles if set, otherwise fall back to defaults
		$apple_pay_title  = ! empty( $apple_pay_title ) ? $apple_pay_title : __( 'Apple Pay', 'monei' );
		$google_pay_title = ! empty( $google_pay_title ) ? $google_pay_title : __( 'Google Pay', 'monei' );

		// Add test mode suffix if enabled
		if ( $this->testmode ) {
			$test_suffix       = ' (' . __( 'Test Mode', 'monei' ) . ')';
			$apple_pay_title  .= $test_suffix;
			$google_pay_title .= $test_suffix;
		}

		wp_localize_script(
			'woocommerce_monei_apple_google',
			'wc_monei_apple_google_params',
			array(
				'accountId'           => $this->getAccountId(),
				'sessionId'           => WC()->session->get_customer_id(),
				'total'               => monei_price_format( $total ),
				'currency'            => get_woocommerce_currency(),
				'appleLogo'           => WC_Monei()->image_url( 'apple-logo.svg' ),
				'applePayTitle'       => $apple_pay_title,
				'googlePayTitle'      => $google_pay_title,
				'paymentRequestStyle' => json_decode( $payment_request_style ),
			)
		);

		wp_enqueue_script( 'woocommerce_monei_apple_google' );
		self::$scripts_enqueued = true;
	}


	/**
	 * Hide Apple/Google Pay in WooCommerce Checkout
	 */
	public function hideAppleGoogleInCheckout( $available_gateways ) {
		return $available_gateways;
	}

	public function isBlockCheckout(): bool {
		// Order-pay and add payment method pages are always classic
		if ( is_checkout_pay_page() || is_add_payment_method_page() ) {
			return false;
		}
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
	 * Check if gateway has fields.
	 * @return bool
	 */
	public function has_fields() {
		return true;
	}

	/**
	 * Payments fields, shown on checkout or payment method page (add payment method).
	 */
	public function payment_fields() {
		ob_start();
		// Show description only in redirect mode
		if ( $this->redirect_flow && $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}
		$this->render_google_pay_form();
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

	public function isGoogleAvailable() {
		$googleInAPI = $this->paymentMethodsService->isGoogleEnabled();
		$googleInWoo = $this->enabled;
		return $googleInAPI && $googleInWoo;
	}

	public function isAppleAvailable() {
		$appleInAPI = $this->paymentMethodsService->isAppleEnabled();
		$appleInWoo = $this->enabled;
		return $appleInAPI && $appleInWoo;
	}

	protected function should_load_scripts() {
		return is_checkout() || is_checkout_pay_page();
	}
}

