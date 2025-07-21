import { Page } from '@playwright/test';
import {BasePaymentProcessor} from "./base-payment-processor";
import {PAYMENT_TEST_DATA} from "../../fixtures/payment-test-data";

export class BizumProcessor extends BasePaymentProcessor {

    // Locators
    readonly bizumContainerSelector = '#bizum-container';
    readonly phoneInput = 'bizum-phone-input';
    readonly bizumButton = 'bizum-button';
    readonly submitButton = 'bizum-pay-button';
    readonly confirmButton = '#bizum-confirm';
    readonly successMessage = '.bizum-success-message';
    readonly errorMessage = '.bizum-error-message';

    constructor(page: Page) {
        super(page);
    }

    /**
     * Feature: Bizum payment
     * Scenario: User can pay with Bizum
     * Given the user has been redirected to Bizum
     * When the user enters their phone number
     * And confirms the payment
     * Then the payment should be processed successfully
     */
    async processPayment(expectSuccess, preset: string = 'success') {
        const bizumDetails = PAYMENT_TEST_DATA.bizum[preset];

        await this.page.waitForTimeout(1000);
        await this.page.click(this.bizumContainerSelector);
        const frameLocator = this.page.frameLocator('iframe[name^="__zoid__monei_bizum__"]');
        await frameLocator.getByTestId(this.phoneInput).fill(bizumDetails.phoneNumber);
        await frameLocator.getByTestId(this.submitButton).click();

        await this.page.waitForTimeout(10000);
    }

    /**
     * Feature: Bizum payment error handling
     * Scenario: User enters invalid phone number
     * Given the user is on the Bizum payment page
     * When the user enters an invalid phone number
     * Then an error message should be displayed
     */
    async handleInvalidPhoneNumber(invalidPhone: string) {
        await this.page.fill(this.phoneInput, invalidPhone);
        await this.page.click(this.submitButton);

        await this.page.waitForSelector(this.errorMessage);
        return this.page.textContent(this.errorMessage);
    }

    /**
     * Feature: Bizum payment cancellation
     * Scenario: User cancels Bizum payment
     * Given the user is on the Bizum payment confirmation page
     * When the user clicks the cancel button
     * Then they should be redirected back to the merchant site
     */
    async cancelPayment() {
        const cancelButtonSelector = '#bizum-cancel';
        await this.page.waitForSelector(cancelButtonSelector);
        await this.page.click(cancelButtonSelector);
        await this.page.waitForNavigation({ waitUntil: 'networkidle' });
    }

    /**
     * Feature: Bizum payment timeout
     * Scenario: Bizum payment times out
     * Given the user has initiated a Bizum payment
     * When the payment is not confirmed within the timeout period
     * Then an error message should be displayed
     */
    async handlePaymentTimeout() {
        const timeoutErrorSelector = '.bizum-timeout-error';
        await this.page.waitForSelector(timeoutErrorSelector, { timeout: 120000 }); // 2 minutes timeout
        return this.page.textContent(timeoutErrorSelector);
    }
}