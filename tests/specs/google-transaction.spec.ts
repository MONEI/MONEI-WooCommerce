import { test, expect } from '@playwright/test';
import { PAYMENT_METHODS } from '../fixtures/payment-methods';
import { CHECKOUT_TYPES } from '../fixtures/checkout-types';
import { PRODUCT_TYPES } from '../fixtures/product-types';
import { USER_TYPES } from '../fixtures/user-types';
import { CheckoutPage } from '../pages/checkout-page';
import { CartPage } from '../pages/cart-page';

test.describe('Google Pay Payment Tests', () => {
    const testCases = [
        {
            name: 'Classic',
            checkoutType: CHECKOUT_TYPES.CLASSIC,
            selector: PAYMENT_METHODS.GOOGLE_PAY.selector.classic
        },
        {
            name: 'Block',
            checkoutType: CHECKOUT_TYPES.BLOCK,
            selector: PAYMENT_METHODS.GOOGLE_PAY.selector.block
        }
    ];

    testCases.forEach(({ name, checkoutType, selector }) => {
        test(`Google Pay Success - ${name} Checkout`, async ({ page }) => {
            const checkoutPage = new CheckoutPage(page, checkoutType);
            const cartHelper = new CartPage(page);

            await cartHelper.addProductToCart(PRODUCT_TYPES.SIMPLE);
            await checkoutPage.goToCheckout(checkoutType);
            await checkoutPage.fillBillingDetails(USER_TYPES.ES_USER);

            await page.click(selector);
            await page.waitForTimeout(1000);

            const iframe = page.locator('iframe[name^="__zoid__monei_payment_request__"]');
            await expect(iframe).toBeVisible({ timeout: 5000 });
            console.log('✅ Google Pay button is visible');
        });
    });
});