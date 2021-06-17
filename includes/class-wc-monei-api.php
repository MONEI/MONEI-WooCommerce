<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * API Helper Class
 *
 * @since 5.0
 * @version 5.0
 */
class WC_Monei_API {

	const OPTION_API_KEY = 'apikey';

	/**
	 * @var string
	 */
	protected static $api_key;

	/**
	 * @var \Monei\MoneiClient
	 */
	protected static $client;

	/**
	 * Get API Key.
	 * @return false|string
	 */
	protected static function get_api_key() {
		if ( isset( self::$api_key ) ) {
			return self::$api_key;
		}

		return monei_get_settings( self::OPTION_API_KEY );
	}

	/**
	 * @return \Monei\MoneiClient
	 */
	protected static function get_client() {
		if ( isset( self::$client ) ) {
			return self::$client;
		}

		include_once WC_Monei()->plugin_path() . '/vendor/autoload.php';
		self::$client = new Monei\MoneiClient( self::get_api_key() );
		return self::$client;
	}

	/**
	 * @param $body
	 * @param $signature
	 *
	 * @return object
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function verify_signature( $body, $signature ) {
		$client = self::get_client();
		return $client->verifySignature( $body, $signature );
	}

	/**
	 * https://docs.monei.com/api/#operation/payments_create
	 * @param array $payload
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function create_payment( $payload ) {
		$client = self::get_client();
		return $client->payments->create( $payload );
	}

	/**
	 * Get the details of a payment that has previously been created. Supply the unique payment ID that was returned from your previous request.
	 * https://docs.monei.com/api/#operation/payments_get
	 *
	 * @param string $payment_id
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function get_payment( $payment_id ) {
		$client = self::get_client();
		return $client->payments->get( $payment_id, );
	}

	/**
	 * https://docs.monei.com/api/#operation/payments_cancel
	 *
	 * @param $payment_id
	 * @param $amount
	 * @param string $refund_reason
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function refund_payment( $payment_id, $amount, $refund_reason = 'requested_by_customer' ) {
		$client = self::get_client();
		return $client->payments->refund(
			$payment_id,
			[
				'amount' => (int) $amount,
				'refundReason' => $refund_reason,
			]
		);
	}

}

