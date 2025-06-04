<?php

namespace Monei\Repositories;

use Monei\MoneiClient;

class PaymentMethodsRepository implements PaymentMethodsRepositoryInterface {
	private $accountId;
    private MoneiClient $moneiClient;

    public function __construct( string $accountId, MoneiClient $moneiClient) {
		$this->accountId = $accountId;
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

		return json_decode($response, true);
	}

	/**
	 * Get payment methods (fetch from transient or API).
	 */
	public function getPaymentMethods(): array {
		return $this->fetchFromAPI() ?: array();
	}
}
