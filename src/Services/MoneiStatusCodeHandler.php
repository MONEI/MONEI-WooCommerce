<?php

namespace Monei\Services;

/**
 * Service for handling MONEI status codes and messages
 *
 * @since 6.4.1
 */
class MoneiStatusCodeHandler {

	/**
	 * Get a localized status message for a status code
	 *
	 * @param string $status_code MONEI status code (e.g., E000, E209).
	 *
	 * @return string Localized error message
	 */
	public function get_status_message( string $status_code ): string {
		switch ( $status_code ) {
			case 'E000':
				return __( 'Transaction approved', 'monei' );
			case 'E999':
				return __( 'Service internal error. Please contact support', 'monei' );
			case 'E101':
				return __( 'Error with payment processor configuration. Check this in your dashboard or contact MONEI for support', 'monei' );
			case 'E102':
				return __( 'Invalid or inactive MID. Please contact the acquiring entity', 'monei' );
			case 'E103':
				return __( 'Operation not allowed/configured for this merchant. Please contact the acquiring entity or MONEI for support', 'monei' );
			case 'E104':
				return __( 'Partial captures are not enabled in your account, please contact MONEI support', 'monei' );
			case 'E105':
				return __( 'MOTO Payment are not enabled in your account, please contact MONEI support', 'monei' );
			case 'E150':
				return __( 'Invalid or malformed request. Please check the message format', 'monei' );
			case 'E151':
				return __( 'Missing or malformed signature/auth', 'monei' );
			case 'E152':
				return __( 'Error while decrypting request', 'monei' );
			case 'E153':
				return __( 'Pre-authorization is expired and cannot be canceled or captured', 'monei' );
			case 'E154':
				return __( 'The payment date cannot be less than the cancellation or capture date', 'monei' );
			case 'E155':
				return __( 'The cancellation date exceeded the date allowed for pre-authorized operations', 'monei' );
			case 'E200':
				return __( 'Transaction failed during payment processing', 'monei' );
			case 'E201':
				return __( 'Transaction declined by the card-issuing bank', 'monei' );
			case 'E202':
				return __( 'Transaction declined by the issuing bank', 'monei' );
			case 'E203':
				return __( 'Payment method not allowed', 'monei' );
			case 'E204':
				return __( 'Wrong or not allowed currency', 'monei' );
			case 'E205':
				return __( 'Incorrect reference / transaction does not exist', 'monei' );
			case 'E207':
				return __( 'Transaction failed: process time exceeded', 'monei' );
			case 'E208':
				return __( 'Transaction is currently being processed', 'monei' );
			case 'E209':
				return __( 'Duplicated operation', 'monei' );
			case 'E210':
				return __( 'Wrong or not allowed payment amount', 'monei' );
			case 'E211':
				return __( 'Refund declined by processor', 'monei' );
			case 'E212':
				return __( 'Transaction has already been captured', 'monei' );
			case 'E213':
				return __( 'Transaction has already been canceled', 'monei' );
			case 'E214':
				return __( 'The amount to be captured cannot exceed the pre-authorized amount', 'monei' );
			case 'E215':
				return __( 'The transaction to be captured has not been pre-authorized yet', 'monei' );
			case 'E216':
				return __( 'The transaction to be canceled has not been pre-authorized yet', 'monei' );
			case 'E217':
				return __( 'Transaction denied by processor to avoid duplicated operations', 'monei' );
			case 'E218':
				return __( 'Error during payment request validation', 'monei' );
			case 'E219':
				return __( 'Refund declined due to exceeded amount', 'monei' );
			case 'E220':
				return __( 'Transaction has already been fully refunded', 'monei' );
			case 'E221':
				return __( 'Transaction declined due to insufficient funds', 'monei' );
			case 'E300':
				return __( 'The user has canceled the payment', 'monei' );
			case 'E301':
				return __( 'Waiting for the transaction to be completed', 'monei' );
			case 'E302':
				return __( 'No reason to decline', 'monei' );
			case 'E303':
				return __( 'Refund not allowed', 'monei' );
			case 'E304':
				return __( 'Transaction cannot be completed, violation of law', 'monei' );
			case 'E305':
				return __( 'Stop Payment Order', 'monei' );
			case 'E400':
				return __( 'Strong Customer Authentication required', 'monei' );
			case 'E401':
				return __( 'Transaction declined due to security restrictions', 'monei' );
			case 'E402':
				return __( '3D Secure authentication failed', 'monei' );
			case 'E403':
				return __( 'Authentication process timed out. Please try again', 'monei' );
			case 'E404':
				return __( 'An error occurred during the 3D Secure process', 'monei' );
			case 'E405':
				return __( 'Invalid or malformed 3D Secure request', 'monei' );
			case 'E406':
				return __( 'Exemption not allowed', 'monei' );
			case 'E407':
				return __( 'Exemption error', 'monei' );
			case 'E408':
				return __( 'Fraud control error', 'monei' );
			case 'E409':
				return __( 'External MPI received wrong. Please check the data', 'monei' );
			case 'E410':
				return __( 'External MPI not enabled. Please contact support', 'monei' );
			case 'E500':
				return __( 'Transaction confirmation rejected by the merchant', 'monei' );
			case 'E501':
				return __( 'Transaction declined during card payment process', 'monei' );
			case 'E502':
				return __( 'Card rejected: invalid card number', 'monei' );
			case 'E503':
				return __( 'Card rejected: wrong expiration date', 'monei' );
			case 'E504':
				return __( 'Card rejected: wrong CVC/CVV2 number', 'monei' );
			case 'E505':
				return __( 'Card number not registered', 'monei' );
			case 'E506':
				return __( 'Card is expired', 'monei' );
			case 'E507':
				return __( 'Error during payment authorization. Please try again', 'monei' );
			case 'E508':
				return __( 'Cardholder has canceled the payment', 'monei' );
			case 'E509':
				return __( 'Transaction declined: AMEX cards not accepted by payment processor', 'monei' );
			case 'E510':
				return __( 'Card blocked temporarily or under suspicion of fraud', 'monei' );
			case 'E511':
				return __( 'Card does not allow pre-authorization operations', 'monei' );
			case 'E512':
				return __( 'CVC/CVV2 number is required', 'monei' );
			case 'E513':
				return __( 'Unsupported card type', 'monei' );
			case 'E514':
				return __( 'Transaction type not allowed for this type of card', 'monei' );
			case 'E515':
				return __( 'Transaction declined by card issuer', 'monei' );
			case 'E516':
				return __( 'Implausible card data', 'monei' );
			case 'E517':
				return __( 'Incorrect PIN', 'monei' );
			case 'E518':
				return __( 'Transaction not allowed for cardholder', 'monei' );
			case 'E519':
				return __( 'The amount exceeds the card limit', 'monei' );
			case 'E600':
				return __( 'Transaction declined during ApplePay/GooglePay payment process', 'monei' );
			case 'E601':
				return __( 'Incorrect ApplePay or GooglePay configuration', 'monei' );
			case 'E620':
				return __( 'Transaction declined during PayPal payment process', 'monei' );
			case 'E621':
				return __( 'Transaction declined during PayPal payment process: invalid currency', 'monei' );
			case 'E640':
				return __( 'Bizum transaction declined after three authentication attempts', 'monei' );
			case 'E641':
				return __( 'Bizum transaction declined due to failed authorization', 'monei' );
			case 'E642':
				return __( 'Bizum transaction declined due to insufficient funds', 'monei' );
			case 'E643':
				return __( 'Bizum transaction canceled: the user does not want to continue', 'monei' );
			case 'E644':
				return __( 'Bizum transaction rejected by destination bank', 'monei' );
			case 'E645':
				return __( 'Bizum transaction rejected by origin bank', 'monei' );
			case 'E646':
				return __( 'Bizum transaction rejected by processor', 'monei' );
			case 'E647':
				return __( 'Bizum transaction failed while connecting with processor. Please try again', 'monei' );
			case 'E648':
				return __( 'Bizum transaction failed, payee is not found', 'monei' );
			case 'E649':
				return __( 'Bizum transaction failed, payer is not found', 'monei' );
			case 'E650':
				return __( 'Bizum REST not implemented', 'monei' );
			case 'E651':
				return __( 'Bizum transaction declined due to failed authentication', 'monei' );
			case 'E652':
				return __( 'The customer has disabled Bizum, please use another payment method', 'monei' );
			case 'E680':
				return __( 'Transaction declined during ClickToPay payment process', 'monei' );
			case 'E681':
				return __( 'Incorrect ClickToPay configuration', 'monei' );
			case 'E700':
				return __( 'Transaction declined during Cofidis payment process', 'monei' );
			default:
				// translators: %s is the status code from MONEI API.
				return sprintf( __( 'Unknown status code: %s', 'monei' ), $status_code );
		}
	}

	/**
	 * Check if a status code indicates success
	 *
	 * @param string|null $status_code MONEI status code.
	 *
	 * @return bool True if success, false otherwise
	 */
	public function is_success_code( ?string $status_code ): bool {
		return 'E000' === $status_code;
	}

	/**
	 * Check if a status code indicates an error
	 *
	 * @param string|null $status_code MONEI status code.
	 *
	 * @return bool True if error, false otherwise
	 */
	public function is_error_code( ?string $status_code ): bool {
		return null !== $status_code && 'E000' !== $status_code;
	}

	/**
	 * Extract error information from API exception
	 *
	 * @param \Monei\ApiException $exception API exception from MONEI SDK.
	 *
	 * @return array{message: string, statusCode: string|null, rawMessage: string|null}
	 */
	public function parse_api_exception( $exception ): array {
		$response_body = json_decode( $exception->getResponseBody(), true );

		// The API can return either:
		// 1. A MONEI status code like "E209" (which we translate to user-friendly messages)
		// 2. An HTTP status code like 400 with a message (which is already user-friendly)
		$status_code_value = $response_body['statusCode'] ?? null;
		$raw_message       = $response_body['message'] ?? $exception->getMessage();

		// Check if statusCode is a MONEI status code (E-prefixed like "E209")
		// vs HTTP status code (numeric like 400)
		$is_monei_status_code = $status_code_value && is_string( $status_code_value ) && strpos( $status_code_value, 'E' ) === 0;

		if ( $is_monei_status_code ) {
			// We have a MONEI status code - translate it to user-friendly message
			$message     = $this->get_status_message( $status_code_value );
			$status_code = $status_code_value;
		} else {
			// HTTP status code or no status code - use the raw message as it's already user-friendly
			$message     = $raw_message;
			$status_code = null;
		}

		// Final fallback
		if ( ! $message ) {
			$message = $exception->getMessage();
		}

		return array(
			'message'    => $message,
			'statusCode' => $status_code,
			'rawMessage' => $raw_message,
		);
	}
}
