<?php

namespace Monei\Repositories;

use Monei\Repositories\PaymentMethodsRepositoryInterface;

class PaymentMethodsRepository implements PaymentMethodsRepositoryInterface {
	private $accountId;

	public function __construct( string $accountId ) {
		$this->accountId = $accountId;
	}

	/**
	 * Fetch payment methods from the API.
	 */
	private function fetchFromAPI(): ?array {
		$response = wp_remote_get( 'https://api.monei.com/v1/payment-methods?accountId=' . $this->accountId );
		if ( is_wp_error( $response ) ) {
			return null;
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Generate a transient key.
	 */
	private function generateTransientKey( string $key ): string {
		return 'payment_methods_' . md5( $key );
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
				set_transient( $transientKey, $data, DAY_IN_SECONDS );
			}
		}

		return $data ?: array();
	}
}
