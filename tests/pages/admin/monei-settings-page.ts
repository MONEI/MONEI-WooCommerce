import { Page, expect } from '@playwright/test';

export class MoneiSettingsPage {
    readonly page: Page;
    static readonly apiKeyModeSelect = '#monei_apikey_mode';
    static readonly testAccountIdInput = '#monei_test_accountid';
    static readonly liveAccountIdInput = '#monei_live_accountid';
    static readonly testApiKeyInput = '#monei_test_apikey';
    static readonly liveApiKeyInput = '#monei_live_apikey';
    static readonly debugCheckbox = '#monei_debug';
    static readonly saveChangesButton = 'button[name="save"]';
    static readonly successMessage = '#message.updated.inline';
    static readonly errorMessage = '#message.error';
    // Locators
    readonly apiKeyModeSelect;
    readonly testAccountIdInput;
    readonly liveAccountIdInput;
    readonly testApiKeyInput;
    readonly liveApiKeyInput;
    readonly debugCheckbox;
    readonly saveChangesButton;
    readonly successMessage;
    readonly errorMessage;

    constructor(page: Page) {
        this.page = page;
        this.apiKeyModeSelect = page.locator(MoneiSettingsPage.apiKeyModeSelect);
        this.testAccountIdInput = page.locator(MoneiSettingsPage.testAccountIdInput);
        this.liveAccountIdInput = page.locator(MoneiSettingsPage.liveAccountIdInput);
        this.testApiKeyInput = page.locator(MoneiSettingsPage.testApiKeyInput);
        this.liveApiKeyInput = page.locator(MoneiSettingsPage.liveApiKeyInput);
        this.debugCheckbox = page.locator(MoneiSettingsPage.debugCheckbox);
        this.saveChangesButton = page.locator(MoneiSettingsPage.saveChangesButton);
        this.successMessage = page.locator(MoneiSettingsPage.successMessage);
        this.errorMessage = page.locator(MoneiSettingsPage.errorMessage);

    }

    /**
     * Feature: API key configuration
     * Scenario: Admin can configure test API keys
     * Given the admin is on the MONEI settings page
     * When the admin selects "Test API Key" mode
     * And enters a valid test Account ID
     * And enters a valid test API key
     * And saves the changes
     * Then the settings should be saved successfully
     */
    async configureTestApiKey(accountId: string, apiKey: string) {
        await this.apiKeyModeSelect.selectOption('test');
        await this.testAccountIdInput.fill(accountId);
        await this.testApiKeyInput.fill(apiKey);
        await this.saveChangesButton.click();
        await expect(this.errorMessage).not.toBeVisible({ timeout: 2000 });

    }

    /**
     * Feature: API key configuration
     * Scenario: Admin can configure live API keys
     * Given the admin is on the MONEI settings page
     * When the admin selects "Live API Key" mode
     * And enters a valid live Account ID
     * And enters a valid live API key
     * And saves the changes
     * Then the settings should be saved successfully
     */
    async configureLiveApiKey(accountId: string, apiKey: string) {
        await this.apiKeyModeSelect.selectOption('live');
        await this.liveAccountIdInput.fill(accountId);
        await this.liveApiKeyInput.fill(apiKey);
        await this.saveChangesButton.click();
        await expect(this.successMessage).toBeVisible();
    }


    async enableDebugLogging() {
        await this.debugCheckbox.check();
        //if is not enabled, it's because was already enabled
        if (await this.saveChangesButton.isEnabled()){
            await this.saveChangesButton.click();
        }
    }
}