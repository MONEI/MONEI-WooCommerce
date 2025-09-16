<?php

namespace Monei\Services\sdk;

use Monei\Configuration;
use Monei\MoneiClient;
use Monei\Services\ApiKeyService;

class MoneiSdkClientFactory {
	private ApiKeyService $apiKeyService;
	private $client;

	public function __construct( ApiKeyService $apiKeyService ) {
		$this->apiKeyService = $apiKeyService;
		$this->client        = null;
	}

	/**
	 * @return MoneiClient
	 */
	public function get_client() {
		if ( $this->client === null ) {
			include_once WC_Monei()->plugin_path() . '/vendor/autoload.php';
			$config       = Configuration::getDefaultConfiguration();
			$this->client = new MoneiClient( $this->apiKeyService->get_api_key(), $config );
			$this->client->setUserAgent(
				'MONEI/WooCommerce/' . WC_Monei()->version .
				' (WordPress v' . get_bloginfo( 'version' ) .
				'; Woo v' . WC()->version .
				'; PHP v' . phpversion() . ')'
			);      }
		return $this->client;
	}
}
