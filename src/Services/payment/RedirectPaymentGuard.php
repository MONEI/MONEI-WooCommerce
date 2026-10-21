<?php

namespace Monei\Services\payment;

/**
 * Checks that a payment named in a redirect URL belongs to the request.
 *
 * The `id` and `orderId` query arguments on a redirect back from MONEI are
 * attacker-controlled. Any shopper can put the ID of a payment they made into the
 * URL of another order. The payment fetched from the API is the only trusted
 * source, so its own `orderId` must tie it to the order or the user.
 */
class RedirectPaymentGuard {

	/**
	 * Whether the payment was created for this order.
	 *
	 * @param mixed      $payment_order_id `orderId` of the payment, from the API.
	 * @param int|string $order_id         WooCommerce order ID.
	 *
	 * @return bool
	 */
	public static function belongs_to_order( $payment_order_id, $order_id ) {
		if ( ! is_scalar( $payment_order_id ) || ! is_scalar( $order_id ) ) {
			return false;
		}

		$order_id = (string) $order_id;

		return '' !== $order_id && (string) $payment_order_id === $order_id;
	}

	/**
	 * Whether the order key in the URL is the key of the order.
	 *
	 * @param mixed  $submitted_key `key` query argument.
	 * @param string $order_key     Key stored on the order.
	 *
	 * @return bool
	 */
	public static function order_key_matches( $submitted_key, $order_key ) {
		if ( ! is_string( $submitted_key ) || ! is_string( $order_key ) || '' === $order_key ) {
			return false;
		}

		return hash_equals( $order_key, $submitted_key );
	}

	/**
	 * Whether the payment is the tokenization payment this user started.
	 *
	 * The "add payment method" flow sets `orderId` to `<user_id>generatetoken<random>`.
	 *
	 * @param mixed $payment_order_id `orderId` of the payment, from the API.
	 * @param int   $user_id          Current user ID.
	 *
	 * @return bool
	 */
	public static function belongs_to_user_token_request( $payment_order_id, $user_id ) {
		if ( ! is_scalar( $payment_order_id ) || $user_id <= 0 ) {
			return false;
		}

		return 0 === strpos( (string) $payment_order_id, $user_id . 'generatetoken' );
	}
}
