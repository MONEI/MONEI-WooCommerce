import { Page, expect } from '@playwright/test';
import { PaymentMethod } from '../fixtures/payment-methods';
import { CheckoutType } from '../fixtures/checkout-types';

export class PayForOrderPage {
    readonly page: Page;
    private checkoutType: CheckoutType;

    // Common locators
    orderTotalSelector: string;
    paymentMethodsSelector: string;
    placeOrderButtonSelector: string;
    savedPaymentMethodsSelector: string;
    errorMessageSelector: string;

    // Credit Card specific locators
    cardholderNameInputSelector: string;
    cardInputContainerSelector: string;
    cardErrorContainerSelector: string;

    constructor(page: Page, checkoutType: CheckoutType) {
        this.page = page;
        this.checkoutType = checkoutType;
        this.initializeLocators();
    }

    private initializeLocators() {
        if (this.checkoutType.isBlockCheckout) {
            this.orderTotalSelector = '.wc-block-components-totals-footer-item .wc-block-components-totals-item__value';
            this.paymentMethodsSelector = '.wc-block-components-radio-control__input';
            this.placeOrderButtonSelector = '.wc-block-components-checkout-place-order-button';
            this.savedPaymentMethodsSelector = '.wc-block-saved-payment-method-options';
            this.errorMessageSelector = '.wc-block-components-notice-banner--error';
        } else {
            this.orderTotalSelector = '.order-total .amount';
            this.paymentMethodsSelector = '.wc_payment_method input[name="payment_method"]';
            this.placeOrderButtonSelector = '#place_order';
            this.savedPaymentMethodsSelector = '.wc-saved-payment-methods';
            this.errorMessageSelector = '.woocommerce-error';
        }

        this.cardholderNameInputSelector = '#monei-card-holder-name';
        this.cardInputContainerSelector = '#monei-card-element';
        this.cardErrorContainerSelector = '#monei-card-errors';
    }

    async navigateToPayForOrderPage(orderId: string, orderKey: string) {
        const url = `/checkout/order-pay/${orderId}/?pay_for_order=true&key=${orderKey}`;
        await this.page.goto(url);
        await this.page.waitForLoadState('networkidle');
    }

    async getOrderTotal(): Promise<string> {
        await this.page.waitForSelector(this.orderTotalSelector);
        const totalElement = await this.page.locator(this.orderTotalSelector).first();
        return await totalElement.innerText();
    }

    async selectPaymentMethod(paymentMethod: PaymentMethod) {
        const selector = paymentMethod.selector.classic;

        await this.page.waitForSelector(selector);
        await this.page.click(selector);

        // Wait for payment method UI to update
        await this.page.waitForTimeout(500);
    }

    async getAvailablePaymentMethods(): Promise<string[]> {
        await this.page.waitForSelector(this.paymentMethodsSelector);

        const methods: string[] = [];
        const inputs = await this.page.$$(this.paymentMethodsSelector);

        for (const input of inputs) {
            const value = await input.getAttribute('value');
            if (value) {
                methods.push(value);
            }
        }

        return methods;
    }

    async fillCardholderName(name: string) {
        await this.page.waitForSelector(this.cardholderNameInputSelector);
        await this.page.fill(this.cardholderNameInputSelector, name);
    }

    async clickPlaceOrder() {
        await this.page.waitForSelector(this.placeOrderButtonSelector);

        // Ensure button is enabled
        await this.page.waitForFunction(
            selector => {
                const button = document.querySelector(selector);
                return button && !(button as HTMLButtonElement).disabled;
            },
            this.placeOrderButtonSelector,
            { timeout: 10000 }
        );

        await this.page.click(this.placeOrderButtonSelector);
    }

    async hasSavedPaymentMethods(): Promise<boolean> {
        try {
            await this.page.waitForSelector(this.savedPaymentMethodsSelector, { timeout: 5000 });
            return true;
        } catch {
            return false;
        }
    }

    async selectSavedPaymentMethod(index: number = 0) {
        const savedMethodSelector = this.checkoutType.isBlockCheckout ?
            `.wc-block-saved-payment-method-options__option:nth-child(${index + 1}) input` :
            `.wc-saved-payment-methods input[type="radio"]:nth-child(${index + 1})`;

        await this.page.click(savedMethodSelector);
    }

    async waitForPaymentProcessing() {
        // Wait for either success redirect or error message
        await Promise.race([
            this.page.waitForSelector('.woocommerce-order-received', { timeout: 30000 }),
            this.page.waitForSelector(this.errorMessageSelector, { timeout: 30000 })
        ]);
    }

    async getErrorMessage(): Promise<string | null> {
        const errorElement = await this.page.locator(this.errorMessageSelector);
        if (await errorElement.isVisible()) {
            return await errorElement.innerText();
        }
        return null;
    }

    async isOrderDetailsVisible(): Promise<boolean> {
        return await this.page.isVisible('.woocommerce-order-pay') ||
            await this.page.isVisible('.wc-block-checkout__order-pay');
    }
}