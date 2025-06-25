import { Page } from '@playwright/test';
import { BasePaymentProcessor } from "./base-payment-processor";
import { PAYMENT_TEST_DATA } from "../../fixtures/payment-test-data";

export class PayPalProcessor extends BasePaymentProcessor {

    // Locators
    readonly paypalContainerSelector = '#paypal-container';
    readonly emailInput = '#email';
    readonly passwordInput = '#password';
    readonly nextButton = '#btnNext'
    readonly loginButton = '#btnLogin';
    readonly submitButton = '[data-testid="submit-button-initial"]';
    readonly paymentConfirmationMessage = '.payment-confirmation';
    readonly errorMessage = '.paypal-error-message';

    constructor(page: Page) {
        super(page);
    }

    /**
     * Feature: PayPal payment processing
     * Scenario: User can pay with PayPal in block checkout
     * Given the user has selected PayPal payment method
     * When the user clicks the PayPal container
     * And is redirected to PayPal login
     * And enters valid credentials
     * And clicks submit button
     * Then the payment should be processed successfully
     *
     * Scenario: User can pay with PayPal in classic checkout
     * Given the user has selected PayPal payment method
     * When the user clicks process payment
     * And is redirected to PayPal login
     * And enters valid credentials
     * And clicks submit button
     * Then the payment should be processed successfully
     */
    async processPayment(expectSuccess: boolean = true, preset: string = 'success') {
        const paypalDetails = PAYMENT_TEST_DATA.paypal[preset];

        await this.page.waitForTimeout(1000);
        const paypalContainer = await this.page.$(this.paypalContainerSelector);

        if (paypalContainer) {
            console.log('Block checkout detected - clicking PayPal container');
            await this.page.click(this.paypalContainerSelector);
        } else {
            console.log('Classic checkout detected - clicking process payment button');
            await this.clickWooSubmitButton();
        }

        await this.handlePayPalLogin(paypalDetails);

        if (expectSuccess) {
            await this.page.waitForNavigation({ waitUntil: 'networkidle' });
        }
    }

    /**
     * Feature: PayPal login with retry logic
     * Scenario: User login credentials may need to be entered twice
     * Given the user is on PayPal login page
     * When the user enters credentials
     * And sometimes needs to retry entering credentials
     * Then the user should be able to login successfully
     */
    private async handlePayPalLogin(paypalDetails: { user: string; pw: string }) {
        await this.page.waitForSelector(this.emailInput, { timeout: 10000 });

        const maxRetries = 2;
        let attempt = 0;

        while (attempt < maxRetries) {
            attempt++;
            console.log(`PayPal login attempt ${attempt}/${maxRetries}`);

            try {
                await this.page.fill(this.emailInput, paypalDetails.user);
                await this.page.waitForTimeout(500);
                await this.page.click(this.nextButton);
                await this.page.fill(this.passwordInput, paypalDetails.pw);
                await this.page.waitForTimeout(500);

                await this.page.click(this.loginButton);
                await this.page.waitForTimeout(2000);

                const stillOnLoginPage = await this.page.$(this.emailInput);

                if (!stillOnLoginPage) {
                    console.log('Login successful, proceeding to payment confirmation');
                    break;
                } else if (attempt < maxRetries) {
                    console.log('Still on login page, retrying credentials...');
                    // Clear the fields before retry
                    await this.page.fill(this.emailInput, '');
                    await this.page.fill(this.passwordInput, '');
                    await this.page.waitForTimeout(1000);
                } else {
                    console.log('Max login attempts reached');
                }

            } catch (error) {
                console.log(`Login attempt ${attempt} failed:`, error.message);
                if (attempt >= maxRetries) {
                    throw error;
                }
            }
        }

        await this.page.waitForSelector(this.submitButton, { timeout: 10000 });
        await this.page.click(this.submitButton);
    }

    /**
     * Feature: PayPal login error handling
     * Scenario: User enters invalid PayPal credentials
     * Given the user is on the PayPal login page
     * When the user enters invalid credentials
     * Then an error message should be displayed
     */
    async handleLoginError(invalidCredentials: {
        user: string;
        pw: string;
    }) {
        await this.page.waitForSelector(this.emailInput);

        await this.page.fill(this.emailInput, invalidCredentials.user);
        await this.page.fill(this.passwordInput, invalidCredentials.pw);
        await this.page.click(this.loginButton);

        const errorSelector = '#errorSection';
        await this.page.waitForSelector(errorSelector);
        return this.page.textContent(errorSelector);
    }

    /**
     * Feature: PayPal payment cancellation
     * Scenario: User cancels PayPal payment
     * Given the user is on the PayPal payment confirmation page
     * When the user clicks the cancel link
     * Then they should be redirected back to the merchant site
     */
    async cancelPayment() {
        const cancelLinkSelector = '#cancelLink';
        await this.page.waitForSelector(cancelLinkSelector);
        await this.page.click(cancelLinkSelector);
        await this.page.waitForNavigation({ waitUntil: 'networkidle' });
    }

    /**
     * Feature: PayPal error handling
     * Scenario: PayPal payment fails
     * Given the user has initiated a PayPal payment
     * When there's an error processing the payment
     * Then an error message should be displayed
     */
    async handlePaymentError() {
        await this.page.waitForSelector(this.errorMessage);
        return this.page.textContent(this.errorMessage);
    }
}