<?php

namespace Monei\Services\payment;

use Monei\ApiException;
use Monei\Services\sdk\MoneiSdkClientFactory;
use OpenAPI\Client\Configuration;

/**
 * API Helper Class
 *
 * @since 5.0
 * @version 5.0
 */
class MoneiPaymentServices {

	/**
	 * @var \Monei\MoneiClient
	 */
	protected $client;

	/**
	 * Holds the order.
	 *
	 * @var int|\WC_Order|null
	 */
	protected $order = null;

	public function __construct( MoneiSdkClientFactory $sdkClientFactory ) {
		$this->client = $sdkClientFactory;
	}

	/**
	 * @param int|\WC_Order $order
	 */
	public function set_order( $order ) {
		$order       = is_int( $order ) ? wc_get_order( $order ) : $order;
		$this->order = $order;
	}

	/**
	 * @param $body
	 * @param $signature
	 *
	 * @return object
	 * @throws \OpenAPI\Client\ApiException
	 */
	public function verify_signature( $body, $signature ) {
		$client = $this->client->get_client();
		return $client->verifySignature( $body, $signature );
	}

	/**
	 * https://docs.monei.com/api/#operation/payments_create
	 *
	 * @param array $payload
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public function create_payment( $payload ) {
		$client = $this->client->get_client();
		return $client->payments->create( $payload );
	}

	/**
	 * https://docs.monei.com/api/#operation/payments_confirm
	 *
	 * @param string $id The payment ID (required)
	 * @param array  $payload
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public function confirm_payment( $id, $payload ) {
		$client = $this->client->get_client();
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
	public function get_payment( $payment_id ) {
		$client = $this->client->get_client();
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
	public function capture_payment( $payment_id, $amount ) {
		$client = $this->client->get_client();
		return $client->payments->capture( $payment_id, array( 'amount' => $amount ) );
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
	public function cancel_payment( $payment_id ) {
		$client = $this->client->get_client();
		return $client->payments->cancel( $payment_id, array( 'cancellationReason' => 'requested_by_customer' ) );
	}

	/**
	 * https://docs.monei.com/api/#operation/payments_cancel
	 *
	 * @param $payment_id
	 * @param $amount
	 * @param string     $refund_reason
	 *
	 * @return \OpenAPI\Client\Model\Payment
	 * @throws \OpenAPI\Client\ApiException
	 */
	public function refund_payment( $payment_id, $amount, $refund_reason = 'requested_by_customer' ) {
		$client = $this->client->get_client();
		return $client->payments->refund(
			$payment_id,
			array(
				'amount'       => (int) $amount,
				'refundReason' => $refund_reason,
			)
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
	public function recurring_payment( $sequence_id, $payload ) {
		$client = $this->client->get_client();
		return $client->payments->recurring( $sequence_id, $payload );
	}


	/**
	 * @param $domain
	 *
	 * @return \OpenAPI\Client\Model\InlineResponse200
	 * @throws ApiException
	 */
	public function register_apple_domain( $domain ) {
		$client = $this->client->get_client();
		return $client->applePayDomain->register( array( 'domainName' => $domain ) );
	}
}
