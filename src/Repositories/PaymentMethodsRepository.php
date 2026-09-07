<?php

namespace Monei\Repositories;

use Monei\MoneiClient;
use Exception;

class PaymentMethodsRepository implements PaymentMethodsRepositoryInterface {
	/**
	 * Cached marker for "the API said no". Distinct from an empty array so the
	 * cache can hold it: an empty array is falsy and would be re-fetched.
	 */
	private const UNAVAILABLE = array(
		'paymentMethods' => array(),
		'metadata' => array(),
	);

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
		// The account id no longer reaches the API — getAllowed() derives the account
		// from the API key. It still gates the call because it is what separates the
		// test cache from the live one, and because an unset one means the plugin is
		// not configured yet.
		if ( ! $this->accountId ) {
			return null;
		}
		try {
			// /allowed-payment-methods, the API key authenticated replacement for the
			// deprecated /payment-methods. Amount, currency and country are left out on
			// purpose: this repository is a container singleton that answers admin
			// screens as well as the checkout, so it has no one cart to describe.
			$response = $this->moneiClient->paymentMethods->getAllowed();
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
				if ( ! $data ) {
					// No answer has ever worked: a wrong or missing API key. An
					// empty array is falsy, so it never reached the cache and
					// every checkout render repeated the failing call. One
					// store did this 15 times a second for a week.
					$data = self::UNAVAILABLE;
				}
				// Without this every request during an outage repeats the
				// failing call.
				set_transient( $transientKey, $data, 30 );
			}
		}

		return $data === self::UNAVAILABLE ? array() : ( $data ?: array() );
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
