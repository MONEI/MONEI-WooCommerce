<?php

namespace Monei\Gateways\PaymentMethods;

use Exception;
use Monei\Features\Subscriptions\SubscriptionHandlerInterface;
use Monei\Features\Subscriptions\SubscriptionService;
use Monei\Gateways\Abstracts\WCMoneiPaymentGatewayComponent;
use Monei\Helpers\CardBrandHelper;
use Monei\Services\ApiKeyService;
use Monei\Services\payment\MoneiPaymentServices;
use Monei\Services\PaymentMethodsService;
use Monei\Templates\TemplateManager;
use WC_Geolocation;
use WC_Monei_IPN;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handle Monei Payment method by default.
 * It will combine HOSTED and Component versions.
 * Merchant can decide with a checkbox in settings screen.
 * We keep MONEI_GATEWAY_ID to have compatibility with old merchants ( with hosted cc selected )
 *
 * For Hosted, see:
 * Form based: This is where the user must click a button on a form that then redirects them to the payment processor on the gateway’s own website.
 * https://docs.monei.com/docs/integrations/use-prebuilt-payment-page/
 *
 * For component, see:
 * Monei Payment method Card Input component.
 * https://docs.monei.com/docs/monei-js/reference/#cardinput-component
 * https://docs.monei.com/docs/payment-methods/card/
 *
 * Class WC_Gateway_Monei_CC
 */
class WCGatewayMoneiCC extends WCMoneiPaymentGatewayComponent {
	const PAYMENT_METHOD               = 'card';
	protected static $scripts_enqueued = false;

	/**
	 * @var bool
	 */
	protected $redirect_flow;

	/**
	 * @var bool
	 */
	protected $apple_google_pay;
	protected SubscriptionService $subscriptions_service;
	protected ?SubscriptionHandlerInterface $handler;
	protected CardBrandHelper $cardBrandHelper;

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
		SubscriptionService $subscriptionService,
		CardBrandHelper $cardBrandHelper
	) {
		parent::__construct( $paymentMethodsService, $templateManager, $apiKeyService, $moneiPaymentServices );
		$this->cardBrandHelper    = $cardBrandHelper;
		$this->id                 = MONEI_GATEWAY_ID;
		$this->method_title       = __( 'MONEI - Credit Card', 'monei' );
		$this->method_description = __( 'Accept credit card payments.', 'monei' );
		$this->enabled            = ( ! empty( $this->get_option( 'enabled' ) && 'yes' === $this->get_option( 'enabled' ) ) && $this->is_valid_for_use() ) ? 'yes' : false;

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		$description = ! empty( $this->get_option( 'description' ) )
			? $this->get_option( 'description' )
			: '';  // Non-breaking space if description is empty

		// Hosted payment with redirect.
		$this->has_fields = false;
		$iconUrl          = apply_filters( 'woocommerce_monei_icon', WC_Monei()->image_url( 'monei-cards.svg' ) );
		$iconMarkup       = '<img src="' . $iconUrl . '" alt="MONEI" class="monei-icons-cc" />';
		// Settings variable
		$this->hide_logo = ( ! empty( $this->get_option( 'hide_logo' ) && 'yes' === $this->get_option( 'hide_logo' ) ) ) ? true : false;

		// Hide logo if card brands are available
		$cardBrands    = $this->cardBrandHelper->getCardBrandsConfig();
		$hasCardBrands = ! empty( $cardBrands ) && count( array_filter( $cardBrands, fn( $b ) => $b['title'] !== 'Card' ) ) > 0;

		$this->icon          = ( $this->hide_logo || $hasCardBrands ) ? '' : $iconMarkup;
		$this->redirect_flow = ( ! empty( $this->get_option( 'cc_mode' ) && 'yes' === $this->get_option( 'cc_mode' ) ) ) ? true : false;
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
		$this->account_id           = $this->getAccountId();
		$this->api_key              = $this->getApiKey();
		$this->shop_name            = get_bloginfo( 'name' );
		$this->password             = ( ! empty( $this->get_option( 'password' ) ) ) ? $this->get_option( 'password' ) : '';
		$this->tokenization         = ( ! empty( $this->get_option( 'tokenization' ) && 'yes' === $this->get_option( 'tokenization' ) ) ) ? true : false;
		// Backward compatible: try local setting first, then global setting
		$local_preauth  = $this->get_option( 'pre-authorize' );
		$global_preauth = get_option( 'monei_pre_authorize', 'no' );
		$this->pre_auth = ( ! empty( $local_preauth ) && 'yes' === $local_preauth ) || ( empty( $local_preauth ) && 'yes' === $global_preauth );
		$this->logging  = ( ! empty( get_option( 'monei_debug' ) ) && 'yes' === get_option( 'monei_debug' ) ) ? true : false;

		// IPN callbacks
		$this->notify_url = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN( $this->logging );

		$this->supports = array(
			'products',
			'refunds',
		);

		if ( $this->tokenization ) {
			$this->supports[] = 'tokenization';
		}
		$this->subscriptions_service = $subscriptionService;
		$this->handler               = $this->subscriptions_service->getHandler();
		if ( $this->handler ) {
			$this->supports = $this->handler->init_subscriptions( $this->supports, $this->id );
		}

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
		add_filter(
			'woocommerce_save_settings_checkout_' . $this->id,
			function ( $is_post ) {
				return $this->checks_before_save( $is_post, 'woocommerce_monei_enabled' );
			}
		);

		add_action( 'wp_enqueue_scripts', array( $this, 'monei_scripts' ) );

		// Add new total on checkout updates (ex, selecting different shipping methods)
		add_filter(
			'woocommerce_update_order_review_fragments',
			function ( $fragments ) {
				return self::add_cart_total_fragments( $fragments );
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
		$this->form_fields = require WC_Monei()->plugin_path() . '/includes/admin/monei-cc-settings.php';
	}

	/**
	 * Validate card_input_style field
	 *
	 * @param string $key
	 * @param string $value
	 * @return string
	 */
	public function validate_card_input_style_field( $key, $value ) {
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
					__( 'Card Input Style field contains invalid JSON: %s', 'monei' ),
					json_last_error_msg()
				)
			);
			return $this->get_option( 'card_input_style', '{"base": {"height": "50px"}, "input": {"background": "none"}}' );
		}

		// Re-encode with pretty print for better readability in admin
		return wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
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
	 * Add payment method in Account add_payment_method_page.
	 *
	 * @return array
	 */
	public function add_payment_method() {
		$nonce = isset( $_POST['woocommerce-add-payment-method-nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['woocommerce-add-payment-method-nonce'] ) )
			: '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'woocommerce-add-payment-method' ) ) {
			return array(
				'result'   => 'failure',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}

		// Since it is a hosted version, we need to create a 0 EUR payment and send customer to MONEI.
		try {
			$zero_payload = $this->create_zero_eur_payload();
			$payment      = $this->moneiPaymentServices->create_payment( $zero_payload );
			$this->log( 'WC_Monei_API::add_payment_method', 'debug' );
			$this->log( $zero_payload, 'debug' );
			$this->log( $payment, 'debug' );
			do_action( 'wc_gateway_monei_add_payment_method_success', $zero_payload, $payment );

			return array(
				'result'   => 'success',
				'redirect' => $payment->getNextAction()->getRedirectUrl(),
			);
		} catch ( Exception $e ) {
			$this->log( $e, 'error' );
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'failure',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}
	}

	/**
	 * @return array
	 */
	protected function create_zero_eur_payload() {
		$current_user_id = (string) get_current_user_id();
		/**
		 * Create 0 EUR Payment Payload
		 */
		$payload = array(
			'amount'                => 0,
			'currency'              => get_woocommerce_currency(),
			'orderId'               => $current_user_id . 'generatetoken' . wp_rand( 0, 1000000 ),
			'description'           => "User $current_user_id creating empty transaction to generate token",
			'callbackUrl'           => wp_sanitize_redirect( esc_url_raw( $this->notify_url ) ),
			'completeUrl'           => wc_get_endpoint_url( 'payment-methods' ),
			'cancelUrl'             => wc_get_endpoint_url( 'payment-methods' ),
			'failUrl'               => wc_get_endpoint_url( 'payment-methods' ),
			'transactionType'       => self::VERIFY_TRANSACTION_TYPE,
			'sessionDetails'        => array(
				'ip'        => WC_Geolocation::get_ip_address(),
				'userAgent' => wc_get_user_agent(),
			),
			'generatePaymentToken'  => true,
			'allowedPaymentMethods' => array( self::PAYMENT_METHOD ),
		);

		// All Zero payloads ( add payment method ) will use component CC.
		$monei_token = $this->get_frontend_generated_monei_token();
		if ( MONEI_GATEWAY_ID === $this->id && $monei_token ) {
			$payload['paymentToken'] = $monei_token;
			$payload['sessionId']    = (string) WC()->session->get_customer_id();
		}

		return $payload;
	}

	/**
	 * Payments fields, shown on checkout or payment method page (add payment method).
	 */
	public function payment_fields() {
		ob_start();
		if ( is_add_payment_method_page() ) {
			esc_html_e( 'Pay via MONEI: you can add your payment method for future payments.', 'monei' );
			// Always use component form in Add Payment method page.
			$this->render_monei_form();
		} elseif ( $this->handler !== null && $this->handler->is_subscription_change_payment_page() ) {
			// On subscription change payment page, we always use component CC.
			// Description not shown in component mode (non-redirect)
			if ( $this->tokenization ) {
				$this->saved_payment_methods();
			}
			$this->render_monei_form();
			if ( $this->tokenization ) {
				$this->tokenization_script();
			}
		} else {
			// Checkout screen.
			// Show description only in redirect mode
			if ( $this->redirect_flow && $this->description ) {
				echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
			}
			if ( $this->tokenization ) {
				$this->saved_payment_methods();
				// If Component CC
				if ( ! $this->redirect_flow ) {
					$this->render_monei_form();
				} else {
					$this->tokenization_script();
				}
				$this->save_payment_method_checkbox();
			} elseif ( ! $this->redirect_flow ) {
				// If Component CC
				$this->render_monei_form();
			}
		}
		ob_end_flush();
	}


	/**
	 * Form where MONEI JS will render CC Component.
	 */
	protected function render_monei_form() {
		?>
		<fieldset class="monei-fieldset monei-card-fieldset wc-block-components-form"
					id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form">
			<!-- Cardholder Name Input -->
			<div class="monei-input-container wc-block-components-text-input">
				<input
						type="text"
						id="monei_cardholder_name"
						name="monei_cardholder_name"
						data-testid="cardholder-name-input"
						placeholder="<?php echo esc_attr__( 'Cardholder Name', 'monei' ); ?>"
						required
						class="monei-input">
				<div
						id="monei-cardholder-name-error"
						class="wc-block-components-validation-error"
				></div>
			</div>
			<!-- Card Input Container -->
			<div id="payment-form" class="monei-input-container wc-block-components-text-input">
				<div id="monei-card-input" class="monei-card-input">
				</div>
				<div
						id="monei-card-error"
						class="wc-block-components-validation-error"
				></div>
			</div>
		</fieldset>

		<?php
	}

	/**
	 * Registering MONEI JS library and plugin js.
	 */
	public function monei_scripts() {
		if ( self::$scripts_enqueued || ! $this->should_load_scripts() ) {
			return;
		}

		// If merchant wants Component CC or is_add_payment_method_page that always use this component method.
		if ( $this->redirect_flow || ( ! is_checkout() && ! is_add_payment_method_page() && ( $this->handler && ! $this->handler->is_subscription_change_payment_page() ) ) ) {
			return;
		}

		if ( 'no' === $this->enabled ) {
			return;
		}

		if ( ! wp_script_is( 'monei', 'registered' ) ) {
			wp_register_script( 'monei', 'https://js.monei.com/v2/monei.js', '', '1.0', true );

		}
		wp_register_script(
			'woocommerce_monei',
			plugins_url( 'public/js/monei-cc-classic.min.js', MONEI_MAIN_FILE ),
			array(
				'jquery',
				'monei',
			),
			MONEI_VERSION,
			true
		);
		wp_enqueue_script( 'monei' );
		// Determine the total amount to be passed
		$total            = $this->determineTheTotalAmountToBePassed();
		$card_input_style = $this->get_option( 'card_input_style', '{"base": {"height": "50px"}, "input": {"background": "none"}}' );
		wp_localize_script(
			'woocommerce_monei',
			'wc_monei_params',
			array(
				'account_id'       => $this->getAccountId(),
				'session_id'       => WC()->session->get_customer_id(),
				'total'            => monei_price_format( $total ),
				'currency'         => get_woocommerce_currency(),
				'apple_logo'       => WC_Monei()->image_url( 'apple-logo.svg' ),
				'card_input_style' => json_decode( $card_input_style ),
				'card_brands'      => $this->cardBrandHelper->getCardBrandsConfig(),
			)
		);

		wp_enqueue_script( 'woocommerce_monei' );
		$this->tokenization_script();
		self::$scripts_enqueued = true;
	}
	protected function should_load_scripts() {
		return is_checkout() || is_cart() || is_product() || is_add_payment_method_page();
	}
}

