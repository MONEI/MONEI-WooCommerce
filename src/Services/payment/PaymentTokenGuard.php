<?php

namespace Monei\Services\payment;

/**
 * Checks that a saved payment token named at checkout belongs to the shopper.
 *
 * The token ID comes from `$_POST`. WooCommerce checks the owner only on delete and
 * set-default, not at checkout, so without this check a shopper can post the token ID
 * of another user and pay with that user's saved card.
 */
class PaymentTokenGuard {

	/**
	 * Whether the token exists and is owned by the current user.
	 *
	 * The owner is compared with the current user, as WooCommerce core does, not with
	 * the order's customer: a logged-in buyer can pay an order that has no user.
	 *
	 * @param int|null $token_user_id   Owner of the token, or null when the token does not exist.
	 * @param int      $current_user_id Current user ID, 0 for a guest.
	 *
	 * @return bool
	 */
	public static function belongs_to( ?int $token_user_id, int $current_user_id ): bool {
		return null !== $token_user_id && 0 !== $current_user_id && $token_user_id === $current_user_id;
	}
}
