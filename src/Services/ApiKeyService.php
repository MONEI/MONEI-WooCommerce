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
        // Get the current state once
        $keyState = $this->getCurrentKeyState();

        // First, check if we need any migration at all
        if ($this->needsMigration($keyState)) {
            // Try standalone migration first (has priority)
            $standaloneSuccess = $this->migrateStandaloneKeys($keyState);

            // Only bother with settings if standalone migration didn't complete everything
            if (!$standaloneSuccess) {
                add_filter('option_woocommerce_monei_settings', array($this, 'processCentralSettings'), 10, 1);
            }
        }
    }

    /**
     * Get current state of all keys
     *
     * @return array Current key state
     */
    private function getCurrentKeyState() {
        return array(
            'test_api_key' => get_option('monei_test_apikey', ''),
            'live_api_key' => get_option('monei_live_apikey', ''),
            'test_account_id' => get_option('monei_test_accountid', ''),
            'live_account_id' => get_option('monei_live_accountid', ''),
            'current_mode' => get_option('monei_apikey_mode', ''),
        );
    }

    /**
     * Check if any migration is needed
     *
     * @param array $keyState Current key state
     * @return bool True if migration is needed
     */
    private function needsMigration($keyState) {
        // Get legacy keys
        $legacyApiKey = get_option('monei_apikey', '');
        $legacyAccountId = get_option('monei_accountid', '');
        $existingSettings = get_option('woocommerce_monei_settings', array());
        $settingsApiKey = $existingSettings['apikey'] ?? '';
        $settingsAccountId = $existingSettings['accountid'] ?? '';

        // Check if both new key sets are complete
        $testKeysComplete = !empty($keyState['test_api_key']) && !empty($keyState['test_account_id']);
        $liveKeysComplete = !empty($keyState['live_api_key']) && !empty($keyState['live_account_id']);

        // If both are complete, no migration needed
        if ($testKeysComplete && $liveKeysComplete) {
            return false;
        }

        // If we have any legacy keys or incomplete new keys, migration is needed
        return !empty($legacyApiKey) || !empty($legacyAccountId) ||
            !empty($settingsApiKey) || !empty($settingsAccountId) ||
            (!empty($keyState['test_api_key']) && empty($keyState['test_account_id'])) ||
            (!empty($keyState['live_api_key']) && empty($keyState['live_account_id']));
    }

    /**
     * Migrate standalone legacy keys (works regardless of settings existence)
     *
     * @param array $keyState Current key state
     * @return bool True if migration was successful and complete, false if settings migration is still needed
     */
    private function migrateStandaloneKeys($keyState) {
        // Get legacy standalone keys
        $legacyApiKey = get_option('monei_apikey', '');
        $legacyAccountId = get_option('monei_accountid', '');

        $needsCleanup = false;
        $migratedFromStandalone = false;

        // Complete partial new keys using legacy standalone keys
        if (!empty($keyState['test_api_key']) && empty($keyState['test_account_id']) && !empty($legacyAccountId)) {
            update_option('monei_test_accountid', $legacyAccountId);
            $needsCleanup = true;
            $migratedFromStandalone = true;
        }

        if (!empty($keyState['live_api_key']) && empty($keyState['live_account_id']) && !empty($legacyAccountId)) {
            update_option('monei_live_accountid', $legacyAccountId);
            $needsCleanup = true;
            $migratedFromStandalone = true;
        }

        // Set mode based on existing new keys if mode is not set
        if (empty($keyState['current_mode'])) {
            if (!empty($keyState['test_api_key'])) {
                update_option('monei_apikey_mode', 'test');
            } elseif (!empty($keyState['live_api_key'])) {
                update_option('monei_apikey_mode', 'live');
            }
        }

        // Full migration from legacy standalone keys if no new keys exist
        if (empty($keyState['test_api_key']) && empty($keyState['live_api_key']) && !empty($legacyApiKey)) {
            if ($this->migrateSingleKeySet($legacyApiKey, $legacyAccountId, $keyState['current_mode'])) {
                $needsCleanup = true;
                $migratedFromStandalone = true;
            }
        }

        // Clean up legacy standalone keys if we migrated
        if ($needsCleanup) {
            delete_option('monei_apikey');
            delete_option('monei_accountid');
        }

        // Return true if we migrated anything from standalone (has priority over settings)
        // or if we already had complete key sets
        $initialTestKeysComplete = !empty($keyState['test_api_key']) && !empty($keyState['test_account_id']);
        $initialLiveKeysComplete = !empty($keyState['live_api_key']) && !empty($keyState['live_account_id']);

        return $migratedFromStandalone || ($initialTestKeysComplete || $initialLiveKeysComplete);
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
            if ($this->migrateSingleKeySet($settingsApiKey, $settingsAccountId, $currentMode)) {
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
     * Migrate a single key set based on key prefix
     *
     * @param string $apiKey The API key to migrate
     * @param string $accountId The account ID to migrate
     * @param string $currentMode Current mode setting
     * @return bool True if migration occurred
     */
    private function migrateSingleKeySet($apiKey, $accountId, $currentMode) {
        if (strpos($apiKey, 'pk_test_') === 0) {
            update_option('monei_test_apikey', $apiKey);
            if (!empty($accountId)) {
                update_option('monei_test_accountid', $accountId);
            }
            if (empty($currentMode)) {
                update_option('monei_apikey_mode', 'test');
            }
            return true;
        } elseif (strpos($apiKey, 'pk_live_') === 0) {
            update_option('monei_live_apikey', $apiKey);
            if (!empty($accountId)) {
                update_option('monei_live_accountid', $accountId);
            }
            if (empty($currentMode)) {
                update_option('monei_apikey_mode', 'live');
            }
            return true;
        }
        return false;
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
