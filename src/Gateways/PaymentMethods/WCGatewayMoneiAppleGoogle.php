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
		$this->api_key      = $this->getApiKey();
		$this->account_id   = $this->getAccountId();
		$this->icon         = ( $this->hide_logo ) ? '' : $iconMarkup;
		$this->settings     = get_option( 'woocommerce_monei_apple_google_settings', array() );
		$this->enabled      = ( ! empty( $this->get_option( 'enabled' ) && 'yes' === $this->get_option( 'enabled' ) ) && $this->is_valid_for_use() ) ? 'yes' : false;
		$this->supports     = array(
			'products',
			'refunds',
		);
        $this->has_fields = true;
		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		add_action( 'wp_enqueue_scripts', array( $this, 'apple_google_scripts' ) );
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array(
				$this,
				'process_admin_options',
			)
		);
	}

	public function apple_google_scripts() {
		wp_register_script(
			'wc-monei-apple-google',
			plugins_url( 'public/js/monei-apple-google-classic.min.js', MONEI_MAIN_FILE ),
			array(
				'jquery',
				'monei',
			),
			MONEI_VERSION,
			true
		);
		wp_enqueue_script( 'wc-monei-apple-google' );
        $total = $this->determineTheTotalAmountToBePassed();
		wp_localize_script(
			'wc-monei-apple-google',
			'wc_monei_apple_google_params',
            array(
                'account_id'       => $this->getAccountId(),
                'session_id'       => WC()->session->get_customer_id(),
                'apple_google_pay' => $this->apple_google_pay,
                'total'            => monei_price_format( $total ),
                'currency'         => get_woocommerce_currency(),
                'apple_logo'       => WC_Monei()->image_url( 'apple-logo.svg' ),
            )
		);
	}

	public function process_admin_options() {
		parent::process_admin_options();

		// Additional processing if needed
		$this->init_settings();

		// Update the settings with the new values
		update_option( 'woocommerce_monei_apple_google_settings', $this->settings, 'yes' );
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
	 * Payments fields, shown on checkout or payment method page (add payment method).
	 */
	public function payment_fields() {
		ob_start();

		// Checkout screen.
		// We show description, if tokenization available, we show saved cards and checkbox to save.
		echo esc_html( $this->description );

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
}

