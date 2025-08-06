/**
 * Represents the configuration for a payment method
 */
export interface PaymentMethod {
    id: string;
    name: string;
    className: string; // CSS class used to identify the method
    isApplicableToSubscription: boolean;
    paymentProcessFunction: string; // Name of the function to process this payment
    isHostedPayment: boolean; // Whether payment happens on external page
    countriesSupported: string[]; // ISO country codes where this method is available
    selector: {
        classic: string;
        block: string;
    };
    presetCredentials: string;
}

/**
 * Available payment methods in the MONEI gateway
 */
export const PAYMENT_METHODS: Record<string, PaymentMethod> = {
    // Credit Card (with Component)
    CREDIT_CARD_SUCCESS: {
        id: 'monei',
        name: 'Credit Card Success',
        className: 'wc-monei-credit-card-payment-method',
        isApplicableToSubscription: true,
        paymentProcessFunction: 'processCreditCardPayment',
        isHostedPayment: false,
        countriesSupported: ['ES', 'PT', 'FR', 'DE', 'IT', 'UK'], // Example countries
        selector: {
            classic: 'input[name="payment_method"][value="monei"]',
            block: '.wc-block-components-radio-control__input[value="monei"]'
        },
        presetCredentials: 'success'
    },

    // Credit Card (with Redirect/Hosted)
    CREDIT_CARD_3DSECURE: {
        id: 'monei',
        name: 'Credit Card (3DSecure)',
        className: 'wc-monei-credit-card-hosted-payment-method',
        isApplicableToSubscription: true,
        paymentProcessFunction: 'processCreditCardHostedPayment',
        isHostedPayment: true,
        countriesSupported: ['ES', 'PT', 'FR', 'DE', 'IT', 'GB'], // Example countries
        selector: {
            classic: 'input[name="payment_method"][value="monei"]',
            block: '.wc-block-components-radio-control__input[value="monei"]'
        },
        presetCredentials: 'threeDSecure'
    },
    CREDIT_CARD_FAIL: {
        id: 'monei',
        name: 'Credit Card (Hosted) fail',
        className: 'wc-monei-credit-card-hosted-payment-method',
        isApplicableToSubscription: true,
        paymentProcessFunction: 'processCreditCardHostedPayment',
        isHostedPayment: true,
        countriesSupported: ['ES', 'PT', 'FR', 'DE', 'IT', 'GB'], // Example countries
        selector: {
            classic: 'input[name="payment_method"][value="monei"]',
            block: '.wc-block-components-radio-control__input[value="monei"]'
        },
        presetCredentials: 'fail'
    },

    // Bizum
    BIZUM: {
        id: 'monei_bizum',
        name: 'Bizum',
        className: 'wc-monei-bizum-payment-method',
        isApplicableToSubscription: false,
        paymentProcessFunction: 'processBizumPayment',
        isHostedPayment: true,
        countriesSupported: ['ES'],
        selector: {
            classic: 'input[name="payment_method"][value="monei_bizum"]',
            block: '.wc-block-components-radio-control__input[value="monei_bizum"]'
        },
        presetCredentials: 'success'
    },

    // PayPal
    PAYPAL: {
        id: 'monei_paypal',
        name: 'PayPal',
        className: 'wc-monei-paypal-payment-method',
        isApplicableToSubscription: true,
        paymentProcessFunction: 'processPayPalPayment',
        isHostedPayment: true,
        countriesSupported: ['ES', 'PT', 'FR', 'DE', 'IT', 'GB', 'US'], // Example countries
        selector: {
            classic: 'input[name="payment_method"][value="monei_paypal"]',
            block: '.wc-block-components-radio-control__input[value="monei_paypal"]'
        },
        presetCredentials: 'success'
    },
    // Apple Pay
    APPLE_PAY: {
        id: 'monei_apple_pay',
        name: 'Apple Pay',
        className: 'wc-monei-apple-pay-payment-method',
        isApplicableToSubscription: true,
        paymentProcessFunction: 'processApplePayPayment',
        isHostedPayment: false,
        countriesSupported: ['ES', 'PT', 'FR', 'DE', 'IT', 'GB', 'US'], // Example countries
        selector: {
            classic: 'input[name="payment_method"][value="monei_apple_pay"]',
            block: '.wc-block-components-radio-control__input[value="monei_apple_pay"]'
        },
        presetCredentials: 'success'
    },
    // Google Pay
    GOOGLE_PAY: {
        id: 'monei_google_pay',
        name: 'Google Pay',
        className: 'wc-monei-google-pay-payment-method',
        isApplicableToSubscription: true,
        paymentProcessFunction: 'processGooglePayPayment',
        isHostedPayment: false,
        countriesSupported: ['ES', 'PT', 'FR', 'DE', 'IT', 'GB', 'US'], // Example countries
        selector: {
            classic: 'input[name="payment_method"][value="monei_apple_google"]',
            block: '.wc-block-components-radio-control__input[value="monei_apple_google"]'
        },
        presetCredentials: 'success'
    },
    // Multibanco
    MULTIBANCO: {
        id: 'monei_multibanco',
        name: 'Multibanco',
        className: 'wc-monei-multibanco-payment-method',
        isApplicableToSubscription: false,
        paymentProcessFunction: 'processMultibancoPayment',
        isHostedPayment: true,
        countriesSupported: ['PT'],
        selector: {
            classic: 'input[name="payment_method"][value="monei_multibanco"]',
            block: '.wc-block-components-radio-control__input[value="monei_multibanco"]'
        },
        presetCredentials: 'success'
    },
    // MBWay
    MBWAY: {
        id: 'monei_mbway',
        name: 'MBWay',
        className: 'wc-monei-mbway-payment-method',
        isApplicableToSubscription: false,
        paymentProcessFunction: 'processMBWayPayment',
        isHostedPayment: true,
        countriesSupported: ['PT'],
        selector: {
            classic: 'input[name="payment_method"][value="monei_mbway"]',
            block: '.wc-block-components-radio-control__input[value="monei_mbway"]'
        },
        presetCredentials: 'success'
    },
};