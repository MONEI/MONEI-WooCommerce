<?php

namespace Monei\Repositories;

use Monei\MoneiClient;

class PaymentMethodsRepository implements PaymentMethodsRepositoryInterface {
	private $accountId;
	private MoneiClient $moneiClient;

	public function __construct( string $accountId, MoneiClient $moneiClient ) {
		$this->accountId   = $accountId;
		$this->moneiClient = $moneiClient;
	}

	/**
	 * Fetch payment methods from the API.
	 */
	private function fetchFromAPI(): ?array {
		if ( ! $this->accountId ) {
			return null;
		}
		try {
			$response = $this->moneiClient->paymentMethods->get( $this->accountId );
		} catch ( \Exception $e ) {
			$response = null;
		}

		return $response ? json_decode( $response, true ) : array();
	}

	/**
	 * Get payment methods (fetch from transient or API).
	 */
	public function getPaymentMethods(): array {
		$transientKey = $this->generateTransientKey( $this->accountId );
		$data         = get_transient( $transientKey );

		if ( ! $data ) {
			$data = $this->fetchFromAPI();
			if ( $data ) {
				set_transient( $transientKey, $data, 30 );
			}
		}

		return $data ?: array();
	}

	/**
	 * Generate a transient key.
	 */
	private function generateTransientKey( string $key ): string {
		return 'payment_methods_' . md5( $key );
	}
}
