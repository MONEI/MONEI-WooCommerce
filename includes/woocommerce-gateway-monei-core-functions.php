<?php
/**
 * Functions related to MONEI core.
 *
 * @author   Manuel Rodriguez
 * @category Core
 * @package  Woocommerce_Gateway_Monei/Functions
 * @version  5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * A positive integer representing how much to charge in the smallest currency unit (e.g., 100 cents to charge 1.00 USD).
 * @param float $amount
 *
 * @return int
 */
function monei_price_format( $amount ) {
	return (int) (string) ( (float) preg_replace( '/[^0-9.]/', '', $amount ) * 100 );
}

/**
 * @param false|string $key
 *
 * @return false|string|array
 */
function monei_get_settings( $key = false ) {
	if ( ! $key ) {
		return get_option( 'woocommerce_monei_settings' );
	}

	$settings = get_option( 'woocommerce_monei_settings' );
	return $settings[ $key ];
}


