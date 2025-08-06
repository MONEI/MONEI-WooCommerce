import { test } from '@playwright/test';
import { TEST_CONFIGURATIONS } from '../fixtures/test-configurations';
import { PAYMENT_METHODS } from '../fixtures/payment-methods';
import { CheckoutPage } from '../pages/checkout-page';
import { getPaymentProcessor } from '../helpers/payment-processors';
import { OrderVerification } from '../verification/order-verification';
import {CartPage} from "../pages/cart-page";
import {WordPressApiClient} from "../setup/wordpress-api-client";

const configName = process.env.TEST_CONFIG || 'ALL_METHODS_TESTS_SIMPLE';
const configurations = TEST_CONFIGURATIONS[configName];

// Each configuration is a complete test case with all the properties needed for the test
const testCombinations = configurations.map(config => ({
    paymentMethod: config.paymentMethod,
    checkoutType: config.checkoutType,
    productType: config.productType,
    userState: config.userState,
    expectSuccess: config.expectSuccess,
    userType: config.userType
}));
test.describe('Payment Gateway Matrix Tests', () => {
    let apiClient: WordPressApiClient;
    apiClient = new WordPressApiClient();
    testCombinations.forEach(({ paymentMethod, checkoutType, productType, userState, expectSuccess, userType }) => {
        test(`${paymentMethod.name} - ${checkoutType.name} - ${productType.name} - ${userState.name}`, async ({ page }) => {
            const checkoutPage = new CheckoutPage(page, checkoutType);
            const cartHelper = new CartPage(page);
            const paymentProcessor = getPaymentProcessor(paymentMethod.id, page);
            if( paymentMethod.id === 'monei') {
                if(paymentMethod.isHostedPayment) {
                    await apiClient.updateGatewaySettings('monei', {
                        enabled: true,
                        settings: {
                            cc_mode: 'yes',
                        }
                    });
                } else {
                    await apiClient.updateGatewaySettings('monei', {
                        enabled: true,
                        settings: {
                            cc_mode: 'no',
                        }
                    });
                }

            }
            const orderVerification = new OrderVerification(page);

            await cartHelper.addProductToCart(productType);
            await checkoutPage.goToCheckout(checkoutType.isBlockCheckout);
            await checkoutPage.fillBillingDetails(userType);
            const selector = paymentMethod.selector[checkoutType.isBlockCheckout? 'block' : 'classic'];
            await page.click(selector);
            await paymentProcessor.processPayment(paymentMethod.isHostedPayment, paymentMethod.presetCredentials);
            await page.waitForTimeout(3000);
            if (expectSuccess) {
                await orderVerification.verifySuccessfulOrder();
            } else {
                await orderVerification.verifyFailedPayment();
            }
        });
        // todo Clean up after each test
    });
});