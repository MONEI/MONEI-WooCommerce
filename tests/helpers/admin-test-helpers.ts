import { Page, expect } from '@playwright/test';
import { MoneiSettingsPage } from '../pages/admin/monei-settings-page';

/**
 * Admin Test Helpers for WordPress/WooCommerce administration
 */
export class AdminTestHelpers {
    readonly page: Page;

    constructor(page: Page) {
        this.page = page;
    }

    /**
     * Feature: WordPress admin authentication
     * Scenario: Admin logs into WordPress dashboard
     * Given the admin has valid credentials
     * When the admin navigates to wp-admin
     * And enters valid credentials
     * Then the admin should be logged in successfully
     */
    async loginAsAdmin(username?: string, password?: string) {
        const adminUser = username || process.env.WORDPRESS_ADMIN_USER || 'admin';
        const adminPassword = password || process.env.WORDPRESS_ADMIN_PASSWORD || 'password';

        await this.page.goto('/wp-admin');

        if (await this.page.locator('#wpadminbar').isVisible()) {
            console.log('Already logged in as admin');
            return;
        }

        await this.page.fill('#user_login', adminUser);
        await this.page.fill('#user_pass', adminPassword);
        await this.page.click('#wp-submit');

        await this.page.waitForURL('/wp-admin/', { timeout: 10000 });
        await expect(this.page.locator('#wpadminbar')).toBeVisible();
        console.log('✅ Successfully logged in as admin');
    }

    async loginAsCustomer(username: string, password: string) {
        const adminUser = username;
        const adminPassword = password;

        await this.page.goto('/wp-admin');

        if (await this.page.locator('#wpadminbar').isVisible()) {
            console.log('Already logged in as admin');
            return;
        }

        await this.page.fill('#user_login', adminUser);
        await this.page.fill('#user_pass', adminPassword);
        await this.page.click('#wp-submit');

        await this.page.waitForURL('/wp-admin/', { timeout: 10000 });
        await expect(this.page.locator('#wpadminbar')).toBeVisible();
        console.log('✅ Successfully logged in as customer');
    }

    /**
     * Feature: WordPress admin logout
     * Scenario: Admin logs out from WordPress dashboard
     * Given the admin is logged in
     * When the admin clicks on the logout link
     * Then the admin should be logged out successfully
     */
    async logoutAsAdmin() {
        if (await this.page.locator('#wpadminbar').isVisible()) {
            await this.page.hover('#wp-admin-bar-my-account');
            await this.page.click('#wp-admin-bar-logout a');
            await this.page.waitForLoadState('networkidle');

            const isAdminBarVisible = await this.page.locator('#wpadminbar').isVisible().catch(() => false);
            if (!isAdminBarVisible) {
                console.log('✅ Successfully logged out from admin account');
            } else {
                console.warn('⚠️ Logout may not have completed successfully');
            }
        } else {
            console.log('ℹ️ Already logged out');
        }
    }

    /**
     * Feature: WooCommerce settings navigation
     * Scenario: Admin navigates to WooCommerce settings
     * Given the admin is logged in
     * When the admin navigates to WooCommerce settings
     * Then the WooCommerce settings page should load
     */
    async navigateToWooCommerceSettings() {
        await this.page.goto('/wp-admin/admin.php?page=wc-settings');
        await expect(this.page.locator('.wc-settings-navigation')).toBeVisible();
    }

    /**
     * Feature: MONEI settings navigation
     * Scenario: Admin navigates to MONEI settings
     * Given the admin is logged in
     * When the admin navigates to MONEI settings
     * Then the MONEI settings page should load
     */
    async navigateToMoneiSettings() {
        await this.page.goto('/wp-admin/admin.php?page=wc-settings&tab=monei_settings');
        await expect(this.page.locator('.monei-settings-header-logo')).toBeVisible();
    }

    /**
     * Feature: Payment method configuration
     * Scenario: Admin activates a specific payment method
     * Given the admin is on the payment settings page
     * When the admin enables a payment method
     * And saves the configuration
     * Then the payment method should be activated
     */
    async activatePaymentMethod(methodId: string, customSettings?: Record<string, any>) {
        await this.page.goto(`/wp-admin/admin.php?page=wc-settings&tab=checkout&section=${methodId}`);

        // Wait for the settings form to load
        await this.page.waitForSelector(`#woocommerce_${methodId}_enabled`, { timeout: 5000 });

        // Enable the payment method
        const enableCheckbox = this.page.locator(`#woocommerce_${methodId}_enabled`);
        const isEnabled = await enableCheckbox.isChecked();

        if (!isEnabled) {
            await enableCheckbox.check();
        }

        // Apply custom settings if provided
        if (customSettings) {
            for (const [settingKey, value] of Object.entries(customSettings)) {
                const selector = `#woocommerce_${methodId}_${settingKey}`;
                const element = this.page.locator(selector);

                if (await element.isVisible()) {
                    const elementType = await element.getAttribute('type');

                    if (elementType === 'checkbox') {
                        if (value) {
                            await element.check();
                        } else {
                            await element.uncheck();
                        }
                    } else if (await element.evaluate(el => el.tagName.toLowerCase()) === 'select') {
                        await element.selectOption(value);
                    } else {
                        await element.fill(value);
                    }
                }
            }
        }

        // Save the settings
        await this.page.click('button[name="save"]');
        await expect(this.page.locator('.updated.inline')).toBeVisible({ timeout: 10000 });

        console.log(`✅ Payment method ${methodId} activated successfully`);
    }

    /**
     * Feature: Payment method validation
     * Scenario: Admin verifies payment method is properly configured
     * Given a payment method is activated
     * When the admin checks the payment method configuration
     * Then all required settings should be properly configured
     */
    async verifyPaymentMethodConfiguration(methodId: string): Promise<boolean> {
        await this.page.goto(`/wp-admin/admin.php?page=wc-settings&tab=checkout&section=${methodId}`);

        const isEnabled = await this.page.isChecked(`#woocommerce_${methodId}_enabled`);
        const hasTitle = await this.page.inputValue(`#woocommerce_${methodId}_title`);

        console.log(`Payment method ${methodId} - Enabled: ${isEnabled}, Title: ${hasTitle}`);

        return isEnabled && !!hasTitle;
    }

    /**
     * Feature: Plugin management
     * Scenario: Admin activates the MONEI plugin
     * Given the MONEI plugin is installed
     * When the admin activates the plugin
     * Then the plugin should be active and settings should be accessible
     */
    async activateMoneiPlugin() {
        await this.page.goto('/wp-admin/plugins.php');

        const moneiPluginRow = this.page.locator('tr[data-plugin*="monei"]').first();
        const activateLink = moneiPluginRow.locator('a.activate');

        if (await activateLink.isVisible()) {
            await activateLink.click();
            await expect(this.page.locator('.updated')).toBeVisible();
            console.log('✅ MONEI plugin activated successfully');
        } else {
            console.log('ℹ️ MONEI plugin is already active');
        }
    }

    /**
     * Feature: Country configuration testing
     * Scenario: Admin tests payment methods for different countries
     * Given payment methods are configured
     * When the admin simulates different billing countries
     * Then country-specific payment methods should be available
     */
    async getAvailablePaymentMethodsForCountry(countryCode: string): Promise<string[]> {
        await this.page.goto('/checkout/');
        await this.page.selectOption('#billing_country', countryCode);
        await this.page.waitForTimeout(2000); // Wait for AJAX to update payment methods

        const paymentMethods = await this.page.locator('input[name="payment_method"]').all();
        const availableMethods = [];

        for (const method of paymentMethods) {
            if (await method.isVisible()) {
                const value = await method.getAttribute('value');
                if (value) {
                    availableMethods.push(value);
                }
            }
        }

        return availableMethods;
    }

    /**
     * Feature: Debug logging verification
     * Scenario: Admin verifies debug logging is working
     * Given debug logging is enabled
     * When payment events occur
     * Then events should be logged in WooCommerce logs
     */
    async verifyDebugLogging(): Promise<boolean> {
        await this.page.goto('/wp-admin/admin.php?page=wc-status&tab=logs');

        const logFiles = await this.page.locator('select[name="log_file"] option').all();

        for (const option of logFiles) {
            const text = await option.textContent();
            if (text && text.includes('monei')) {
                console.log('✅ MONEI debug logs found');
                return true;
            }
        }

        console.log('ℹ️ No MONEI debug logs found yet');
        return false;
    }

    /**
     * Feature: API key validation
     * Scenario: Admin validates API keys are working
     * Given API keys are configured
     * When the admin saves the settings
     * Then the keys should be validated against MONEI API
     */
    async validateApiKeys(accountId: string, apiKey: string): Promise<boolean> {
        await this.navigateToMoneiSettings();

        await this.page.selectOption(MoneiSettingsPage.apiKeyModeSelect , 'test');
        await this.page.fill(MoneiSettingsPage.testAccountIdInput, accountId);
        await this.page.fill(MoneiSettingsPage.testApiKeyInput, apiKey);

        await this.page.click(MoneiSettingsPage.saveChangesButton);

        const hasError = await this.page.locator('.error').isVisible();
        const hasSuccess = await this.page.locator('.updated.inline').isVisible();

        if (hasError) {
            const errorText = await this.page.locator('.error').textContent();
            console.log(`❌ API key validation failed: ${errorText}`);
            return false;
        }

        if (hasSuccess) {
            console.log('✅ API keys validated successfully');
            return true;
        }

        console.log('⚠️ API key validation status unclear');
        return false;
    }

    /**
     * Feature: Cleanup utilities
     * Scenario: Admin cleans up test data
     * Given tests have been run
     * When cleanup is needed
     * Then test data should be removed
     */
    async cleanupTestData() {
        await this.page.goto('/cart/');
        const removeButtons = await this.page.locator('.remove').all();
        for (const button of removeButtons) {
            await button.click();
            await this.page.waitForTimeout(1000);
        }

        console.log('🧹 Test cleanup completed');
    }
}

/**
 * Environment configuration helper
 */
export class TestEnvironmentHelper {
    /**
     * Validates that all required environment variables are set
     */
    static validateEnvironment(): void {
        const required = [
            'TESTSITE_URL',
            'WORDPRESS_ADMIN_USER',
            'WORDPRESS_ADMIN_PASSWORD',
            'PAYMENT_GATEWAY_ACCOUNTID',
            'PAYMENT_GATEWAY_API_KEY'
        ];

        const missing = required.filter(env => !process.env[env]);

        if (missing.length > 0) {
            throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
        }
    }

    /**
     * Gets test configuration from environment
     */
    static getTestConfig() {
        this.validateEnvironment();

        return {
            siteUrl: process.env.TESTSITE_URL,
            adminUser: process.env.WORDPRESS_ADMIN_USER,
            adminPassword: process.env.WORDPRESS_ADMIN_PASSWORD,
            accountId: process.env.PAYMENT_GATEWAY_ACCOUNTID,
            apiKey: process.env.PAYMENT_GATEWAY_API_KEY,
            wcConsumerKey: process.env.WC_CONSUMER_KEY,
            wcConsumerSecret: process.env.WC_CONSUMER_SECRET
        };
    }
}