<?php
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
class WC_Gateway_Monei_CC extends WC_Monei_Payment_Gateway_Component {

	use WC_Monei_Subscriptions_Trait;

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
	public function __construct() {

		$this->id                 = MONEI_GATEWAY_ID;
		$this->method_title       = __( 'MONEI - Credit Card', 'monei' );
		$this->method_description = __( 'Accept Credit Card payments.', 'monei' );
		$this->enabled            = ( ! empty( $this->get_option( 'enabled' ) && 'yes' === $this->get_option( 'enabled' ) ) && $this->is_valid_for_use() ) ? 'yes' : false;

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		// Hosted payment with redirect.
		$this->has_fields = false;
		$iconUrl = apply_filters( 'woocommerce_monei_icon', WC_Monei()->image_url( 'monei-logo.svg' ));
		$iconMarkup = '<img src="' . $iconUrl . '" alt="MONEI" class="monei-icons" />';
		// Settings variable
		$this->hide_logo            = ( ! empty( $this->get_option( 'hide_logo' ) && 'yes' === $this->get_option( 'hide_logo' ) ) ) ? true : false;
		$this->icon                 = ( $this->hide_logo ) ? '' : $iconMarkup;
		$this->redirect_flow        = ( ! empty( $this->get_option( 'cc_mode' ) && 'yes' === $this->get_option( 'cc_mode' ) ) ) ? true : false;
		$this->apple_google_pay     = ( ! empty( $this->get_option( 'apple_google_pay' ) && 'yes' === $this->get_option( 'apple_google_pay' ) ) ) ? true : false;
		$this->testmode             = ( ! empty( $this->get_option( 'testmode' ) && 'yes' === $this->get_option( 'testmode' ) ) ) ? true : false;
		$this->title                = ( ! empty( $this->get_option( 'title' ) ) ) ? $this->get_option( 'title' ) : '';
		$this->description          = ( ! empty( $this->get_option( 'description' ) ) ) ? $this->get_option( 'description' ) : '';
		$this->status_after_payment = ( ! empty( $this->get_option( 'orderdo' ) ) ) ? $this->get_option( 'orderdo' ) : '';
		$this->account_id           = ( ! empty( $this->get_option( 'accountid' ) ) ) ? $this->get_option( 'accountid' ) : '';
		$this->api_key              = ( ! empty( $this->get_option( 'apikey' ) ) ) ? $this->get_option( 'apikey' ) : '';
		$this->shop_name            = get_bloginfo( 'name' );
		$this->password             = ( ! empty( $this->get_option( 'password' ) ) ) ? $this->get_option( 'password' ) : '';
		$this->tokenization         = ( ! empty( $this->get_option( 'tokenization' ) && 'yes' === $this->get_option( 'tokenization' ) ) ) ? true : false;
		$this->pre_auth             = ( ! empty( $this->get_option( 'pre-authorize' ) && 'yes' === $this->get_option( 'pre-authorize' ) ) ) ? true : false;
		$this->logging              = ( ! empty( $this->get_option( 'debug' ) ) && 'yes' === $this->get_option( 'debug' ) ) ? true : false;

		// IPN callbacks
		$this->notify_url = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN($this->logging);

		$this->supports = array(
			'products',
			'refunds',
		);

		if ( $this->tokenization ) {
			$this->supports[] = 'tokenization';
		}

		if ( $this->is_subscriptions_addon_enabled() ) {
			$this->init_subscriptions();
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_filter( 'woocommerce_save_settings_checkout_' . $this->id, array( $this, 'checks_before_save' ) );

		// If merchant wants Component CC or is_add_payment_method_page that always use this component method.
		if ( ! $this->redirect_flow || is_add_payment_method_page() || $this->is_subscription_change_payment_page() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'monei_scripts' ] );
		}

		// Add new total on checkout updates (ex, selecting different shipping methods)
		add_filter( 'woocommerce_update_order_review_fragments', function( $fragments ) {
			return self::add_cart_total_fragments( $fragments );
		} );
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

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-add-payment-method-nonce'] ) ), 'woocommerce-add-payment-method' ) ) {
			return array(
				'result'   => 'failure',
				'redirect' => wc_get_endpoint_url( 'payment-methods' ),
			);
		}

		// Since it is a hosted version, we need to create a 0 EUR payment and send customer to MONEI.
		try {
			$zero_payload = $this->create_zero_eur_payload();
			$payment      = WC_Monei_API::create_payment( $zero_payload );
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
		$payload = [
			'amount'                => 0,
			'currency'              => get_woocommerce_currency(),
			'orderId'               => $current_user_id . 'generatetoken' . wp_rand( 0, 1000000 ),
			'description'           => "User $current_user_id creating empty transaction to generate token",
			'callbackUrl'           => wp_sanitize_redirect( esc_url_raw( $this->notify_url ) ),
			'completeUrl'           => wc_get_endpoint_url( 'payment-methods' ),
			'cancelUrl'             => wc_get_endpoint_url( 'payment-methods' ),
			'failUrl'               => wc_get_endpoint_url( 'payment-methods' ),
			'transactionType'       => self::SALE_TRANSACTION_TYPE,
			'sessionDetails'        => [
				'ip'        => WC_Geolocation::get_ip_address(),
				'userAgent' => wc_get_user_agent(),
			],
			'generatePaymentToken'  => true,
			'allowedPaymentMethods' => [ self::PAYMENT_METHOD ],
		];

		// All Zero payloads ( add payment method ) will use component CC.
		if ( MONEI_GATEWAY_ID === $this->id && $monei_token = $this->get_frontend_generated_monei_token() ) {
			$payload['paymentToken'] = $monei_token;
			$payload['sessionId']    = (string) WC()->session->get_customer_id();
		}

		return $payload;
	}

	/**
	 * Payments fields, shown on checkout or payment method page (add payment method).
	 */
	function payment_fields() {
		ob_start();
		if ( is_add_payment_method_page() ) {
			esc_html_e( 'Pay via MONEI: you can add your payment method for future payments.', 'monei' );
			// Always use component form in Add Payment method page.
			$this->render_monei_form();
		} elseif ( $this->is_subscription_change_payment_page() ) {
			// On subscription change payment page, we always use component CC.
			echo esc_html( $this->description );
			if ( $this->tokenization ) {
				$this->saved_payment_methods();
			}
			$this->render_monei_form();
			if ( $this->tokenization ) {
				$this->tokenization_script();
			}
		} else {
			// Checkout screen.
			// We show description, if tokenization available, we show saved cards and checkbox to save.
			echo esc_html( $this->description );
			if ( $this->tokenization ) {
				$this->saved_payment_methods();
				// If Component CC
				if ( ! $this->redirect_flow ) {
					$this->render_monei_form();
				} else {
					$this->tokenization_script();
				}
				$this->save_payment_method_checkbox();
			} else {
				// If Component CC
				if ( ! $this->redirect_flow ) {
					$this->render_monei_form();
				}
			}
			if ( $this->apple_google_pay ) {
				$this->render_google_pay_form();
			}
		}
		ob_end_flush();
	}

	/**
	 * Form where Google or Apple Pay button will be rendered.
	 * https://docs.monei.com/docs/monei-js/payment-request/#2-add-payment-request-component-to-your-payment-page-client-side
	 */
	protected function render_google_pay_form() {
		?>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-payment-request-form" class="wc-payment-request-form" style="background:transparent;">
			<div id="payment-request-form">
				<div id="payment-request-container">
					<!-- Payment Request Component will be inserted here. -->
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Form where MONEI JS will render CC Component.
	 */
	protected function render_monei_form() {
		?>
        <style>
            #payment-form {
                padding-bottom: 15px;
            }

            #card-input {
                border: 1px solid transparent;
                border-radius: 4px;
                background-color: white;
                box-shadow: 0 1px 3px 0 #e6ebf1;
                height: 38px;
                box-sizing: border-box;
                -webkit-transition: box-shadow 150ms ease;
                transition: box-shadow 150ms ease;
				max-width: 350px;
            }

            #card-input.is-focused {
                box-shadow: 0 1px 3px 0 #cfd7df;
            }
		</style>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
			<div id="payment-form">
				<div class="card-field">
					<div id="card-input">
						<!-- A MONEI Card Input Component will be inserted here. -->
					</div>
					<!-- Used to display card errors. -->
					<div id="monei-card-error"></div>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Registering MONEI JS library and plugin js.
	 */
	public function monei_scripts() {

		if ( ! is_checkout() && ! is_add_payment_method_page() && ! $this->is_subscription_change_payment_page() ) {
			return;
		}

		if ( 'no' === $this->enabled ) {
			return;
		}

		$script_version_name = ( $this->testmode ) ? 'monei.js' : 'monei.min.js';
		wp_register_script( 'monei', 'https://js.monei.com/v1/monei.js', '', '1.0', true );
		wp_register_script( 'woocommerce_monei', plugins_url( 'assets/js/' . $script_version_name, MONEI_MAIN_FILE ), [
			'jquery',
			'monei'
		], MONEI_VERSION, true );
		wp_enqueue_script( 'monei' );

		wp_localize_script(
			'woocommerce_monei',
			'wc_monei_params',
			[
				'account_id'       => monei_get_settings( 'accountid' ),
				'session_id'       => WC()->session->get_customer_id(),
				'apple_google_pay' => $this->apple_google_pay,
				'total'            => monei_price_format( WC()->cart->get_total( false ) ),
				'currency'         => get_woocommerce_currency(),
			]
		);

		wp_enqueue_script( 'woocommerce_monei' );
		$this->tokenization_script();
	}

}

