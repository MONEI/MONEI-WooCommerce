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
			$this->client = $this->build_client( $this->apiKeyService->get_api_key(), Configuration::getDefaultConfiguration() );
		}
		return $this->client;
	}

	/**
	 * Client for the key of a mode, whatever the selected mode.
	 *
	 * It gets its own Configuration: MoneiClient writes its key into the configuration, and the main client uses the shared default one.
	 *
	 * @param string $mode 'test' or 'live'.
	 */
	public function get_client_for_mode( string $mode ): MoneiClient {
		return $this->build_client( $this->apiKeyService->get_api_key_for_mode( $mode ), new Configuration() );
	}

	private function build_client( string $api_key, Configuration $config ): MoneiClient {
		include_once WC_Monei()->plugin_path() . '/vendor/autoload.php';
		$client = new MoneiClient( $api_key, $config );
		$client->setUserAgent(
			'MONEI/WooCommerce/' . WC_Monei()->version .
			' (WordPress v' . get_bloginfo( 'version' ) .
			'; Woo v' . WC()->version .
			'; PHP v' . phpversion() . ')'
		);
		return $client;
	}
}
