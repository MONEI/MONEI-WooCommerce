import { Page, expect } from '@playwright/test';
import { PaymentMethod } from '../fixtures/payment-methods';
import { CheckoutType } from '../fixtures/checkout-types';
import { UserState } from '../fixtures/user-states';

export class CheckoutPage {
    readonly page: Page;
    private checkoutType: CheckoutType;

    // Common locators (will be initialized differently based on checkout type)
    billingFirstNameInput;
    billingLastNameInput;
     billingAddressInput;
     billingCityInput;
     billingPostcodeInput;
     billingPhoneInput;
     billingEmailInput;
     paymentMethodOptions;
     placeOrderButton;
     savedPaymentMethodsSection;
     savePaymentMethodCheckbox;

    // Credit Card specific locators
    cardholderNameInput;
    cardInputContainer;
    cardErrorContainer;

    constructor(page: Page, checkoutType: CheckoutType) {
        this.page = page;
        this.checkoutType = checkoutType;
        this.initializeLocators();
    }

    private initializeLocators() {
        if (this.checkoutType.isBlockCheckout == true) {
            this.billingFirstNameInput = '#billing-first_name';
            this.billingLastNameInput = '#billing-last_name';
            this.billingAddressInput = '#billing-address_1';
            this.billingCityInput = '#billing-city';
            this.billingPostcodeInput = '#billing-postcode';
            this.billingPhoneInput = '#billing-phone';
            this.billingEmailInput = '#email';
            this.paymentMethodOptions = '.wc-block-components-radio-control__input';
            this.placeOrderButton = '.wc-block-components-checkout-place-order-button';
            this.savedPaymentMethodsSection = '.wc-block-components-payment-methods__saved-payment-methods';
            this.savePaymentMethodCheckbox = '#save-payment-method';
        } else {
            this.billingFirstNameInput = '#billing_first_name';
            this.billingLastNameInput = '#billing_last_name';
            this.billingAddressInput = '#billing_address_1';
            this.billingCityInput = '#billing_city';
            this.billingPostcodeInput = '#billing_postcode';
            this.billingPhoneInput = '#billing_phone';
            this.billingEmailInput = '#billing_email';
            this.paymentMethodOptions = '.wc_payment_method input[name="payment_method"]';
            this.placeOrderButton = '#place_order';
            this.savedPaymentMethodsSection = '.woocommerce-SavedPaymentMethods-token';
            this.savePaymentMethodCheckbox = '#wc-monei-new-payment-method';
        }

        this.cardholderNameInput = '#monei-card-holder-name';
        this.cardInputContainer = '#monei-card-element';
        this.cardErrorContainer = '#monei-card-errors';
    }

    async fillBillingDetails(details: {
        firstName: string;
        lastName: string;
        address: string;
        city: string;
        postcode: string;
        phone: string;
        email: string;
        country?: string;
        state?: string;
    }) {
        await this.page.fill(this.billingEmailInput, details.email);
        if (details.country) {
            await this.page.selectOption(this.checkoutType.isBlockCheckout ? '#billing-country' : '#billing_country', details.country);
        }
        if (details.state) {
            await this.page.selectOption(this.checkoutType.isBlockCheckout ? '#billing-state' : '#billing_state', details.state);
        }
        await this.page.fill(this.billingFirstNameInput, details.firstName);
        await this.page.fill(this.billingLastNameInput, details.lastName);
        await this.page.fill(this.billingAddressInput, details.address);
        await this.page.fill(this.billingCityInput, details.city);
        await this.page.fill(this.billingPostcodeInput, details.postcode);
        await this.page.fill(this.billingPhoneInput, details.phone);
    }

    async goToCheckout(checkoutType: CheckoutType) {
        const { page } = this;
        // Navigate to the cart page
        if (this.checkoutType.isBlockCheckout == true) {
            await page.goto('/checkout/');
        }else {
            await page.goto('/checkout-short-code/');
        }
    }

    async selectPaymentMethod(paymentMethod: PaymentMethod) {
        await this.page.click(`${this.paymentMethodOptions}[value="${paymentMethod.id}"]`);
        await this.page.waitForSelector(`#payment_method_${paymentMethod.id}`);
    }

    async loginDuringCheckout(username: string, password: string) {
        if (this.checkoutType.isBlockCheckout) {
            await this.page.click('.wc-block-components-checkout-step__heading-content a');
        } else {
            await this.page.click('.showlogin');
        }
        await this.page.fill('#username', username);
        await this.page.fill('#password', password);
        await this.page.click('button[name="login"]');
        await this.page.waitForLoadState('networkidle');
    }

    async selectSavedPaymentMethod(tokenId: string) {
        await this.page.click(`${this.savedPaymentMethodsSection} input[value="${tokenId}"]`);
    }

    async toggleSavePaymentMethod(save: boolean) {
        if (save) {
            await this.page.check(this.savePaymentMethodCheckbox);
        } else {
            await this.page.uncheck(this.savePaymentMethodCheckbox);
        }
    }

    async placeOrder() {
        await this.page.click(this.placeOrderButton);
        await this.page.waitForNavigation({ waitUntil: 'networkidle' });
    }
    // New helper methods for block checkout
    async fillBlockCheckoutBillingDetails(details: {
        firstName: string;
        lastName: string;
        address: string;
        city: string;
        postcode: string;
        phone: string;
        email: string;
        country?: string;
        state?: string;
    }) {
        if (!this.checkoutType.isBlockCheckout) return;

        await this.page.fill(this.billingFirstNameInput, details.firstName);
        await this.page.fill(this.billingLastNameInput, details.lastName);
        await this.page.fill(this.billingAddressInput, details.address);
        await this.page.fill(this.billingCityInput, details.city);
        await this.page.fill(this.billingPostcodeInput, details.postcode);
        await this.page.fill(this.billingPhoneInput, details.phone);
        await this.page.fill(this.billingEmailInput, details.email);

        if (details.country) {
            await this.page.selectOption('#billing-country', details.country);
        }
        if (details.state) {
            await this.page.selectOption('#billing-state', details.state);
        }
    }

    async selectBlockCheckoutPaymentMethod(paymentMethod: PaymentMethod) {
        if (!this.checkoutType.isBlockCheckout) return;

        await this.page.click(`.wc-block-components-radio-control__input[value="${paymentMethod.id}"]`);
        await this.page.waitForSelector(`#wc-${paymentMethod.id}-payment-method`);
    }

    async getBlockCheckoutOrderTotal(): Promise<string> {
        if (!this.checkoutType.isBlockCheckout) return '';

        const totalElement = await this.page.locator('.wc-block-components-totals-footer-item .wc-block-components-totals-item__value');
        return totalElement.innerText();
    }

    // New helper methods for classic checkout
    async fillClassicCheckoutBillingDetails(details: {
        firstName: string;
        lastName: string;
        address: string;
        city: string;
        postcode: string;
        phone: string;
        email: string;
        country?: string;
        state?: string;
    }) {
        if (this.checkoutType.isBlockCheckout) return;

        await this.page.fill(this.billingFirstNameInput, details.firstName);
        await this.page.fill(this.billingLastNameInput, details.lastName);
        await this.page.fill(this.billingAddressInput, details.address);
        await this.page.fill(this.billingCityInput, details.city);
        await this.page.fill(this.billingPostcodeInput, details.postcode);
        await this.page.fill(this.billingPhoneInput, details.phone);
        await this.page.fill(this.billingEmailInput, details.email);

        if (details.country) {
            await this.page.selectOption('#billing_country', details.country);
        }
        if (details.state) {
            await this.page.selectOption('#billing_state', details.state);
        }
    }

    async selectClassicCheckoutPaymentMethod(paymentMethod: PaymentMethod) {
        if (this.checkoutType.isBlockCheckout) return;

        await this.page.click(`input[name="payment_method"][value="${paymentMethod.id}"]`);
        await this.page.waitForSelector(`#payment_method_${paymentMethod.id}`);
    }

    async getClassicCheckoutOrderTotal(): Promise<string> {
        if (this.checkoutType.isBlockCheckout) return '';

        const totalElement = await this.page.locator('.order-total .amount');
        return totalElement.innerText();
    }

    // Common helper methods
    async waitForCheckoutLoad() {
        if (this.checkoutType.isBlockCheckout) {
            await this.page.waitForSelector('.wc-block-checkout__main');
        } else {
            await this.page.waitForSelector('#order_review');
        }
    }

    async verifyOrderDetails(expectedTotal: string) {
        let actualTotal: string;
        if (this.checkoutType.isBlockCheckout) {
            actualTotal = await this.getBlockCheckoutOrderTotal();
        } else {
            actualTotal = await this.getClassicCheckoutOrderTotal();
        }
        expect(actualTotal).toBe(expectedTotal);
    }

    async fillCreditCardDetails(cardDetails: {
        cardNumber: string;
        expiryDate: string;
        cvc: string;
        cardholderName: string;
    }) {
        await this.page.fill(this.cardholderNameInput, cardDetails.cardholderName);

        // Assuming the card details are entered into an iframe
        const frameHandle = await this.page.waitForSelector('#monei-card-element iframe');
        const frame = await frameHandle.contentFrame();

        await frame?.fill('[name="cardnumber"]', cardDetails.cardNumber);
        await frame?.fill('[name="exp-date"]', cardDetails.expiryDate);
        await frame?.fill('[name="cvc"]', cardDetails.cvc);
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
}