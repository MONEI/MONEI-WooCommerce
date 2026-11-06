<?php
/**
 * Owner check on a saved token selected at checkout.
 *
 * The token ID comes from the shopper's browser. Without this check a shopper posts the
 * token ID of another user and pays with that user's saved card.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\payment\PaymentTokenGuard;
use PHPUnit\Framework\TestCase;

class PaymentTokenGuardTest extends TestCase {

	public function test_own_token_is_accepted() {
		$this->assertTrue( PaymentTokenGuard::belongs_to( 7, 7 ) );
	}

	public function test_token_of_another_user_is_rejected() {
		$this->assertFalse( PaymentTokenGuard::belongs_to( 8, 7 ) );
	}

	public function test_guest_is_rejected_even_for_a_token_without_owner() {
		// Both sides are equal, so only the zero check can reject this.
		$this->assertFalse( PaymentTokenGuard::belongs_to( 0, 0 ) );
	}

	public function test_missing_token_is_rejected() {
		$this->assertFalse( PaymentTokenGuard::belongs_to( null, 7 ) );
	}
}
