import { Page } from '@playwright/test';

export abstract class BasePaymentProcessor {
    readonly page: Page;
    readonly wooSubmitButtonSelectors: string[] = [
        '#place_order',
        '.wc-block-components-checkout-place-order-button'
    ];

    constructor(page: Page) {
        this.page = page;
    }

    /**
     * Clicks on the WooCommerce submit button, checking for multiple possible selectors
     */
    async clickWooSubmitButton(): Promise<void> {
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

    // Define abstract methods that all payment processors must implement
    abstract processPayment(isHostedPayment?: boolean, preset?: string): Promise<void>;
}