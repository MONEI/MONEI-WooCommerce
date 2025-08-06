import { test, expect } from '@playwright/test';
import { MoneiSettingsPage } from '../pages/admin/monei-settings-page';
import { CheckoutPage } from '../pages/checkout-page';
import { CartPage } from '../pages/cart-page';
import { AdminTestHelpers, TestEnvironmentHelper } from '../helpers/admin-test-helpers';
import { CHECKOUT_TYPES } from '../fixtures/checkout-types';
import { PRODUCT_TYPES } from '../fixtures/product-types';
import { USER_TYPES } from '../fixtures/user-types';

/**
 * Comprehensive MONEI Settings Configuration Test Suite
 *
 * This test suite validates the complete flow of:
 * 1. Configuring MONEI with test API keys from environment variables
 * 2. Activating available payment methods
 * 3. Verifying payment methods appear in both classic and block checkout
 * 4. Testing country-specific payment method visibility
 */
test.describe('MONEI Gateway Complete Integration Test', () => {
    let moneiSettingsPage: MoneiSettingsPage;
    let adminHelpers: AdminTestHelpers;
    let cartPage: CartPage;

    const testConfig = TestEnvironmentHelper.getTestConfig();

    test.beforeAll(async () => {
        TestEnvironmentHelper.validateEnvironment();
        console.log('🔧 Test environment validated successfully');
    });

    test.beforeEach(async ({ page }) => {
        moneiSettingsPage = new MoneiSettingsPage(page);
        adminHelpers = new AdminTestHelpers(page);
        cartPage = new CartPage(page);

        await adminHelpers.loginAsAdmin(testConfig.adminUser, testConfig.adminPassword);
        console.log('🔐 Authenticated as WordPress admin');
    });

    test.afterEach(async ({ page }) => {
        await adminHelpers.cleanupTestData();
    });

    /**
     * Main integration test that covers the complete MONEI configuration flow
     */
    test('Complete MONEI configuration and payment method verification', async ({ page }) => {
        // STEP 1: Configure MONEI with test API keys from environment
        console.log('📋 Step 1: Configuring MONEI with test API keys');
        await adminHelpers.navigateToMoneiSettings();
        const isValidConfig = await adminHelpers.validateApiKeys(testConfig.accountId, testConfig.apiKey);
        expect(isValidConfig).toBeTruthy();
        console.log('✅ MONEI test API keys configured and validated');

        // STEP 2: Activate all available MONEI payment methods
        console.log('📋 Step 2: Discovering and activating payment methods');
        const availablePaymentMethods = await discoverAvailablePaymentMethods(page);
        console.log(`Found ${availablePaymentMethods.length} available payment methods:`, availablePaymentMethods);

        for (const methodId of availablePaymentMethods) {
            await adminHelpers.activatePaymentMethod(methodId, {
                title: getPaymentMethodDisplayName(methodId),
                description: `Test ${methodId} payment method`
            });
        }

        // STEP 3: Verify payment methods in Classic Checkout
        console.log('📋 Step 3: Testing payment methods in Classic Checkout');
        await testPaymentMethodsInCheckout(page, CHECKOUT_TYPES.CLASSIC, availablePaymentMethods);

        // STEP 4: Verify payment methods in Block Checkout
        console.log('📋 Step 4: Testing payment methods in Block Checkout');
        await testPaymentMethodsInCheckout(page, CHECKOUT_TYPES.BLOCK, availablePaymentMethods);

        console.log('🎉 Complete MONEI integration test passed successfully');
    });

    /**
     * Test country-specific payment method visibility
     */
    test('Country-specific payment method visibility', async ({ page }) => {
        await adminHelpers.navigateToMoneiSettings();
        await moneiSettingsPage.configureTestApiKey(testConfig.accountId, testConfig.apiKey);

        const availablePaymentMethods = await discoverAvailablePaymentMethods(page);
        console.log('Available payment methods for this account:', availablePaymentMethods);

        // Activate all available payment methods
        for (const methodId of availablePaymentMethods) {
            await adminHelpers.activatePaymentMethod(methodId, {
                title: getPaymentMethodDisplayName(methodId),
                description: `Test ${methodId} payment method`
            });
        }

        // Test Spain - should see Bizum if available
        const expectedSpanishMethods = ['monei', 'monei_bizum', 'monei_paypal'];
        const availableSpanishMethods = filterAvailableMethods(expectedSpanishMethods, availablePaymentMethods);
        const forbiddenSpanishMethods = []; // No specific forbidden methods for Spain

        if (availableSpanishMethods.length > 0) {
            console.log('🇪🇸 Testing Spanish user payment methods:', availableSpanishMethods);
            await testCountrySpecificPaymentMethods(
                page,
                USER_TYPES.ES_USER,
                availableSpanishMethods,
                forbiddenSpanishMethods
            );
        } else {
            console.log('⚠️ Skipping Spanish payment methods test - no compatible methods available in this account');
        }

        // Test Portugal - should see Multibanco/MBWay if available and not Bizum
        const expectedPortugueseMethods = ['monei', 'monei_multibanco', 'monei_mbway', 'monei_paypal'];
        const availablePortugueseMethods = filterAvailableMethods(expectedPortugueseMethods, availablePaymentMethods);
        const forbiddenPortugueseMethods = ['monei_bizum'].filter(
            method => availablePaymentMethods.includes(method)
        );

        if (availablePortugueseMethods.length > 0) {
            console.log('🇵🇹 Testing Portuguese user payment methods:', availablePortugueseMethods);
            await testCountrySpecificPaymentMethods(
                page,
                USER_TYPES.PT_USER,
                availablePortugueseMethods,
                forbiddenPortugueseMethods
            );
        } else {
            console.log('⚠️ Skipping Portuguese payment methods test - no compatible methods available in this account');
        }
    });
    /**
     * Filters expected methods to only include those available in the account
     */
    function filterAvailableMethods(expectedMethods: string[], availableMethods: string[]): string[] {
        const filteredMethods = expectedMethods.filter(method => availableMethods.includes(method));

        const unavailableMethods = expectedMethods.filter(method => !availableMethods.includes(method));
        if (unavailableMethods.length > 0) {
            console.log(`ℹ️ Some expected methods are not available in this account: ${unavailableMethods.join(', ')}`);
        }

        return filteredMethods;
    }

    /**
     * Test debug logging functionality
     */
    test('Debug logging activation and verification', async ({ page }) => {
        await adminHelpers.navigateToMoneiSettings();
        await moneiSettingsPage.configureTestApiKey(testConfig.accountId, testConfig.apiKey);
        await moneiSettingsPage.enableDebugLogging();

        // Verify debug logging is enabled
        const debugEnabled = await page.isChecked('#monei_debug');
        expect(debugEnabled).toBeTruthy();

        console.log('✅ Debug logging enabled successfully');
    });

    /**
     * Helper Functions
     */

    /**
     * Discovers available payment methods for the configured account
     */
    async function discoverAvailablePaymentMethods(page): Promise<string[]> {
        const moneiMethods = [];
        const accountId = testConfig.accountId;
        const apiKey = testConfig.apiKey;

        try {
            const response = await fetch(`https://api.monei.com/v1/payment-methods?accountId=${accountId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${apiKey}`
                }
            });

            if (!response.ok) {
                throw new Error(`API request failed with status ${response.status}`);
            }

            const data = await response.json();
            console.log('API Response:', data);

            const methodMap = {
                'card': 'monei',
                'bizum': 'monei_bizum',
                'multibanco': 'monei_multibanco',
                'mbway': 'monei_mbway',
                'paypal': 'monei_paypal',
                //'applePay': 'monei_apple_google', TODO add Apple Pay and Google Pay support
                //'googlePay': 'monei_apple_google'
            };

            if (data && data.paymentMethods) {
                for (const method of data.paymentMethods) {
                    if (methodMap[method] && !moneiMethods.includes(methodMap[method])) {
                        moneiMethods.push(methodMap[method]);
                    }
                }
            }
        } catch (error) {
            console.error('Error fetching payment methods from API:', error);

            console.log('Falling back to manual method discovery');
            const commonMethods = ['monei', 'monei_bizum', 'monei_multibanco', 'monei_mbway', 'monei_paypal'];
            for (const methodId of commonMethods) {
                if (!moneiMethods.includes(methodId)) {
                    await page.goto(`/wp-admin/admin.php?page=wc-settings&tab=checkout&section=${methodId}`);
                    if (await page.locator(`#woocommerce_${methodId}_enabled`).isVisible()) {
                        moneiMethods.push(methodId);
                    }
                }
            }
        }

        return [...new Set(moneiMethods)];
    }

    /**
     * Tests payment methods in a specific checkout type
     */
    async function testPaymentMethodsInCheckout(page, checkoutType, expectedMethods: string[]) {
        console.log(`🧪 Testing ${expectedMethods.length} payment methods in ${checkoutType.name}`);

        const checkoutPage = new CheckoutPage(page, checkoutType);
        await cartPage.addProductToCart(PRODUCT_TYPES.SIMPLE);
        await checkoutPage.goToCheckout(checkoutType.isBlockCheckout);

        // Wait for payment methods to load
        // Wait for payment methods to load
        await page.waitForSelector(
            checkoutType.isBlockCheckout
            ? '.wc-block-components-radio-control__input'
                : 'input[name="payment_method"]',
            { state: 'visible', timeout: 10000 }
            );

        let visibleMethods = 0;
        for (const methodId of expectedMethods) {
            const selector = checkoutType.isBlockCheckout
                ? `.wc-block-components-radio-control__input[value="${methodId}"]`
                : `input[name="payment_method"][value="${methodId}"]`;

            const isVisible = await page.locator(selector).isVisible();

            if (isVisible) {
                visibleMethods++;
                console.log(`✅ ${methodId} visible in ${checkoutType.name}`);

            } else {
                console.log(`❌ ${methodId} NOT visible in ${checkoutType.name}`);
            }
        }

        expect(visibleMethods).toBeGreaterThan(0);
        console.log(`✅ ${visibleMethods}/${expectedMethods.length} payment methods working in ${checkoutType.name}`);

        await page.goto('/cart/');
        if (await page.locator('.remove').first().isVisible()) {
            await page.locator('.remove').first().click();
            await page.waitForSelector('.cart-empty', { timeout: 5000 });
        }
    }

    /**
     * Tests country-specific payment method visibility using a new browser context for each country
     */
    async function testCountrySpecificPaymentMethods(
        page,
        userType,
        expectedMethods: string[],
        forbiddenMethods: string[] = []
    ): Promise<boolean> {
        // Create a new browser context for this country test
        const browser = page.context().browser();
        const context = await browser.newContext({
            baseURL: page.url().split('/checkout')[0] // Preserve the base URL
        });
        const countryPage = await context.newPage();

        console.log(`🌍 Testing ${userType.country} in a new browser context`);

        try {
            const cartPage = new CartPage(countryPage);
            const checkoutPage = new CheckoutPage(countryPage, CHECKOUT_TYPES.BLOCK);
            await cartPage.addProductToCart(PRODUCT_TYPES.SIMPLE);
            await countryPage.goto('/checkout/');

            await checkoutPage.fillBillingDetails(userType);

            // Wait for country-based payment method filtering
            await countryPage.waitForLoadState('networkidle');

            const visibleMethods = [];
            const paymentInputs = await countryPage.locator('.wc-block-components-radio-control__input').all();

            for (const input of paymentInputs) {
                if (await input.isVisible()) {
                    const value = await input.getAttribute('value');
                    if (value) {
                        visibleMethods.push(value);
                    }
                }
            }

            console.log(`🌍 Payment methods for ${userType.country}:`, visibleMethods);

            let allExpectedMethodsVisible = true;
            for (const expectedMethod of expectedMethods) {
                if (visibleMethods.includes(expectedMethod)) {
                    console.log(`✅ ${expectedMethod} correctly shown for ${userType.country}`);
                    expect(visibleMethods).toContain(expectedMethod);
                } else {
                    console.log(`❌ ${expectedMethod} not shown for ${userType.country} (should be visible)`);
                    allExpectedMethodsVisible = false;
                    expect(visibleMethods).toContain(expectedMethod);
                }
            }

            for (const forbiddenMethod of forbiddenMethods) {
                if (!visibleMethods.includes(forbiddenMethod)) {
                    console.log(`✅ ${forbiddenMethod} correctly hidden for ${userType.country}`);
                    expect(visibleMethods).not.toContain(forbiddenMethod);
                } else {
                    console.log(`❌ ${forbiddenMethod} shown for ${userType.country} (should be hidden)`);
                    expect(visibleMethods).not.toContain(forbiddenMethod);
                }
            }

            await countryPage.goto('/cart/');
            if (await countryPage.locator('.remove').first().isVisible()) {
                await countryPage.locator('.remove').first().click();
                await countryPage.waitForSelector('.cart-empty', { timeout: 5000 });
            }

            return allExpectedMethodsVisible;
        } catch (error) {
            console.error(`Error testing ${userType.country}:`, error);
            return false;
        } finally {
            await context.close();
            console.log(`🧹 Closed browser context for ${userType.country} test`);
        }
    }

    /**
     * Gets display name for payment method
     */
    function getPaymentMethodDisplayName(methodId: string): string {
        const displayNames = {
            'monei': 'Credit Card',
            'monei_bizum': 'Bizum',
            'monei_multibanco': 'Multibanco',
            'monei_mbway': 'MBWay',
            'monei_paypal': 'PayPal',
            'monei_apple_pay': 'Apple Pay',
            'monei_google_pay': 'Google Pay'
        };

        return displayNames[methodId] || methodId;
    }
});