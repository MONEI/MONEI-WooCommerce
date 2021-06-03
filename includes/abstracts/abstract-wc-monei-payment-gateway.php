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
	 * Form option fields.
	 *
	 * @var array
	 */
	public $form_fields = array();

}

