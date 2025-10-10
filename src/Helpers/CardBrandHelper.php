<?php

namespace Monei\Helpers;

use Monei\Services\PaymentMethodsService;

class CardBrandHelper {

	private $paymentMethodsService;

	public function __construct( PaymentMethodsService $paymentMethodsService ) {
		$this->paymentMethodsService = $paymentMethodsService;
	}

	/**
	 * Get card brand configuration for JavaScript.
	 *
	 * @return array Array of brand configs with url, width, height, title.
	 */
	public function getCardBrandsConfig(): array {
		$brands = $this->paymentMethodsService->getCardBrands();
		$config = array();

		foreach ( $brands as $brand ) {
			$config[ $brand ] = array(
				'url'    => WC_Monei()->image_url( "cards/{$brand}.svg" ),
				'width'  => 40,
				'height' => 24,
				'title'  => $this->getBrandTitle( $brand ),
			);
		}

		// Add default fallback
		$config['default'] = array(
			'url'    => WC_Monei()->image_url( 'cards/default.svg' ),
			'width'  => 40,
			'height' => 24,
			'title'  => __( 'Card', 'monei' ),
		);

		return $config;
	}

	/**
	 * Get human-readable brand title.
	 *
	 * @param string $brand Brand code.
	 * @return string Brand title.
	 */
	private function getBrandTitle( string $brand ): string {
		$titles = array(
			'visa'       => 'Visa',
			'mastercard' => 'Mastercard',
			'amex'       => 'American Express',
			'discover'   => 'Discover',
			'diners'     => 'Diners Club',
			'jcb'        => 'JCB',
			'maestro'    => 'Maestro',
			'unionpay'   => 'UnionPay',
		);

		return $titles[ $brand ] ?? ucfirst( $brand );
	}
}
