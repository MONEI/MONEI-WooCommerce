<?php
/**
 * Minor-unit conversion for express checkout amounts.
 *
 * Every MONEI amount on the wire is an integer in the currency's smallest unit. A
 * wrong conversion here is a wrong charge, and Task 22 verifies the order total with
 * the same method, so a drift between the two rejects legitimate payments.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\express\ExpressCheckoutAjaxHandler;
use PHPUnit\Framework\TestCase;

class ExpressAmountTest extends TestCase {

	public static function setUpBeforeClass(): void {
		// monei_price_format() is the plugin-wide converter. It lives in a plain
		// function file, and every function in it is declaration-only, so the file
		// loads without WordPress.
		require_once dirname( __DIR__, 2 ) . '/includes/woocommerce-gateway-monei-core-functions.php';
	}

	public function test_two_decimal_currency_converts_to_cents() {
		$this->assertSame( 1999, ExpressCheckoutAjaxHandler::to_minor_units( 19.99, 'EUR' ) );
	}

	public function test_missing_currency_defaults_to_two_decimals() {
		// get_woocommerce_currency() can return an empty string before WooCommerce is
		// fully loaded; treating that as zero-decimal would undercharge by 100x.
		$this->assertSame( 1234, ExpressCheckoutAjaxHandler::to_minor_units( 12.34, '' ) );
	}

	/**
	 * @dataProvider binary_float_edge_cases
	 */
	public function test_rounds_binary_float_error_instead_of_truncating( $amount, $expected ) {
		// (int) ( 10.10 * 100 ) is 1009 — the product is 1009.9999999999999. Truncation
		// here silently undercharges by one cent on very common amounts.
		$this->assertSame( $expected, ExpressCheckoutAjaxHandler::to_minor_units( $amount, 'EUR' ) );
	}

	public function binary_float_edge_cases() {
		return array(
			'ten point one'   => array( 10.10, 1010 ),
			'fifty seven'     => array( 57.85, 5785 ),
			'one point one'   => array( 1.10, 110 ),
			'sub cent rounds' => array( 4.999, 500 ),
		);
	}

	public function test_string_amount_is_accepted() {
		// WooCommerce getters return formatted decimal strings in some contexts.
		$this->assertSame( 1999, ExpressCheckoutAjaxHandler::to_minor_units( '19.99', 'EUR' ) );
	}

	public function test_zero_decimal_currency_is_not_multiplied() {
		// 1000 JPY is 1000 minor units, not 100000. Charging the latter is a 100x
		// overcharge.
		$this->assertSame( 1000, ExpressCheckoutAjaxHandler::to_minor_units( 1000, 'JPY' ) );
	}

	public function test_zero_decimal_currency_code_is_case_insensitive() {
		$this->assertSame( 1000, ExpressCheckoutAjaxHandler::to_minor_units( 1000, 'jpy' ) );
	}

	public function test_negative_amount_keeps_its_sign() {
		// Discounts are emitted as negative display items so the items sum to the total.
		$this->assertSame( -550, ExpressCheckoutAjaxHandler::to_minor_units( -5.50, 'EUR' ) );
	}

	/**
	 * @dataProvider two_decimal_amounts
	 */
	public function test_agrees_with_monei_price_format_on_two_decimal_currencies( $amount ) {
		// Express must not put a second amount format on the wire: the IPN amount check
		// and payment creation both go through monei_price_format().
		$this->assertSame(
			monei_price_format( $amount ),
			ExpressCheckoutAjaxHandler::to_minor_units( $amount, 'EUR' )
		);
	}

	public function two_decimal_amounts() {
		return array(
			array( 0 ),
			array( 0.01 ),
			array( 10.10 ),
			array( 19.99 ),
			array( 57.85 ),
			array( 1234.56 ),
		);
	}
}
