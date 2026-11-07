<?php

namespace Monei\Services\payment;

/**
 * The `sessionId` to send with a payment token that MONEI.js made in the browser.
 *
 * MONEI binds the token to the `sessionId` the page gave MONEI.js: the WooCommerce
 * session ID when the page was rendered. When a guest creates an account at checkout,
 * WooCommerce logs the guest in and moves the session to the user ID in the same
 * request, before the gateway builds the payment. The current session ID is then the
 * user ID, and MONEI rejects the token with "Invalid payment method".
 */
class TokenSessionId {

	/**
	 * Guest session ID that WooCommerce moved to a user in this request.
	 *
	 * @var string
	 */
	private static $guest_session_id = '';

	/**
	 * Hooked to `woocommerce_guest_session_to_user_id`.
	 *
	 * @param mixed $guest_session_id Session ID before the login.
	 *
	 * @return void
	 */
	public static function remember_guest_session_id( $guest_session_id ) {
		self::$guest_session_id = (string) $guest_session_id;
	}

	/**
	 * Session ID that the browser token was made with.
	 *
	 * @param string $current_session_id Current WooCommerce session ID.
	 *
	 * @return string
	 */
	public static function for_token( string $current_session_id ): string {
		return '' !== self::$guest_session_id ? self::$guest_session_id : $current_session_id;
	}
}
