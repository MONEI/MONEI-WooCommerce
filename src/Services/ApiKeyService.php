<?php

namespace Monei\Services;

class ApiKeyService {
	private string $test_api_key;
	private string $live_api_key;
	private string $api_key_mode;
	private string $test_account_id;
	private string $live_account_id;


	public function __construct() {
		// Load the API keys and mode from the database
		$this->test_api_key    = get_option( 'monei_test_apikey', '' );
		$this->live_api_key    = get_option( 'monei_live_apikey', '' );
		$this->api_key_mode    = get_option( 'monei_apikey_mode', 'test' );
		$this->test_account_id = get_option( 'monei_test_accountid', '' );
		$this->live_account_id = get_option( 'monei_live_accountid', '' );

		// Copy the API keys to the central settings when the plugin is activated or updated
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
		return ( $this->api_key_mode === 'test' ) ? $this->test_account_id : $this->live_account_id;
	}

	/**
	 * Update the API keys and mode in the service.
	 * This can be called whenever the database is updated.
	 */
	public function update_keys(): void {
		$this->test_api_key    = get_option( 'monei_test_apikey', '' );
		$this->live_api_key    = get_option( 'monei_live_apikey', '' );
		$this->test_account_id = get_option( 'monei_test_accountid', '' );
		$this->live_account_id = get_option( 'monei_live_accountid', '' );
		$this->api_key_mode    = get_option( 'monei_apikey_mode', 'test' );
	}

	public function copyKeysToCentralSettings() {
		add_filter(
			'option_woocommerce_monei_settings',
			function ( $default_params ) {
                $newTestApiKey = get_option( 'monei_test_apikey', '' );
                $newLiveApiKey = get_option( 'monei_live_apikey', '' );
                $newTestAccountId = get_option( 'monei_test_accountid', '' );
                $newLiveAccountId = get_option( 'monei_live_accountid', '' );
                $currentMode = get_option( 'monei_apikey_mode', '' );

                // Get legacy keys
                $legacyApiKey = get_option( 'monei_apikey', '' );
                $legacyAccountId = get_option( 'monei_accountid', '' );
                $settingsApiKey = $default_params['apikey'] ?? '';
                $settingsAccountId = $default_params['accountid'] ?? '';

                // priority: legacy standalone > settings
                $sourceApiKey = !empty($legacyApiKey) ? $legacyApiKey : $settingsApiKey;
                $sourceAccountId = !empty($legacyAccountId) ? $legacyAccountId : $settingsAccountId;

                $needsMigration = false;
                $testKeysComplete = !empty($newTestApiKey) && !empty($newTestAccountId);
                $liveKeysComplete = !empty($newLiveApiKey) && !empty($newLiveAccountId);

                // Scenario 1: Both sets of new keys are complete
                if ($testKeysComplete && $liveKeysComplete) {
                    if (empty($currentMode)) {
                        update_option('monei_apikey_mode', 'test'); // Default to test if both exist
                    }
                    $this->cleanup_legacy_keys($default_params);
                    return $default_params;
                }

                // Scenario 2 & 3: Partial new keys exist - try to complete them
                if (!empty($newTestApiKey) && empty($newTestAccountId)) {
                    if (!empty($sourceAccountId)) {
                        update_option('monei_test_accountid', $sourceAccountId);
                        $needsMigration = true;
                    }
                }

                if (!empty($newLiveApiKey) && empty($newLiveAccountId)) {
                    if (!empty($sourceAccountId)) {
                        update_option('monei_live_accountid', $sourceAccountId);
                        $needsMigration = true;
                    }
                }

                // Set mode based on existing new keys if mode is not set
                if (empty($currentMode)) {
                    if (!empty($newTestApiKey)) {
                        update_option('monei_apikey_mode', 'test');
                    } elseif (!empty($newLiveApiKey)) {
                        update_option('monei_apikey_mode', 'live');
                    }
                }

                // Scenario 4: No new keys exist, need full migration from legacy
                if (empty($newTestApiKey) && empty($newLiveApiKey) && !empty($sourceApiKey)) {
                    if (strpos($sourceApiKey, 'pk_test_') === 0) {
                        // Migrate to test keys
                        update_option('monei_test_apikey', $sourceApiKey);
                        if (!empty($sourceAccountId)) {
                            update_option('monei_test_accountid', $sourceAccountId);
                        }
                        if (empty($currentMode)) {
                            update_option('monei_apikey_mode', 'test');
                        }
                        $needsMigration = true;

                    } elseif (strpos($sourceApiKey, 'pk_live_') === 0) {
                        // Migrate to live keys
                        update_option('monei_live_apikey', $sourceApiKey);
                        if (!empty($sourceAccountId)) {
                            update_option('monei_live_accountid', $sourceAccountId);
                        }
                        if (empty($currentMode)) {
                            update_option('monei_apikey_mode', 'live');
                        }
                        $needsMigration = true;
                    }
                }

                // Clean up legacy keys if we did any migration
                if ($needsMigration) {
                    $this->cleanup_legacy_keys($default_params);
                }

                return $default_params;
			},
			10
		);
	}
    function cleanup_legacy_keys($settings_array) {
        // Remove legacy standalone options
        delete_option('monei_apikey');
        delete_option('monei_accountid');

        // Remove legacy keys from settings array (which will be returned by the filter)
        if (isset($settings_array['apikey'])) {
            unset($settings_array['apikey']);
        }
        if (isset($settings_array['accountid'])) {
            unset($settings_array['accountid']);
        }

        return $settings_array;
    }
}
