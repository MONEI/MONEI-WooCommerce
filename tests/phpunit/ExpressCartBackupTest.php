<?php
/**
 * Cart snapshot handling for product page express checkout.
 *
 * ⚠️ Coverage limits, stated plainly. This suite runs without a WordPress bootstrap,
 * so it covers the snapshot's *shape* — what is stored, what is stripped, what is
 * refused, and which lines survive a second tab — but not the round trip through
 * `WC_Cart`. `save()` and `restore()` read `WC()->cart` and `WC()->session`, which
 * cannot be stood up here: the WooCommerce stubs are declaration-only and fatal at
 * runtime (see tests/phpunit/bootstrap.php). The live round trip — three items in the
 * cart, express from a product page, cancel, three items back — is verified against
 * the running store and recorded in the plan.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\express\ExpressCartBackup;
use PHPUnit\Framework\TestCase;

class ExpressCartBackupTest extends TestCase {

	/**
	 * @return array<string, mixed>
	 */
	private function cart_line( $product_id, $quantity, array $extra = array() ) {
		return array_merge(
			array(
				'key'          => 'k' . $product_id,
				'product_id'   => $product_id,
				'variation_id' => 0,
				'variation'    => array(),
				'quantity'     => $quantity,
				'line_total'   => 10.0,
			),
			$extra
		);
	}

	public function test_snapshot_keeps_items_quantities_and_coupons() {
		$snapshot = ExpressCartBackup::build_snapshot(
			array(
				'a' => $this->cart_line( 11, 3 ),
				'b' => $this->cart_line( 22, 1 ),
			),
			array( 'SUMMER10' ),
			array( 0 => 'flat_rate:1' )
		);

		$this->assertSame( array( 'a', 'b' ), array_keys( $snapshot['contents'] ) );
		$this->assertSame( 3, $snapshot['contents']['a']['quantity'] );
		$this->assertSame( 22, $snapshot['contents']['b']['product_id'] );
		$this->assertSame( array( 'SUMMER10' ), $snapshot['coupons'] );
		$this->assertSame( array( 0 => 'flat_rate:1' ), $snapshot['shipping'] );
	}

	public function test_snapshot_strips_the_product_object() {
		// A WC_Product in the session serializes the whole post and rehydrates stale.
		// WooCommerce drops it in get_cart_for_session(); a filter can put it back.
		$snapshot = ExpressCartBackup::build_snapshot(
			array( 'a' => $this->cart_line( 11, 1, array( 'data' => new \stdClass() ) ) ),
			array(),
			array()
		);

		$this->assertArrayNotHasKey( 'data', $snapshot['contents']['a'] );
		$this->assertSame( 11, $snapshot['contents']['a']['product_id'] );
	}

	public function test_snapshot_keeps_third_party_line_data() {
		// Bookings, gift wrap, subscription meta: a restore that drops these gives the
		// shopper a cart that looks right and prices wrong.
		$snapshot = ExpressCartBackup::build_snapshot(
			array( 'a' => $this->cart_line( 11, 1, array( 'gift_message' => 'hello' ) ) ),
			array(),
			array()
		);

		$this->assertSame( 'hello', $snapshot['contents']['a']['gift_message'] );
	}

	public function test_snapshot_of_an_empty_cart_is_still_restorable() {
		// Express from a product page with nothing in the cart is the common case, and
		// it must still take a snapshot — otherwise the express product itself is left
		// behind after a cancel.
		$snapshot = ExpressCartBackup::build_snapshot( array(), array(), array() );

		$this->assertTrue( ExpressCartBackup::is_restorable( $snapshot ) );
		$this->assertSame( array(), $snapshot['contents'] );
	}

	public function test_snapshot_ignores_non_array_lines() {
		$snapshot = ExpressCartBackup::build_snapshot(
			array(
				'a' => $this->cart_line( 11, 1 ),
				'b' => 'corrupt',
			),
			array(),
			array()
		);

		$this->assertSame( array( 'a' ), array_keys( $snapshot['contents'] ) );
	}

	/**
	 * @dataProvider unrestorable_values
	 */
	public function test_refuses_anything_that_is_not_a_snapshot_of_this_version( $value ) {
		// Restoring a half-written or older-format value would overwrite a live cart
		// with nonsense, which is worse than not restoring at all.
		$this->assertFalse( ExpressCartBackup::is_restorable( $value ) );
	}

	public function unrestorable_values() {
		$valid = ExpressCartBackup::build_snapshot( array(), array(), array() );

		$wrong_version            = $valid;
		$wrong_version['version'] = ExpressCartBackup::VERSION + 1;

		$missing_coupons = $valid;
		unset( $missing_coupons['coupons'] );

		$bad_contents             = $valid;
		$bad_contents['contents'] = 'nope';

		return array(
			'null'             => array( null ),
			'empty string'     => array( '' ),
			'plain array'      => array( array( 'contents' => array() ) ),
			'future version'   => array( $wrong_version ),
			'missing coupons'  => array( $missing_coupons ),
			'contents not set' => array( $bad_contents ),
		);
	}

	public function test_valid_snapshot_is_restorable() {
		$this->assertTrue(
			ExpressCartBackup::is_restorable(
				ExpressCartBackup::build_snapshot(
					array( 'a' => $this->cart_line( 11, 1 ) ),
					array( 'X' ),
					array( 0 => 'free_shipping:2' )
				)
			)
		);
	}

	public function test_foreign_items_excludes_what_express_added() {
		// The two-tab case: express owns 'exp', a second tab added 'other'. Restoring
		// must put the snapshot back and keep 'other'.
		$foreign = ExpressCartBackup::foreign_items(
			array(
				'exp'   => $this->cart_line( 11, 1 ),
				'other' => $this->cart_line( 22, 2 ),
			),
			array( 'exp' )
		);

		$this->assertSame( array( 'other' ), array_keys( $foreign ) );
		$this->assertSame( 2, $foreign['other']['quantity'] );
	}

	public function test_foreign_items_keeps_everything_when_express_added_nothing() {
		$foreign = ExpressCartBackup::foreign_items(
			array( 'other' => $this->cart_line( 22, 1 ) ),
			array()
		);

		$this->assertSame( array( 'other' ), array_keys( $foreign ) );
	}

	public function test_foreign_items_compares_keys_as_strings() {
		// Cart keys are md5 hashes, but a numeric-looking key would compare loosely
		// against an int and silently drop a shopper's line.
		$foreign = ExpressCartBackup::foreign_items(
			array( '0' => $this->cart_line( 11, 1 ) ),
			array( 0 )
		);

		$this->assertSame( array(), $foreign );
	}
}
