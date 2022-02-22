<?php

use OpenAPI\Client\Configuration;

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
	 * Holds the order.
	 * @var int|WC_Order|null
	 */
	protected static $order = null;

	/**
	 * Get API Key.
	 * @return false|string
	 */
	protected static function get_api_key() {
		if ( isset( self::$api_key ) ) {
			return self::$api_key;
		}

		self::$api_key = monei_get_settings( self::OPTION_API_KEY, monei_get_option_key_from_order( self::$order ) );
		return self::$api_key;
	}

	/**
	 * @param int|WC_Order $order
	 */
	public static function set_order( $order ) {
		$order       = is_int( $order ) ? wc_get_order( $order ) : $order;
		self::$order = $order;
	}

	/**
	 * @return \Monei\MoneiClient
	 */
	protected static function get_client() {
		if ( isset( self::$client ) ) {
			return self::$client;
		}

		include_once WC_Monei()->plugin_path() . '/vendor/autoload.php';
		$config = Configuration::getDefaultConfiguration();
		$config->setUserAgent( 'MONEI/WooCommerce/' . WC_Monei()->version );
		self::$client = new Monei\MoneiClient( self::get_api_key(), $config );
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
	 * https://docs.monei.com/api/#operation/payments_confirm
	 *
	 * @param string $id The payment ID (required)
	 * @param array $payload
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function confirm_payment( $id, $payload ) {
		$client = self::get_client();
		return $client->payments->confirm( $id, $payload );
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
		return $client->payments->get( $payment_id );
	}

	/**
	 * Capture the payment of an existing, uncaptured, payment. This is the second half of the two-step payment flow, where first you created a payment with the transactionType set to AUTH.
	 * Uncaptured payments expire exactly seven days after they are created. If they are not captured by that point in time, they will be marked as expired and will no longer be capturable.
	 * https://docs.monei.com/api/#operation/payments_capture
	 *
	 * @param string $payment_id
	 * @param string $amount
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function capture_payment( $payment_id, $amount ) {
		$client = self::get_client();
		return $client->payments->capture( $payment_id, [ 'amount' => $amount ] );
	}

	/**
	 * Release customer's funds that were reserved earlier. You can only cancel a payment with the AUTHORIZED status.
	 * This is the second half of the two-step payment flow, where first you created a payment with the transactionType set to AUTH.
	 * https://docs.monei.com/api/#operation/payments_cancel
	 *
	 * @param string $payment_id
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function cancel_payment( $payment_id ) {
		$client = self::get_client();
		return $client->payments->cancel( $payment_id, [ 'cancellationReason' => 'requested_by_customer' ] );
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

	/**
	 * https://docs.monei.com/api/#operation/payments_recurring
	 *
	 * @param string $sequence_id
	 * @param array  $payload
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function recurring_payment( $sequence_id, $payload ) {
		$client = self::get_client();
		return $client->payments->recurring( $sequence_id, $payload );
	}


	/**
	 * @param $domain
	 *
	 * @return \OpenAPI\Client\Model\InlineResponse200
	 * @throws \OpenAPI\Client\ApiException
	 */
	public static function register_apple_domain( $domain ) {
		$client = self::get_client();
		return $client->applePayDomain->register( [ 'domainName' => $domain ] );
	}

}

