<?php
/**
 * Ownership checks on a payment named in a redirect URL.
 *
 * The `id` and `orderId` query arguments come from the shopper's browser. Without
 * these checks a shopper pays one order and reuses that payment ID to mark a second
 * unpaid order of the same total as paid.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\payment\RedirectPaymentGuard;
use PHPUnit\Framework\TestCase;

class RedirectPaymentGuardTest extends TestCase {

	public function test_payment_of_the_order_is_accepted() {
		// The API returns orderId as a string, WooCommerce order IDs are ints.
		$this->assertTrue( RedirectPaymentGuard::belongs_to_order( '42', 42 ) );
	}

	public function test_payment_of_another_order_is_rejected() {
		$this->assertFalse( RedirectPaymentGuard::belongs_to_order( '41', 42 ) );
	}

	public function test_payment_of_an_order_with_a_prefix_id_is_rejected() {
		// A loose or prefix comparison would let order 12's payment pay order 123.
		$this->assertFalse( RedirectPaymentGuard::belongs_to_order( '12', 123 ) );
		$this->assertFalse( RedirectPaymentGuard::belongs_to_order( '123', 12 ) );
	}

	public function test_payment_without_order_id_is_rejected_when_no_order_is_named() {
		// Both sides cast to '' and must not compare equal.
		$this->assertFalse( RedirectPaymentGuard::belongs_to_order( null, '' ) );
	}

	public function test_matching_order_key_is_accepted() {
		$this->assertTrue( RedirectPaymentGuard::order_key_matches( 'wc_order_abc123', 'wc_order_abc123' ) );
	}

	/**
	 * @dataProvider bad_order_keys
	 */
	public function test_bad_order_key_is_rejected( $submitted ) {
		$this->assertFalse( RedirectPaymentGuard::order_key_matches( $submitted, 'wc_order_abc123' ) );
	}

	public function bad_order_keys() {
		return array(
			'other key' => array( 'wc_order_xyz789' ),
			'missing'   => array( '' ),
			'array'     => array( array( 'wc_order_abc123' ) ),
		);
	}

	public function test_empty_key_does_not_match_an_order_without_key() {
		$this->assertFalse( RedirectPaymentGuard::order_key_matches( '', '' ) );
	}

	public function test_token_request_of_the_user_is_accepted() {
		$this->assertTrue( RedirectPaymentGuard::belongs_to_user_token_request( '5generatetoken123456', 5 ) );
	}

	public function test_token_request_of_another_user_is_rejected() {
		// User 5 must not save the card of user 55.
		$this->assertFalse( RedirectPaymentGuard::belongs_to_user_token_request( '55generatetoken123456', 5 ) );
	}

	public function test_order_payment_is_not_a_token_request() {
		$this->assertFalse( RedirectPaymentGuard::belongs_to_user_token_request( '5', 5 ) );
	}

	public function test_guest_has_no_token_request() {
		$this->assertFalse( RedirectPaymentGuard::belongs_to_user_token_request( '0generatetoken123456', 0 ) );
	}
}
