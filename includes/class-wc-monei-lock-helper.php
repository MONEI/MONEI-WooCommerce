<?php
/**
 * MONEI Lock Helper
 * Provides locking mechanism to prevent concurrent payment processing
 *
 * @package MONEI
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Lock Helper Class
 * Provides shared locking functionality for payment processing
 */
class WC_Monei_Lock_Helper {

	/**
	 * Acquire a lock using WordPress object cache or transients.
	 *
	 * @param string $lock_key   The lock key.
	 * @param mixed  $lock_value The lock value.
	 * @param int    $timeout    Lock timeout in seconds (default 60).
	 * @return bool True if lock acquired, false otherwise.
	 */
	public static function acquire_lock( $lock_key, $lock_value, $timeout = 60 ) {
		// Try wp_cache_add first (atomic, fails if key exists)
		// This requires object cache, but works with default WordPress cache
		$acquired = wp_cache_add( $lock_key, $lock_value, 'monei_locks', $timeout );

		if ( ! $acquired ) {
			// Lock already held by another process
			// Check if it's stale by trying to get it
			$existing_value = wp_cache_get( $lock_key, 'monei_locks' );
			if ( false === $existing_value ) {
				// Cache expired between checks, try again
				return wp_cache_add( $lock_key, $lock_value, 'monei_locks', $timeout );
			}
			return false;
		}

		return true;
	}

	/**
	 * Release a lock using WordPress object cache.
	 *
	 * @param string $lock_key   The lock key.
	 * @param mixed  $lock_value The lock value to verify ownership.
	 * @return void
	 */
	public static function release_lock( $lock_key, $lock_value ) {
		$existing_value = wp_cache_get( $lock_key, 'monei_locks' );

		// Only delete if we own the lock.
		if ( $existing_value === $lock_value ) {
			wp_cache_delete( $lock_key, 'monei_locks' );
		}
	}

	/**
	 * Generate a consistent lock key for a payment ID.
	 *
	 * @param string $payment_id The MONEI payment ID.
	 * @return string The lock key.
	 */
	public static function get_payment_lock_key( $payment_id ) {
		return 'monei_payment_' . $payment_id;
	}
}
