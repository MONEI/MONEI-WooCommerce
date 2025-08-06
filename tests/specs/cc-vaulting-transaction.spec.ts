import { test, expect } from '@playwright/test';
import { MoneiSettingsPage } from '../pages/admin/monei-settings-page';
import { CheckoutPage } from '../pages/checkout-page';
import { CartPage } from '../pages/cart-page';
import { AdminTestHelpers, TestEnvironmentHelper } from '../helpers/admin-test-helpers';
import { CreditCardProcessor } from '../helpers/payment-processors/credit-card-processor';
import { OrderVerification } from '../verification/order-verification';
import { WordPressApiClient } from '../setup/wordpress-api-client';
import { CHECKOUT_TYPES } from '../fixtures/checkout-types';
import { PRODUCT_TYPES } from '../fixtures/product-types';
import { USER_TYPES } from '../fixtures/user-types';
import { TEST_USERS } from '../setup/user-setup';

/**
 * MONEI Vaulting/Tokenization Complete Integration Test Suite
 *
 * This test suite validates:
 * 1. Enabling tokenization setting programmatically via existing WooCommerce API client
 * 2. Saving credit card from user account payments page
 * 3. Removing saved payment methods
 * 4. Saving payment method during checkout flow using existing CreditCardProcessor
 * 5. Using saved payment method for subsequent transactions
 */
test.describe('MONEI Vaulting/Tokenization Integration Test', () => {
    let moneiSettingsPage: MoneiSettingsPage;
    let adminHelpers: AdminTestHelpers;
    let cartPage: CartPage;
    let checkoutPage: CheckoutPage;
    let apiClient: WordPressApiClient;
    let creditCardProcessor: CreditCardProcessor;
    let orderVerification: OrderVerification;
    const testConfig = TestEnvironmentHelper.getTestConfig();
    let customerEmail: string;
    let customerPassword: string;


    test.beforeAll(async () => {
        TestEnvironmentHelper.validateEnvironment();
        console.log('🔧 Test environment validated successfully');
        customerEmail = TEST_USERS.ES_CUSTOMER.email;
        customerPassword = TEST_USERS.ES_CUSTOMER.password;
    });

    test.beforeEach(async ({ page }) => {
        moneiSettingsPage = new MoneiSettingsPage(page);
        adminHelpers = new AdminTestHelpers(page);
        cartPage = new CartPage(page);
        checkoutPage = new CheckoutPage(page, CHECKOUT_TYPES.CLASSIC);
        apiClient = new WordPressApiClient();
        creditCardProcessor = new CreditCardProcessor(page, false); // Direct mode for tokenization
        orderVerification = new OrderVerification(page);

        await adminHelpers.loginAsAdmin(testConfig.adminUser, testConfig.adminPassword);
        console.log('🔐 Authenticated as WordPress admin');
    });

    test.afterEach(async ({ page }) => {
        await adminHelpers.cleanupTestData();
    });

    /**
     * Test 1: Setup MONEI with tokenization enabled using existing API infrastructure
     */
    test('Setup MONEI configuration and enable tokenization', async ({ page }) => {
        console.log('📋 Step 1: Configuring MONEI with test API keys');

        // Configure MONEI basic settings using existing method
        await adminHelpers.navigateToMoneiSettings();
        const isValidConfig = await adminHelpers.validateApiKeys(testConfig.accountId, testConfig.apiKey);
        expect(isValidConfig).toBeTruthy();
        console.log('✅ MONEI test API keys configured and validated');

        // Enable MONEI Credit Card gateway with tokenization using existing API client
        console.log('📋 Step 2: Enabling MONEI Credit Card gateway with tokenization via API');

        try {
            // Enable the main credit card gateway with tokenization
            await apiClient.updateGatewaySettings('monei', {
                enabled: true,
                settings: {
                    tokenization: 'yes',
                }
            });

            console.log('✅ MONEI Credit Card gateway enabled with tokenization via API');

            // Verify tokenization is enabled in admin panel
            await page.goto('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=monei');
            await page.waitForLoadState('networkidle');

            const tokenizationEnabled = await page.isChecked('#woocommerce_monei_tokenization');
            expect(tokenizationEnabled).toBe(true);
            console.log('✅ Tokenization setting verified in admin panel');

        } catch (error) {
            console.error('❌ Failed to enable tokenization via API:', error);
            throw error;
        }
    });

    /**
     * Test 2: Create test customer and save payment method from account page
     */
    test('Save credit card method from user account payments page', async ({ page }) => {
        console.log('📋 Step 3: Login test customer and saving payment method');

        // Login as customer and navigate to payment methods page
        await adminHelpers.loginAsCustomer(customerEmail, customerPassword);
        await page.goto('/my-account/payment-methods/');
        await page.waitForLoadState('networkidle');

        // Check if "Add payment method" button exists
        const addMethodButton = page.getByRole('link', { name: 'Add payment method' });
        await expect(addMethodButton).toBeVisible();
        await addMethodButton.click();
        await page.waitForLoadState('networkidle');

        // Use existing CreditCardProcessor with save option
        await fillCreditCardFormWithSave(page);

        // Submit the form
        await page.click('#place_order, .wc-block-components-checkout-place-order-button');
        await page.waitForLoadState('networkidle');
        await creditCardProcessor.fillCardDetails()
        await page.getByTestId('pay-button').click();

        // Verify payment method was saved
        await page.waitForLoadState('networkidle');
        await page.goto('/my-account/payment-methods/');
        await page.waitForLoadState('networkidle');

        const savedMethods = page.locator('.woocommerce-PaymentMethod[data-title="Method"]');
        const methodText = await savedMethods.textContent();
        expect(methodText).toContain('Visa ending in 4422');
        console.log('✅ Payment method saved successfully from account page');
    });

    /**
     * Test 3: Save payment method during checkout
     */
    test('Save payment method during checkout and complete transaction', async ({ page }) => {
        //TODO remove any saved payment method
        console.log('📋 Step 5: Testing save payment method during checkout');

        // Add product to cart using existing CartPage
        await cartPage.addProductToCart(PRODUCT_TYPES.SIMPLE);
        console.log('✅ Product added to cart');

        // Login as customer and proceed to checkout
        await adminHelpers.loginAsCustomer(customerEmail, customerPassword);
        await checkoutPage.goToCheckout(CHECKOUT_TYPES.CLASSIC); // Classic checkout
        await page.waitForLoadState('networkidle');

        // Fill billing details using existing method
        await checkoutPage.fillBillingDetails(USER_TYPES.ES_USER);

        // Select MONEI payment method
        const paymentMethodSelector = 'input[value="monei"]';
        await page.click(paymentMethodSelector);
        await page.waitForTimeout(1000); // Wait for payment form to load

        // Use existing CreditCardProcessor with save option
        await processCreditCardPaymentWithSave(page);

        // Complete the transaction
        await orderVerification.verifySuccessfulOrder();
        console.log('✅ Transaction completed successfully');

        // Verify payment method was saved
        await page.goto('/my-account/payment-methods/');
        await page.waitForLoadState('networkidle');

        const savedMethods = page.locator('.woocommerce-PaymentMethod[data-title="Method"]');
        const methodText = await savedMethods.textContent();
        expect(methodText).toContain('Visa ending in 4422');
        console.log('✅ Payment method saved during checkout');
    });

    /**
     * Test 5: Use saved payment method for subsequent transaction
     */
    test('Use saved payment method for subsequent transaction', async ({ page }) => {
        console.log('📋 Step 6: Testing transaction with saved payment method');

        // Add another product to cart
        await cartPage.addProductToCart(PRODUCT_TYPES.SIMPLE);
        console.log('✅ Product added to cart for second transaction');

        // Login as customer and proceed to checkout
        await adminHelpers.loginAsCustomer(customerEmail, customerPassword);
        await checkoutPage.goToCheckout(CHECKOUT_TYPES.CLASSIC); // Classic checkout
        await page.waitForLoadState('networkidle');

        // Fill billing details
        await checkoutPage.fillBillingDetails(USER_TYPES.ES_USER);

        // Select MONEI payment method
        await page.click('input[value="monei"]');
        await page.waitForTimeout(1000);

        // Check if saved payment methods are available
        const savedMethodsDropdown = page.locator('select[name="wc-monei-payment-token"]');
        if (await savedMethodsDropdown.isVisible()) {
            // Select the saved payment method (skip "Use a new payment method" option)
            const options = await savedMethodsDropdown.locator('option').all();
            if (options.length > 1) {
                await savedMethodsDropdown.selectOption({ index: 1 }); // First saved method
                console.log('✅ Saved payment method selected');
            } else {
                throw new Error('No saved payment methods found in dropdown');
            }
        } else {
            // For block checkout or different UI, look for radio buttons
            const savedMethodRadio = page.locator('input[name="wc-monei-payment-token"]:not([value=""])').first();
            if (await savedMethodRadio.isVisible()) {
                await savedMethodRadio.check();
                console.log('✅ Saved payment method selected (radio button)');
            } else {
                throw new Error('No saved payment methods found');
            }
        }

        // Complete the transaction
        await page.click('#place_order');
        await orderVerification.verifySuccessfulOrder();
        console.log('✅ Second transaction completed successfully using saved payment method');
    });

    async function fillCreditCardFormWithSave(page: any) {
        await creditCardProcessor.fillCardDetails()
        // Check save payment method checkbox
        const saveCheckbox = page.locator('#wc-monei-new-payment-method, input[name="wc-monei-new-payment-method"]');
        if (await saveCheckbox.isVisible()) {
            await saveCheckbox.check();
            console.log('✅ Save payment method checkbox checked');
        }
    }

    async function processCreditCardPaymentWithSave(page: any) {
        // Check save payment method option
        const saveCheckbox = page.locator('#wc-monei-new-payment-method, input[name="wc-monei-new-payment-method"]');
        if (await saveCheckbox.isVisible()) {
            await saveCheckbox.check();
        }

        await creditCardProcessor.clickWooSubmitButton();
        await page.waitForLoadState('networkidle');
        await creditCardProcessor.fillCardDetails();
        await page.getByTestId('pay-button').click();
    }
});