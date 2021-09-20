<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handle Monei Cofidis Payment method.
 * todo: hide payment gateway when not fitting limites Cofidis.
 * todo: when happens when shipping costs are lower or bigger than limits?
 * todo: refund?
 *
 * Class WC_Gateway_Monei_Cofidis
 */
class WC_Gateway_Monei_Cofidis extends WC_Monei_Payment_Gateway_Hosted {

	const PAYMENT_METHOD = 'cofidis';

	/**
	 * Constructor for the gateway.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		$this->id = MONEI_GATEWAY_ID . '_cofidis';
		$this->method_title  = __( 'MONEI - Cofidis', 'monei' );
		$this->method_description = __( 'Accept Cofidis payments.', 'monei' );
		$this->enabled = ( ! empty( $this->get_option( 'enabled' ) && 'yes' === $this->get_option( 'enabled' ) ) && $this->is_valid_for_use() ) ? 'yes' : false;

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		// Cofidis Hosted payment with redirect.
		$this->has_fields = false;

		// Settings variable
		$this->hide_logo            = ( ! empty( $this->get_option( 'hide_logo' ) && 'yes' === $this->get_option( 'hide_logo' ) ) ) ? true : false;
		$this->icon                 = ( $this->hide_logo ) ? '' : apply_filters( 'woocommerce_monei_cofidis_icon', WC_Monei()->image_url( 'cofidis-logo.svg' ) );
		$this->title                = ( ! empty( $this->get_option( 'title' ) ) ) ? $this->get_option( 'title' ) : '';
		$this->description          = ( ! empty( $this->get_option( 'description' ) ) ) ? $this->get_option( 'description' ) : '';
		$this->status_after_payment = ( ! empty( $this->get_option( 'orderdo' ) ) ) ? $this->get_option( 'orderdo' ) : '';
		$this->api_key              = ( ! empty( $this->get_option( 'apikey' ) ) ) ? $this->get_option( 'apikey' ) : '';
		$this->logging              = ( ! empty( $this->get_option( 'debug' ) ) && 'yes' === $this->get_option( 'debug' ) ) ? true : false;

		// IPN callbacks
		$this->notify_url           = WC_Monei()->get_ipn_url();
		new WC_Monei_IPN();

		$this->supports             = array(
			'products',
			'refunds',
		);

        // todo: check limits.
        if ( is_checkout() ) {
            //$this->enabled = false;
        }

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_filter( 'woocommerce_save_settings_checkout_' . $this->id, array( $this, 'checks_before_save' ) );
        add_action( 'wp_enqueue_scripts', [ $this, 'cofidis_scripts' ] );
        // Add total price info on update action js
        add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_cart_total' ) );
    }


	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @since 5.0
	 * @return void
	 */
	public function init_form_fields() {
        $this->form_fields = require WC_Monei()->plugin_path() . '/includes/admin/monei-cofidis-settings.php';
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
     * Setting checks when saving.
     *
     * @param $is_post
     * @return bool
     */
    public function checks_before_save( $is_post ) {
        if ( $is_post ) {
            if ( empty( $_POST['woocommerce_monei_cofidis_apikey'] ) ) {
                WC_Admin_Settings::add_error( __( 'Please, MONEI needs API Key in order to work. Disabling the gateway.', 'monei' ) );
                unset( $_POST['woocommerce_monei_cofidis_enabled'] );
            }
        }
        return $is_post;
    }

    /**
     * Payments fields, shown on checkout or payment method page (add payment method).
     */
    public function payment_fields() {
        ob_start();
        if ( is_checkout() ) {
            echo $this->description;
            $this->render_cofidis_widget();
        }
        ob_end_flush();
    }

    /**
     * To add the widget to your cart or product page create a container element in the HTML file where the widget will be displayed.
     * https://docs.monei.com/docs/guides/setup-cofidis-widget/
     */
    protected function render_cofidis_widget() {
        ?>
        <div id="cofidis_widget">
        </div>
        <?php
    }

    /**
     * Registering MONEI JS library and plugin js.
     * https://docs.monei.com/docs/guides/setup-cofidis-widget/
     */
    public function cofidis_scripts()
    {

        if ( ! is_checkout() ) {
            return;
        }

        if ( 'no' === $this->enabled ) {
            return;
        }

        // If already enqueued (by other payment method) we do nothing.
        if ( ! wp_script_is('monei', 'enqueued' ) ) {
            wp_register_script('monei', 'https://js.monei.com/v1/monei.js', '', '1.0', true);
            wp_enqueue_script('monei');
        }

        $script_version_name = ( $this->testmode ) ? 'cofidis.js' : 'cofidis.js';
        wp_register_script('woocommerce_monei_cofidis', plugins_url('assets/js/' . $script_version_name, MONEI_MAIN_FILE), ['jquery', 'monei'], MONEI_VERSION, true);
        wp_localize_script(
            'woocommerce_monei_cofidis',
            'wc_monei_params',
            [
                'account_id' => monei_get_settings('accountid'),
                // Ask about this, if it takes automatically the lang.
                'lang'       => get_locale(),
                //'total'      => monei_price_format ( WC()->cart->get_total( false ) ),
                'total'      => monei_price_format ( 500 ),
            ]
        );
        wp_enqueue_script('woocommerce_monei_cofidis');
    }

    /**
     * On updated_checkout, we need thew new total cart in order to update cofidis plugin.
     *
     * @param array $fragments
     * @return array
     */
    public function add_cart_total( $fragments ) {
        if ( ! WC()->cart ) {
            return $fragments;
        }

        $fragments['monei_new_total'] = monei_price_format ( WC()->cart->get_total( false ) );
        return $fragments;
    }

}

