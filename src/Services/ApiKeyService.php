<?php

namespace Monei\Services;

class ApiKeyService {
	private string $test_api_key;
	private string $live_api_key;
	private string $api_key_mode;
	private string $account_id;

	public function __construct() {
		// Load the API keys and mode from the database
		$this->test_api_key = get_option( 'monei_test_apikey', '' );
		$this->live_api_key = get_option( 'monei_live_apikey', '' );
		$this->api_key_mode = get_option( 'monei_apikey_mode', 'test' );
		$this->account_id   = get_option( 'monei_accountid' );
		add_action( 'init', array( $this, 'copyKeysToCentralSettings' ), 0 );
	}

	/**
	 * Get the appropriate API key based on the selected mode.
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return ( $this->api_key_mode === 'test' ) ? $this->test_api_key : $this->live_api_key;
	}

	/**
	 * Get the selected API key mode.
	 *
	 * @return bool
	 */
	public function is_test_mode(): bool {
		return $this->api_key_mode === 'test';
	}

	/**
	 * Get the account id.
	 *
	 * @return string
	 */
	public function get_account_id(): string {
		return $this->account_id;
	}

	/**
	 * Update the API keys and mode in the service.
	 * This can be called whenever the database is updated.
	 */
	public function update_keys(): void {
		$this->test_api_key = get_option( 'monei_test_apikey', '' );
		$this->live_api_key = get_option( 'monei_live_apikey', '' );
		$this->api_key_mode = get_option( 'monei_apikey_mode', 'test' );
	}

	public function copyKeysToCentralSettings() {
		add_filter(
			'option_woocommerce_monei_settings',
			function ( $default_params ) {
				$centralApiKey    = get_option( 'monei_apikey' );
				$centralAccountId = get_option( 'monei_accountid' );
				$ccApiKey         = $default_params['apikey'] ?? false;
				$ccAccountId      = $default_params['accountid'] ?? false;

				if ( empty( $centralApiKey ) && empty( $ccApiKey ) ) {
					return $default_params;
				}

				$keyToUse = ! empty( $centralApiKey ) ? $centralApiKey : $ccApiKey;

				if ( strpos( $keyToUse, 'pk_test_' ) === 0 ) {
					update_option( 'monei_test_apikey', $keyToUse );
					update_option( 'monei_apikey_mode', 'test' );
				} else {
					update_option( 'monei_live_apikey', $keyToUse );
					update_option( 'monei_apikey_mode', 'live' );
				}

				delete_option( 'monei_apikey' );

				if ( empty( $centralAccountId ) && ! empty( $ccAccountId ) ) {
					update_option( 'monei_accountid', $ccAccountId );
				}

				return $default_params;
			},
			1
		);
	}
}
