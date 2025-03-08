<?php

namespace Monei\Services\sdk;

use Monei\MoneiClient;
use Monei\Services\ApiKeyService;
use OpenAPI\Client\Configuration;

class MoneiSdkClientFactory {
	private ApiKeyService $apiKeyService;
	private $client;

	public function __construct( ApiKeyService $apiKeyService ) {
		$this->apiKeyService = $apiKeyService;
		$this->client        = null;
	}

	/**
	 * @return \Monei\MoneiClient
	 */
	public function get_client() {
		if ( $this->client === null ) {
			include_once WC_Monei()->plugin_path() . '/vendor/autoload.php';
			$config = Configuration::getDefaultConfiguration();
			$config->setUserAgent( 'MONEI/WooCommerce/' . WC_Monei()->version );
			$this->client = new MoneiClient( $this->apiKeyService->get_api_key(), $config );
		}
		return $this->client;
	}
}
