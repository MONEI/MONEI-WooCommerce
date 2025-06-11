import { Page } from '@playwright/test';
import { PayPalProcessor } from './payment-processors/paypal-processor';
import { CreditCardProcessor } from './payment-processors/credit-card-processor';
import { BizumProcessor } from './payment-processors/bizum-processor';

export function getPaymentProcessor(paymentMethod: string, page: Page): PayPalProcessor | CreditCardProcessor | BizumProcessor {
    switch (paymentMethod) {
        case 'monei-paypal':
            return new PayPalProcessor(page);
        case 'monei':
            return new CreditCardProcessor(page, false);
        case 'monei-hosted':
            return new CreditCardProcessor(page, true);
        case 'monei-bizum':
            return new BizumProcessor(page);
        default:
            throw new Error(`Unsupported payment method: ${paymentMethod}`);
    }
}