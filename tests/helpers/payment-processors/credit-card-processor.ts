import { Page } from '@playwright/test';
import { PAYMENT_TEST_DATA } from '../../fixtures/payment-test-data';

export class CreditCardProcessor {
    readonly page: Page;
    readonly isHosted: boolean;
    // Locators
    readonly cardNumberInput: string;
    readonly cardExpiryInput: string;
    readonly cardCvcInput: string;
    readonly cardholderNameInput: string;
    readonly submitButton: string;
    readonly wooSubmitButtonSelectors: string[];

    constructor(page: Page, isHosted: boolean) {
        this.page = page;
        this.isHosted = isHosted;
        this.cardNumberInput = 'card-number-input';
        this.cardExpiryInput = 'expiry-date-input';
        this.cardCvcInput = 'cvc-input';
        this.cardholderNameInput = 'cardholder-name-input';
        this.submitButton = 'pay-button';
        this.wooSubmitButtonSelectors = [
            '#place_order',
            '.wc-block-components-checkout-place-order-button'
        ];
    }

    /**
     * Clicks on the WooCommerce submit button, checking for multiple possible selectors
     */
    async clickWooSubmitButton() {
        // Try each selector in order until one is found
        for (const selector of this.wooSubmitButtonSelectors) {
            const buttonExists = await this.page.$(selector) !== null;
            if (buttonExists) {
                await this.page.click(selector);
                return;
            }
        }
        throw new Error('WooCommerce submit button not found. Tried selectors: ' + this.wooSubmitButtonSelectors.join(', '));
    }

    async processPayment(isHostedPayment, preset: string = 'success') {
        const cardDetails = PAYMENT_TEST_DATA.creditCard[preset];
        if(isHostedPayment) {
            await this.clickWooSubmitButton()
            await this.page.waitForLoadState('networkidle');

            const frameLocator = this.page.frameLocator('iframe[name^="__zoid__monei_card_input__"]');

            await this.page.getByTestId(this.cardholderNameInput).fill(cardDetails.cardholderName);

            await frameLocator.getByTestId(this.cardNumberInput).fill(cardDetails.cardNumber);
            await frameLocator.getByTestId(this.cardExpiryInput).fill(cardDetails.expiry);
            await frameLocator.getByTestId(this.cardCvcInput).fill(cardDetails.cvc);

            await this.page.getByTestId(this.submitButton).click();
        } else {
            await this.page.getByTestId(this.cardNumberInput).fill(cardDetails.cardNumber);
            await this.page.getByTestId(this.cardExpiryInput).fill(cardDetails.expiry);
            await this.page.getByTestId(this.cardCvcInput).fill(cardDetails.cvc);
            await this.page.getByTestId(this.cardholderNameInput).fill(cardDetails.cardholderName);
            await this.clickWooSubmitButton()
            if (preset === 'threeDSecure') {
                await this.complete3DSecure();
            } else {
                await this.page.waitForNavigation({ waitUntil: 'networkidle' });
            }
        }
    }

    async validateCardErrors(invalidCard: {
        cardNumber: string;
        expiry: string;
        cvc: string;
        cardholderName: string;
    }) {
        await this.page.fill(this.cardNumberInput, invalidCard.cardNumber);
        await this.page.fill(this.cardExpiryInput, invalidCard.expiry);
        await this.page.fill(this.cardCvcInput, invalidCard.cvc);
        await this.page.fill(this.cardholderNameInput, invalidCard.cardholderName);
        await this.page.click(this.submitButton);

        const errorSelector = '.woocommerce-error, .monei-error-message';
        await this.page.waitForSelector(errorSelector);
        return this.page.$$eval(errorSelector, errors => errors.map(e => e.textContent?.trim()));
    }

    async complete3DSecure() {
        // Wait for 3D Secure iframe to load
        await this.page.waitForSelector('iframe[name^="monei-3ds-"]');
        
        // Switch to 3D Secure iframe
        // @ts-ignore
        const frame = this.page.frame({ name: /monei-3ds-/ });
        if (!frame) throw new Error('3D Secure iframe not found');

        // Complete 3D Secure verification (this may vary depending on the bank's 3D Secure implementation)
        await frame.waitForSelector('#password');
        await frame.fill('#password', '1234');
        await frame.click('#submit-button');

        // Wait for redirection back to the order received page
        await this.page.waitForNavigation({ waitUntil: 'networkidle' });
    }
}