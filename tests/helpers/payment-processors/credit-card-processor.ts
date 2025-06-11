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

    constructor(page: Page, isHosted: boolean) {
        this.page = page;
        this.isHosted = isHosted;
        if (isHosted) {
            this.cardNumberInput = '';
            this.cardExpiryInput = '';
            this.cardCvcInput = '';
            this.cardholderNameInput = '';
            this.submitButton = '#submit-button';
        } else {
            this.cardNumberInput = '#monei-card-number';
            this.cardExpiryInput = '#monei-card-expiry';
            this.cardCvcInput = '#monei-card-cvc';
            this.cardholderNameInput = '#monei-card-holder-name';
            this.submitButton = '#place_order';
        }
    }

    async processPayment(isHostedPayment, preset: string = 'success') {
        const cardDetails = PAYMENT_TEST_DATA.creditCard[preset];
        if(isHostedPayment) {
            console.log('redirect')
            await this.page.click(this.submitButton);
            //fill the redirected page
        } else {
            await this.page.fill(this.cardNumberInput, cardDetails.cardNumber);
            await this.page.fill(this.cardExpiryInput, cardDetails.expiry);
            await this.page.fill(this.cardCvcInput, cardDetails.cvc);
            await this.page.fill(this.cardholderNameInput, cardDetails.cardholderName);
            await this.page.click(this.submitButton);
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