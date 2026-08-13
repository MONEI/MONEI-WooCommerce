<?php
/**
 * Save and restore of a shopper's cart around a product page express payment.
 *
 * @package Monei
 */

namespace Monei\Services\express;

use WC_Cart;
use WC_Monei_Logger;
use WC_Session;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the shopper's cart safe while a product page express payment borrows it.
 *
 * Express checkout from a product page has to charge for that one product, and the
 * only cart WooCommerce has is the live one. This class takes a snapshot first, and
 * puts it back on every way out of the flow.
 *
 * ⚠️ This is a deliberate divergence from the reference implementation. Stripe for
 * WooCommerce 10.8.5 keeps no backup at all: it persists `chosen_shipping_methods`
 * and calls `empty_cart()` with nothing saved, so a cancelled express payment throws
 * away whatever the shopper had collected. That is a support ticket, not a pattern.
 *
 * The snapshot lives in the WooCommerce session, where WooCommerce already keeps the
 * cart itself. Backup and cart therefore share a lifetime and cannot drift apart, and
 * it works the same for guests, who are most of the traffic on a product page. User
 * meta would need a second code path and a cleanup obligation, and a stale cart
 * resurfacing in someone's account weeks later is worse than losing a backup when a
 * session expires — if the session is gone, so is the cart it was protecting.
 */
class ExpressCartBackup {

	/**
	 * Session key holding the snapshot.
	 */
	const SESSION_KEY = 'monei_express_cart_backup';

	/**
	 * Snapshot format. Bumped when the shape changes, so a snapshot written by an
	 * older plugin version is discarded instead of restored wrongly.
	 */
	const VERSION = 1;

	/**
	 * Takes a snapshot of the current cart, unless one is already held.
	 *
	 * Never overwrites: a second express attempt while a backup is open would
	 * otherwise snapshot the single express product and lose the real cart.
	 *
	 * @return bool True when a snapshot is held afterwards.
	 */
	public function save() {
		$session = $this->get_session();

		if ( ! $session instanceof WC_Session || ! WC()->cart instanceof WC_Cart ) {
			return false;
		}

		if ( $this->has_backup() ) {
			return true;
		}

		$session->set(
			self::SESSION_KEY,
			self::build_snapshot(
				(array) WC()->cart->get_cart_for_session(),
				(array) WC()->cart->get_applied_coupons(),
				(array) $session->get( 'chosen_shipping_methods', array() )
			)
		);

		return true;
	}

	/**
	 * Records which cart items the express flow itself put in the cart.
	 *
	 * Restore keeps everything else, which is how a second browser tab editing the
	 * cart mid-flow survives.
	 *
	 * @param string[] $keys Cart item keys.
	 *
	 * @return void
	 */
	public function remember_express_items( array $keys ) {
		$backup  = $this->get_backup();
		$session = $this->get_session();

		if ( null === $backup || ! $session instanceof WC_Session ) {
			return;
		}

		$backup['express_keys'] = array_values( array_map( 'strval', $keys ) );

		$session->set( self::SESSION_KEY, $backup );
	}

	/**
	 * @return bool
	 */
	public function has_backup() {
		return null !== $this->get_backup();
	}

	/**
	 * Puts the shopper's cart back and drops the snapshot.
	 *
	 * @return bool True when the cart was restored, false when there was nothing to
	 *              restore or the restore failed.
	 */
	public function restore() {
		$backup = $this->get_backup();

		if ( null === $backup ) {
			return false;
		}

		$session = $this->get_session();
		$cart    = WC()->cart;

		if ( ! $session instanceof WC_Session || ! $cart instanceof WC_Cart ) {
			return false;
		}

		// Anything the shopper has in the cart now that express did not put there came
		// from somewhere else — a second tab, most likely — and must survive.
		$foreign = self::foreign_items(
			(array) $cart->get_cart_for_session(),
			isset( $backup['express_keys'] ) ? (array) $backup['express_keys'] : array()
		);

		try {
			$cart->empty_cart();

			$session->set( 'cart', $backup['contents'] );
			$session->set( 'applied_coupons', $backup['coupons'] );
			$session->set( 'chosen_shipping_methods', $backup['shipping'] );

			// Rebuilds the product objects and revalidates every line, which is what
			// makes a snapshot safe to hold across a session.
			$cart->get_cart_from_session();

			$this->add_items( $foreign );

			$cart->calculate_totals();
		} catch ( Exception $e ) {
			// The snapshot stays in the session on failure, so a later exit path can
			// try again rather than the shopper being left with whatever is there now.
			$this->fail( $backup, 'Express checkout could not restore the cart: ' . $e->getMessage() );

			return false;
		}

		// A snapshot that held items and produced an empty cart is a silent basket
		// wipe, which is the one outcome this class exists to prevent.
		if ( ! empty( $backup['contents'] ) && 0 === $cart->get_cart_contents_count() ) {
			$this->fail( $backup, 'Express checkout restored an empty cart from a snapshot holding ' . count( $backup['contents'] ) . ' item(s).' );

			return false;
		}

		$session->set( self::SESSION_KEY, null );

		return true;
	}

	/**
	 * Drops the snapshot without restoring, for when the express order was placed and
	 * the old cart is genuinely finished with.
	 *
	 * @return void
	 */
	public function forget() {
		$session = $this->get_session();

		if ( $session instanceof WC_Session ) {
			$session->set( self::SESSION_KEY, null );
		}
	}

	/**
	 * Builds the stored form of a cart.
	 *
	 * `get_cart_for_session()` has already dropped the `WC_Product` object from each
	 * line; this strips any that a filter put back, because a product object in the
	 * session serializes the whole post and rehydrates stale.
	 *
	 * @param array<string, mixed>  $contents Cart contents in session form.
	 * @param array<int, string>    $coupons  Applied coupon codes.
	 * @param array<int|string, mixed> $shipping Chosen shipping methods.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_snapshot( array $contents, array $coupons, array $shipping ) {
		$clean = array();

		foreach ( $contents as $key => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			unset( $item['data'] );

			$clean[ $key ] = $item;
		}

		return array(
			'version'      => self::VERSION,
			'created'      => time(),
			'contents'     => $clean,
			'coupons'      => array_values( $coupons ),
			'shipping'     => $shipping,
			'express_keys' => array(),
		);
	}

	/**
	 * Cart lines that the express flow did not add.
	 *
	 * @param array<string, mixed> $contents     Current cart in session form.
	 * @param string[]             $express_keys Keys express added.
	 *
	 * @return array<string, mixed>
	 */
	public static function foreign_items( array $contents, array $express_keys ) {
		$foreign = array();

		foreach ( $contents as $key => $item ) {
			if ( in_array( (string) $key, array_map( 'strval', $express_keys ), true ) ) {
				continue;
			}

			if ( is_array( $item ) ) {
				$foreign[ $key ] = $item;
			}
		}

		return $foreign;
	}

	/**
	 * Whether a value read back out of the session is a snapshot this version wrote.
	 *
	 * @param mixed $backup Value from the session.
	 *
	 * @return bool
	 */
	public static function is_restorable( $backup ) {
		return is_array( $backup )
			&& isset( $backup['version'] ) && self::VERSION === $backup['version']
			&& isset( $backup['contents'] ) && is_array( $backup['contents'] )
			&& isset( $backup['coupons'] ) && is_array( $backup['coupons'] )
			&& isset( $backup['shipping'] ) && is_array( $backup['shipping'] );
	}

	/**
	 * Re-adds lines through the ordinary cart API, so stock and validation still apply.
	 *
	 * @param array<string, mixed> $items Cart lines in session form.
	 *
	 * @return void
	 */
	private function add_items( array $items ) {
		foreach ( $items as $item ) {
			if ( empty( $item['product_id'] ) ) {
				continue;
			}

			$extra = $item;
			unset(
				$extra['key'],
				$extra['product_id'],
				$extra['variation_id'],
				$extra['variation'],
				$extra['quantity'],
				$extra['data'],
				$extra['data_hash'],
				$extra['line_tax_data'],
				$extra['line_subtotal'],
				$extra['line_subtotal_tax'],
				$extra['line_total'],
				$extra['line_tax']
			);

			WC()->cart->add_to_cart(
				(int) $item['product_id'],
				isset( $item['quantity'] ) ? (int) $item['quantity'] : 1,
				isset( $item['variation_id'] ) ? (int) $item['variation_id'] : 0,
				isset( $item['variation'] ) && is_array( $item['variation'] ) ? $item['variation'] : array(),
				$extra
			);
		}
	}

	/**
	 * Never lets a failed restore pass silently: the shopper is told and the failure
	 * is logged.
	 *
	 * ⚠️ Only the shape of the snapshot is logged, never the snapshot. Cart extensions
	 * keep shopper-entered values — gift messages, engraving, custom fields — in line
	 * item metadata, and the log is not the place for those. The snapshot itself stays
	 * in the session, which is where a later exit path recovers it from.
	 *
	 * @param array<string, mixed> $backup  Snapshot that failed to restore.
	 * @param string               $message Log message.
	 *
	 * @return void
	 */
	private function fail( array $backup, $message ) {
		WC_Monei_Logger::log( $message, WC_Monei_Logger::LEVEL_ERROR );
		WC_Monei_Logger::log(
			sprintf(
				'Express checkout snapshot: version %s, %d item(s).',
				isset( $backup['version'] ) ? (string) $backup['version'] : 'unknown',
				isset( $backup['contents'] ) ? count( (array) $backup['contents'] ) : 0
			),
			WC_Monei_Logger::LEVEL_ERROR
		);

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice(
				__( 'We could not put your cart back after the express checkout. Please check your cart before ordering.', 'monei' ),
				'error'
			);
		}
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function get_backup() {
		$session = $this->get_session();

		if ( ! $session instanceof WC_Session ) {
			return null;
		}

		$backup = $session->get( self::SESSION_KEY, null );

		return self::is_restorable( $backup ) ? $backup : null;
	}

	/**
	 * @return WC_Session|null
	 */
	private function get_session() {
		$session = function_exists( 'WC' ) ? WC()->session : null;

		return $session instanceof WC_Session ? $session : null;
	}
}
