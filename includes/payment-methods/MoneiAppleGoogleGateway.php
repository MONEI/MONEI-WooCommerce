<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles Monei Apple Google method based on CC
 *
 * Class MoneiAppleGoogleGateway
 */
class MoneiAppleGoogleGateway extends WC_Gateway_Monei_CC {

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
        parent::__construct();
		$this->id                 = 'monei_apple_google';
		$this->method_title       = __( 'MONEI - Apple/Google', 'monei' );
        $this->settings = get_option( 'woocommerce_monei_settings', array() );
        $this->enabled = ( ! empty( isset($this->settings['apple_google_pay']) && 'yes' ===$this->settings['apple_google_pay'] ) ) ? 'yes' : 'no';

        add_filter(
            'woocommerce_available_payment_gateways',
            [$this, 'hideAppleGoogleInCheckout'],
            11,
            1
        );
	}

    /**
     * Hide Apple/Google Pay in WooCommerce Checkout
     */
    public function hideAppleGoogleInCheckout($available_gateways)
    {
        if (!has_block('woocommerce/checkout')) {
            unset($available_gateways['monei_apple_google']);
        }

        return $available_gateways;
    }
    public function isBlockCheckout(): bool
    {
        if (!is_checkout()) {
            return false;
        }
        if (!class_exists('WC_Blocks_Utils')) {
            return false;
        }
        // Check if the checkout block is present
        $has_block = WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout');

        // Additional check: see if the traditional checkout shortcode is present
        $has_shortcode = has_shortcode(get_post(wc_get_page_id('checkout'))->post_content, 'woocommerce_checkout');

        // If the block is present and the shortcode is not, we can be more confident it's a block checkout
        return $has_block && !$has_shortcode;
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
	public function process_payment($order_id, $allowed_payment_method = null) {
		return parent::process_payment($order_id, self::PAYMENT_METHOD);
	}
}

