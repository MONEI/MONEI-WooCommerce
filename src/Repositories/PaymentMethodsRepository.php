<?php

namespace Monei\Repositories;

use Monei\MoneiClient;
use Exception;

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
		} catch ( Exception $e ) {
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
				set_transient( $this->fallbackKey( $transientKey ), $data, HOUR_IN_SECONDS );
			} else {
				// An empty answer marks every gateway unavailable, which takes
				// the payment methods off the checkout. The 30 second cache
				// makes that one failed call away at any moment, so fall back
				// to the last answer that worked.
				$data = get_transient( $this->fallbackKey( $transientKey ) );
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

	/**
	 * Key of the longer lived copy used when the API call fails.
	 */
	private function fallbackKey( string $transientKey ): string {
		return $transientKey . '_last_ok';
	}
}
