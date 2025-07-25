import { test } from '@playwright/test';
import { WordPressApiClient } from '../setup/wordpress-api-client';
import { PayForOrderPage } from '../pages/pay-for-order';
import { getPaymentProcessor } from '../helpers/payment-processors';
import { OrderCreationHelper } from '../helpers/order-creation-helper';
import { OrderVerification } from '../verification/order-verification';
import { PAYMENT_METHODS } from '../fixtures/payment-methods';
import { CHECKOUT_TYPES } from '../fixtures/checkout-types';
import { USER_TYPES } from '../fixtures/user-types';
import { PRODUCT_TYPES } from '../fixtures/product-types';

interface OrderData {
    id: number;
    order_key: string;
    total: string;
    status: string;
}

const orderHelper = new OrderCreationHelper();

/**
 * Build pay-order URL
 */
function buildPayOrderUrl(orderId: number, orderKey: string): string {
    return `/checkout/order-pay/${orderId}/?pay_for_order=true&key=${orderKey}`;
}

test.describe('Pay Order Page - Gateway Tests', () => {
    let apiClient: WordPressApiClient;
    let createdOrderIds: number[] = [];

    test.beforeAll(async () => {
        apiClient = new WordPressApiClient();
    });

    test.afterAll(async () => {
        // Clean up created orders
        for (const orderId of createdOrderIds) {
            try {
                await apiClient.wooCommerce.delete(`orders/${orderId}`, { force: true });
                console.log(`✅ Cleaned up order: ${orderId}`);
            } catch (error) {
                console.error(`Failed to delete order ${orderId}:`, error.message);
            }
        }
    });

    // Test all payment methods
    const paymentMethodsToTest = Object.values(PAYMENT_METHODS).filter(pm =>
        // Filter out methods not suitable for pay-order page if needed
        pm.id !== 'monei_googlepay' && pm.id !== 'monei_applepay'
    );

    paymentMethodsToTest.forEach(paymentMethod => {
            test(`Pay Order - ${paymentMethod.name}`, async ({ page }) => {
                const order = await orderHelper.createOrder({
                    productType: paymentMethod.id.includes('bizum') ? PRODUCT_TYPES.BIZUM_SUCCESS : PRODUCT_TYPES.SIMPLE,
                    userType: USER_TYPES.ES_USER,
                    lineItems: [{ sku: PRODUCT_TYPES.SIMPLE.sku, quantity: 1 }]
                });

                // Initialize page objects
                const payForOrderPage = new PayForOrderPage(page, CHECKOUT_TYPES.BLOCK);//the url will be in /checkout
                const orderVerification = new OrderVerification(page);
                const paymentProcessor = getPaymentProcessor(paymentMethod.id, page);

                // Navigate to pay-order page
                const payOrderUrl = buildPayOrderUrl(order.id, order.order_key);
                await page.goto(payOrderUrl);

                // Wait for page to load
                await page.waitForLoadState('networkidle');
                await page.waitForSelector('#payment');

                await payForOrderPage.selectPaymentMethod(paymentMethod);

                await paymentProcessor.processPayment(
                    paymentMethod.isHostedPayment,
                    paymentMethod.presetCredentials
                );

                // Verify order result
                if (paymentMethod.presetCredentials === 'success') {
                    await orderVerification.verifySuccessfulOrder();
                    console.log(`✅ Payment successful for ${paymentMethod.name}`);
                } else {
                    await orderVerification.verifyFailedPayment();
                    console.log(`❌ Payment failed as expected for ${paymentMethod.name}`);
                }
            });
        });

    // Test with saved payment methods
    test('Pay Order - Saved Payment Method', async ({ page, context }) => {
        await context.addInitScript(() => {
            localStorage.setItem('wc-blocks_saved_payment_method_enabled', 'true');
        });

        // Create order
        const order = await orderHelper.createOrder({
            productType: PRODUCT_TYPES.SIMPLE,
            userType: USER_TYPES.ES_USER
        });

        const payForOrderPage = new PayForOrderPage(page, CHECKOUT_TYPES.CLASSIC);
        const orderVerification = new OrderVerification(page);

        // Navigate to pay-order page
        const payOrderUrl = buildPayOrderUrl(order.id, order.order_key);
        await page.goto(payOrderUrl);
        await page.waitForLoadState('networkidle');

        // Check if saved payment methods are displayed
        const savedMethodsVisible = await payForOrderPage.hasSavedPaymentMethods();
        if (savedMethodsVisible) {
            await payForOrderPage.selectSavedPaymentMethod(0);
            await payForOrderPage.clickPlaceOrder();
            await orderVerification.verifySuccessfulOrder();
            console.log('✅ Payment with saved method successful');
        } else {
            console.log('⚠️ No saved payment methods available');
        }
    });
});