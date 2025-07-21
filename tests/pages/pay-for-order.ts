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
        } else {
            this.orderTotalSelector = '.order-total .amount';
            this.paymentMethodsSelector = '.wc_payment_method input[name="payment_method"]';
            this.placeOrderButtonSelector = '#place_order';
        }

        this.cardholderNameInputSelector = '#monei-card-holder-name';
        this.cardInputContainerSelector = '#monei-card-element';
        this.cardErrorContainerSelector = '#monei-card-errors';
    }

    async navigateToPayForOrderPage(orderId: string) {
        await this.page.goto(`/checkout/order-pay/${orderId}/?pay_for_order=true&key=wc_order_XXXXXXXXXXXXX`);
        await this.waitForPageLoad();
    }

    async waitForPageLoad() {
        if (this.checkoutType.isBlockCheckout) {
            await this.page.waitForSelector('.wc-block-checkout__main');
        } else {
            await this.page.waitForSelector('#order_review');
        }
    }

    async getOrderTotal(): Promise<string> {
        const totalElement = await this.page.locator(this.orderTotalSelector);
        return totalElement.innerText();
    }

    async selectPaymentMethod(paymentMethod: PaymentMethod) {
        await this.page.click(`${this.paymentMethodsSelector}[value="${paymentMethod.id}"]`);
        await this.page.waitForSelector(`#payment_method_${paymentMethod.id}`);
    }

    async fillCreditCardDetails(cardDetails: {
        cardNumber: string;
        expiryDate: string;
        cvc: string;
        cardholderName: string;
    }) {
        await this.page.fill(this.cardholderNameInputSelector, cardDetails.cardholderName);

        // Assuming the card details are entered into an iframe
        const frameHandle = await this.page.waitForSelector(`${this.cardInputContainerSelector} iframe`);
        const frame = await frameHandle.contentFrame();

        await frame?.fill('[name="cardnumber"]', cardDetails.cardNumber);
        await frame?.fill('[name="exp-date"]', cardDetails.expiryDate);
        await frame?.fill('[name="cvc"]', cardDetails.cvc);
    }

    async placeOrder() {
        await this.page.click(this.placeOrderButtonSelector);
        await this.page.waitForNavigation({ waitUntil: 'networkidle' });
    }

    async verifyOrderDetails(expectedTotal: string) {
        const actualTotal = await this.getOrderTotal();
        expect(actualTotal).toBe(expectedTotal);
    }

    async verifyPaymentMethodVisibility(paymentMethod: PaymentMethod, shouldBeVisible: boolean) {
        const selector = this.checkoutType.isBlockCheckout
            ? `.wc-block-components-radio-control__input[value="${paymentMethod.id}"]`
            : `input[name="payment_method"][value="${paymentMethod.id}"]`;

        if (shouldBeVisible) {
            await expect(this.page.locator(selector)).toBeVisible();
        } else {
            await expect(this.page.locator(selector)).toBeHidden();
        }
    }

    async getErrorMessage(): Promise<string | null> {
        const errorSelector = this.checkoutType.isBlockCheckout
            ? '.wc-block-components-notice-banner__content'
            : '.woocommerce-error';

        const errorElement = await this.page.locator(errorSelector);
        return errorElement.isVisible() ? errorElement.innerText() : null;
    }

    async waitForSuccessfulPayment() {
        // Wait for the success message or redirection to the order received page
        await this.page.waitForSelector('.woocommerce-order-received', { timeout: 30000 });
    }
}
