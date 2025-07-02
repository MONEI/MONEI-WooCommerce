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
        // First, check if we need any migration at all
        if ($this->needsMigration()) {
            // Try standalone migration first (has priority)
            $standaloneSuccess = $this->migrateStandaloneKeys();

            // Only bother with settings if standalone migration didn't complete everything
            if (!$standaloneSuccess) {
                add_filter('option_woocommerce_monei_settings', array($this, 'processCentralSettings'), 10, 1);
            }
        }
    }

    /**
     * Check if any migration is needed
     *
     * @return bool True if migration is needed
     */
    private function needsMigration() {
        $newTestApiKey = get_option('monei_test_apikey', '');
        $newLiveApiKey = get_option('monei_live_apikey', '');
        $newTestAccountId = get_option('monei_test_accountid', '');
        $newLiveAccountId = get_option('monei_live_accountid', '');

        // Get legacy keys
        $legacyApiKey = get_option('monei_apikey', '');
        $legacyAccountId = get_option('monei_accountid', '');
        $existingSettings = get_option('woocommerce_monei_settings', array());
        $settingsApiKey = $existingSettings['apikey'] ?? '';
        $settingsAccountId = $existingSettings['accountid'] ?? '';

        // Check if both new key sets are complete
        $testKeysComplete = !empty($newTestApiKey) && !empty($newTestAccountId);
        $liveKeysComplete = !empty($newLiveApiKey) && !empty($newLiveAccountId);

        // If both are complete, no migration needed
        if ($testKeysComplete && $liveKeysComplete) {
            return false;
        }

        // If we have any legacy keys or incomplete new keys, migration is needed
        return !empty($legacyApiKey) || !empty($legacyAccountId) ||
            !empty($settingsApiKey) || !empty($settingsAccountId) ||
            (!empty($newTestApiKey) && empty($newTestAccountId)) ||
            (!empty($newLiveApiKey) && empty($newLiveAccountId));
    }

    /**
     * Migrate standalone legacy keys (works regardless of settings existence)
     *
     * @return bool True if migration was successful and complete, false if settings migration is still needed
     */
    private function migrateStandaloneKeys() {
        $newTestApiKey = get_option('monei_test_apikey', '');
        $newLiveApiKey = get_option('monei_live_apikey', '');
        $newTestAccountId = get_option('monei_test_accountid', '');
        $newLiveAccountId = get_option('monei_live_accountid', '');
        $currentMode = get_option('monei_apikey_mode', '');

        // Get legacy standalone keys
        $legacyApiKey = get_option('monei_apikey', '');
        $legacyAccountId = get_option('monei_accountid', '');

        $needsCleanup = false;
        $migratedFromStandalone = false;

        // Complete partial new keys using legacy standalone keys
        if (!empty($newTestApiKey) && empty($newTestAccountId) && !empty($legacyAccountId)) {
            update_option('monei_test_accountid', $legacyAccountId);
            $needsCleanup = true;
            $migratedFromStandalone = true;
        }

        if (!empty($newLiveApiKey) && empty($newLiveAccountId) && !empty($legacyAccountId)) {
            update_option('monei_live_accountid', $legacyAccountId);
            $needsCleanup = true;
            $migratedFromStandalone = true;
        }

        // Set mode based on existing new keys if mode is not set
        if (empty($currentMode)) {
            if (!empty($newTestApiKey)) {
                update_option('monei_apikey_mode', 'test');
            } elseif (!empty($newLiveApiKey)) {
                update_option('monei_apikey_mode', 'live');
            }
        }

        // Full migration from legacy standalone keys if no new keys exist
        if (empty($newTestApiKey) && empty($newLiveApiKey) && !empty($legacyApiKey)) {
            if (strpos($legacyApiKey, 'pk_test_') === 0) {
                update_option('monei_test_apikey', $legacyApiKey);
                if (!empty($legacyAccountId)) {
                    update_option('monei_test_accountid', $legacyAccountId);
                }
                if (empty($currentMode)) {
                    update_option('monei_apikey_mode', 'test');
                }
                $needsCleanup = true;
                $migratedFromStandalone = true;

            } elseif (strpos($legacyApiKey, 'pk_live_') === 0) {
                update_option('monei_live_apikey', $legacyApiKey);
                if (!empty($legacyAccountId)) {
                    update_option('monei_live_accountid', $legacyAccountId);
                }
                if (empty($currentMode)) {
                    update_option('monei_apikey_mode', 'live');
                }
                $needsCleanup = true;
                $migratedFromStandalone = true;
            }
        }

        // Clean up legacy standalone keys if we migrated
        if ($needsCleanup) {
            delete_option('monei_apikey');
            delete_option('monei_accountid');
        }

        // Check if migration is now complete (both sets of keys exist OR we successfully migrated what we had)
        $newTestApiKeyAfter = get_option('monei_test_apikey', '');
        $newLiveApiKeyAfter = get_option('monei_live_apikey', '');
        $newTestAccountIdAfter = get_option('monei_test_accountid', '');
        $newLiveAccountIdAfter = get_option('monei_live_accountid', '');

        $testKeysComplete = !empty($newTestApiKeyAfter) && !empty($newTestAccountIdAfter);
        $liveKeysComplete = !empty($newLiveApiKeyAfter) && !empty($newLiveAccountIdAfter);

        // Return true if we have at least one complete set OR if we migrated anything from standalone
        // (meaning settings keys are irrelevant since standalone has priority)
        return ($testKeysComplete || $liveKeysComplete) || $migratedFromStandalone;
    }

    /**
     * Process and migrate API keys from settings (only called via filter)
     *
     * @param array $default_params The settings array from the filter
     * @return array The processed settings array
     */
    public function processCentralSettings($default_params) {
        $newTestApiKey = get_option('monei_test_apikey', '');
        $newLiveApiKey = get_option('monei_live_apikey', '');
        $newTestAccountId = get_option('monei_test_accountid', '');
        $newLiveAccountId = get_option('monei_live_accountid', '');
        $currentMode = get_option('monei_apikey_mode', '');

        // Get keys from settings
        $settingsApiKey = $default_params['apikey'] ?? '';
        $settingsAccountId = $default_params['accountid'] ?? '';

        $needsCleanup = false;
        $testKeysComplete = !empty($newTestApiKey) && !empty($newTestAccountId);
        $liveKeysComplete = !empty($newLiveApiKey) && !empty($newLiveAccountId);

        // If both sets are complete, just clean up settings and return
        if ($testKeysComplete && $liveKeysComplete) {
            if (empty($currentMode)) {
                update_option('monei_apikey_mode', 'test');
            }
            return $this->cleanup_legacy_keys($default_params);
        }

        // Complete partial new keys using settings keys
        if (!empty($newTestApiKey) && empty($newTestAccountId) && !empty($settingsAccountId)) {
            update_option('monei_test_accountid', $settingsAccountId);
            $needsCleanup = true;
        }

        if (!empty($newLiveApiKey) && empty($newLiveAccountId) && !empty($settingsAccountId)) {
            update_option('monei_live_accountid', $settingsAccountId);
            $needsCleanup = true;
        }

        // Set mode based on existing new keys if mode is not set
        if (empty($currentMode)) {
            if (!empty($newTestApiKey)) {
                update_option('monei_apikey_mode', 'test');
            } elseif (!empty($newLiveApiKey)) {
                update_option('monei_apikey_mode', 'live');
            }
        }

        // Full migration from settings keys if no new keys exist
        if (empty($newTestApiKey) && empty($newLiveApiKey) && !empty($settingsApiKey)) {
            if (strpos($settingsApiKey, 'pk_test_') === 0) {
                update_option('monei_test_apikey', $settingsApiKey);
                if (!empty($settingsAccountId)) {
                    update_option('monei_test_accountid', $settingsAccountId);
                }
                if (empty($currentMode)) {
                    update_option('monei_apikey_mode', 'test');
                }
                $needsCleanup = true;

            } elseif (strpos($settingsApiKey, 'pk_live_') === 0) {
                update_option('monei_live_apikey', $settingsApiKey);
                if (!empty($settingsAccountId)) {
                    update_option('monei_live_accountid', $settingsAccountId);
                }
                if (empty($currentMode)) {
                    update_option('monei_apikey_mode', 'live');
                }
                $needsCleanup = true;
            }
        }

        // Clean up legacy keys from settings if we did any migration
        if ($needsCleanup) {
            $default_params = $this->cleanup_legacy_keys($default_params);
        }

        return $default_params;
    }

    /**
     * Clean up legacy keys from settings array
     *
     * @param array $settings_array The settings array
     * @return array The cleaned settings array
     */
    private function cleanup_legacy_keys($settings_array) {
        if (isset($settings_array['apikey'])) {
            unset($settings_array['apikey']);
        }
        if (isset($settings_array['accountid'])) {
            unset($settings_array['accountid']);
        }

        return $settings_array;
    }
}
