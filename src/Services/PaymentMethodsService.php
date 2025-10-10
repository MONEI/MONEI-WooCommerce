<?php

namespace Monei\Services;

use Monei\Repositories\PaymentMethodsRepositoryInterface;

class PaymentMethodsService {

	public const GOOGLE_API = 'googlePay';
	public const APPLE_API  = 'applePay';
	private $repository;

	public function __construct( PaymentMethodsRepositoryInterface $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Parse and return enabled payment methods.
	 */
	public function getEnabledPaymentMethods(): array {
		$data = $this->repository->getPaymentMethods();

		$enabledMethods = array();
		foreach ( $data['paymentMethods'] ?? array() as $method ) {
			$metadata                  = $data['metadata'][ $method ] ?? array();
			$enabledMethods[ $method ] = array(
				'countries' => $metadata['countries'] ?? null,
				'details'   => $metadata,
			);
		}

		return $enabledMethods;
	}

	public function isGoogleEnabled(): bool {
		$enabledMethods = $this->getEnabledPaymentMethods();
		return isset( $enabledMethods[ self::GOOGLE_API ] );
	}

	public function isAppleEnabled(): bool {
		$enabledMethods = $this->getEnabledPaymentMethods();
		return isset( $enabledMethods[ self::APPLE_API ] );
	}

	/**
	 * Get available card brands from MONEI API metadata.
	 *
	 * @return array Array of lowercase brand names (e.g., ['visa', 'mastercard', 'amex']).
	 */
	public function getCardBrands(): array {
		$data = $this->repository->getPaymentMethods();

		// Extract card brands from metadata.card.brands
		if ( isset( $data['metadata']['card']['brands'] ) && is_array( $data['metadata']['card']['brands'] ) ) {
			return array_map( 'strtolower', $data['metadata']['card']['brands'] );
		}

		// Fallback to common brands if API doesn't return any
		return array( 'visa', 'mastercard', 'amex', 'discover' );
	}

	/**
	 * Get availability of a specific payment method.
	 *
	 * @param string $methodId Payment method ID (e.g., 'bizum', 'card').
	 * @return array|null Availability details or null if the method is not enabled.
	 */
	public function getMethodAvailability( string $methodId ): ?array {
		$paymentData      = $this->repository->getPaymentMethods();
		$methodIdToApiMap = array(
			'monei'              => 'card',
			'monei_apple_google' => 'applePay',
			'monei_google'       => 'googlePay',
			'monei_bizum'        => 'bizum',
			'monei_mbway'        => 'mbway',
			'monei_multibanco'   => 'multibanco',
			'monei_paypal'       => 'paypal',
		);

		if ( isset( $methodIdToApiMap[ $methodId ] ) ) {
			$apiName = $methodIdToApiMap[ $methodId ];
			if ( in_array( $apiName, $paymentData['paymentMethods'] ?? array(), true ) ) {
				$metadata = $paymentData['metadata'][ $apiName ] ?? array();
				$data     = array(
					'enabled'   => true,
					'countries' => $metadata['countries'] ?? null,
					'details'   => $metadata,
				);
				if ( $methodId === 'monei_apple_google' ) {
					$data['googlePay'] = in_array( 'googlePay', $paymentData['paymentMethods'], true );
					$data['applePay']  = in_array( 'applePay', $paymentData['paymentMethods'], true );
				}
				return $data;
			}
		}
		return null;
	}
}
