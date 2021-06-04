<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class that will be inherited by all payment methods.
 * Empty for now.
 *
 * @extends WC_Payment_Gateway_CC
 *
 * @since 5.0
 */
abstract class WC_Monei_Payment_Gateway extends WC_Payment_Gateway {

	public const LIVE_URL   = 'https://pay.monei.com/checkout';
	public const REFUND_URL = 'https://api.monei.com/v1/refund';
	public const CHARGE_URL = 'https://api.monei.com/v1/charge';

	/**
	 * Is sandbox?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Is debug active?
	 *
	 * @var bool
	 */
	public $debug;

	/**
	 * What to do after payment?. processing or completed.
	 *
	 * @var string
	 */
	public $status_after_payment;

	/**
	 * Account ID.
	 *
	 * @var string
	 */
	public $account_id;

	/**
	 * API Key.
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * Shop Name.
	 *
	 * @var string
	 */
	public $shop_name;

	/**
	 * Password.
	 *
	 * @var string
	 */
	public $password;

	/**
	 * Enable Tokenization.
	 * @var bool
	 */
	public $tokenization;

	/**
	 * Enable Debugging.
	 *
	 * @var bool
	 */
	public $logging;

	/**
	 * Logger.
	 *
	 * @var WC_Logger
	 */
	public $logger;

	/**
	 * @var string
	 */
	public $notify_url;

	/**
	 * Form option fields.
	 *
	 * @var array
	 */
	public $form_fields = array();

	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @access public
	 * @return bool
	 */
	protected function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), array( 'EUR', 'USD', 'GBP' ), true ) ) {
			return false;
		} else {
			return true;
		}
	}
}

