<?php
/**
 * Server-side verification of the amount a wallet reports.
 *
 * `finalAmount` reaches the server through the shopper's browser, so it is
 * attacker-controlled. The only figure allowed to decide a charge is the one the server
 * recomputes from the cart, and these tests are what keeps that true.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\express\ExpressCheckoutAjaxHandler;
use PHPUnit\Framework\TestCase;

class ExpressAmountVerificationTest extends TestCase {

	public static function setUpBeforeClass(): void {
		// monei_price_format() is the plugin-wide converter, used below to show that the
		// express check does NOT agree with it on a zero-decimal currency.
		require_once dirname( __DIR__, 2 ) . '/includes/woocommerce-gateway-monei-core-functions.php';
	}

	public function test_matching_amount_is_accepted() {
		$this->assertTrue( ExpressCheckoutAjaxHandler::amount_matches( 19.99, 'EUR', '1999' ) );
	}

	public function test_integer_and_float_forms_of_the_same_amount_are_accepted() {
		// The wire carries whatever JSON produced; 1999 and "1999" and 1999.0 are one
		// amount and none of them is tampering.
		$this->assertTrue( ExpressCheckoutAjaxHandler::amount_matches( 19.99, 'EUR', 1999 ) );
		$this->assertTrue( ExpressCheckoutAjaxHandler::amount_matches( 19.99, 'EUR', 1999.0 ) );
	}

	/**
	 * @dataProvider tampered_amounts
	 */
	public function test_tampered_amount_is_rejected( $submitted ) {
		// A cart of 19.99 must never be charged as anything else, whatever the client
		// claims the wallet showed.
		$this->assertFalse( ExpressCheckoutAjaxHandler::amount_matches( 19.99, 'EUR', $submitted ) );
	}

	public function tampered_amounts() {
		return array(
			'one cent'           => array( '1' ),
			'free'               => array( '0' ),
			'negative'           => array( '-1999' ),
			'hundredth'          => array( '19' ),
			'hundred times more' => array( '199900' ),
			'not a number'       => array( 'nineteen ninety nine' ),
			'expression'         => array( '1999+0' ),
			'array'              => array( array( 1999 ) ),
			'boolean'            => array( true ),
		);
	}

	/**
	 * @dataProvider off_by_one_cent
	 */
	public function test_off_by_one_cent_is_rejected( $submitted ) {
		// The commonest tamper is the smallest one, and a tolerance here is a licence to
		// underpay every order in the store.
		$this->assertFalse( ExpressCheckoutAjaxHandler::amount_matches( 19.99, 'EUR', $submitted ) );
	}

	public function off_by_one_cent() {
		return array(
			'one under' => array( '1998' ),
			'one over'  => array( '2000' ),
		);
	}

	public function test_fractional_minor_units_are_rejected() {
		// Nothing hands back a fraction of a cent. Accepting one would let a rounded
		// value pass the comparison it was rounded into.
		$this->assertFalse( ExpressCheckoutAjaxHandler::amount_matches( 19.99, 'EUR', '1999.4' ) );
	}

	/**
	 * @dataProvider missing_amounts
	 */
	public function test_missing_amount_is_rejected( $submitted ) {
		// An absent amount is not an amount that matches. Treating it as one would make
		// the whole check optional from the client's side.
		$this->assertFalse( ExpressCheckoutAjaxHandler::amount_matches( 19.99, 'EUR', $submitted ) );
	}

	public function missing_amounts() {
		return array(
			'null'          => array( null ),
			'empty string'  => array( '' ),
			'only spaces'   => array( '   ' ),
		);
	}

	public function test_zero_total_is_matched_exactly() {
		$this->assertTrue( ExpressCheckoutAjaxHandler::amount_matches( 0, 'EUR', '0' ) );
	}

	public function test_zero_decimal_currency_uses_to_minor_units() {
		// 1000 JPY is 1000 minor units. The wallet reports 1000 and the check must agree.
		$this->assertTrue( ExpressCheckoutAjaxHandler::amount_matches( 1000, 'JPY', '1000' ) );
	}

	public function test_zero_decimal_currency_does_not_use_monei_price_format() {
		// The proof that the right converter is in use: monei_price_format() multiplies
		// by 100 for every currency, so a check built on it would demand 100000 from the
		// wallet and refuse every legitimate JPY payment while accepting a 100x overcharge.
		$this->assertSame( 100000, monei_price_format( 1000 ) );

		$this->assertFalse( ExpressCheckoutAjaxHandler::amount_matches( 1000, 'JPY', (string) monei_price_format( 1000 ) ) );
		$this->assertTrue( ExpressCheckoutAjaxHandler::amount_matches( 1000, 'JPY', '1000' ) );
	}

	public function test_binary_float_total_still_matches() {
		// (int) ( 10.10 * 100 ) is 1009. A truncating comparison would refuse a correct
		// wallet amount on one of the commonest totals there is.
		$this->assertTrue( ExpressCheckoutAjaxHandler::amount_matches( 10.10, 'EUR', '1010' ) );
	}
}
