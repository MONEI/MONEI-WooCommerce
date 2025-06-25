import { PAYMENT_METHODS, PaymentMethod } from './payment-methods';
import { CHECKOUT_TYPES, CheckoutType } from './checkout-types';
import { PRODUCT_TYPES, ProductType } from './product-types';
import { USER_STATES, UserState } from './user-states';
import { USER_TYPES, UserType } from './user-types';


/**
 * Represents a complete test configuration
 */
export interface TestConfiguration {
    paymentMethod: PaymentMethod;
    checkoutType: CheckoutType;
    productType: ProductType;
    userState: UserState;
    expectSuccess: boolean;
    userType: UserType;
}

/**
 * Generates test configurations based on provided filters
 */
export function generateTestConfigurations(options: {
    paymentMethods?: PaymentMethod[];
    checkoutTypes?: CheckoutType[];
    productTypes?: ProductType[];
    userStates?: UserState[];
    expectSuccess?: boolean;
    userTypes?: UserType[];
}): TestConfiguration[] {
    const {
        paymentMethods = Object.values(PAYMENT_METHODS),
        checkoutTypes = Object.values(CHECKOUT_TYPES),
        productTypes = Object.values(PRODUCT_TYPES),
        userStates = Object.values(USER_STATES),
        expectSuccess = true,
        userTypes = Object.values(USER_TYPES)
    } = options;

    const configurations: TestConfiguration[] = [];

    for (const paymentMethod of paymentMethods) {
        for (const checkoutType of checkoutTypes) {
            for (const productType of productTypes) {
                for (const userState of userStates) {
                    for (const userType of userTypes) {
                        configurations.push({
                            paymentMethod,
                            checkoutType,
                            productType,
                            userState,
                            expectSuccess,
                            userType
                        });
                    }

                }
            }
        }
    }

    return configurations;
}

/**
 * Predefined common test configurations
 */
// tests/fixtures/test-configurations.ts

export const TEST_CONFIGURATIONS = {
    // Quick smoke test
    QUICK: generateTestConfigurations({
        paymentMethods: [PAYMENT_METHODS.CREDIT_CARD_SUCCESS],
        checkoutTypes: [CHECKOUT_TYPES.CLASSIC],
        productTypes: [PRODUCT_TYPES.SIMPLE],
        userStates: [USER_STATES.GUEST],
        userTypes: [USER_TYPES.ES_USER]
    }),
    //PayPal Tests
    PAYPAL_TESTS: [
        {
            paymentMethod: PAYMENT_METHODS.PAYPAL,
            checkoutType: [CHECKOUT_TYPES.CLASSIC, CHECKOUT_TYPES.BLOCK],
            productType: PRODUCT_TYPES.SIMPLE,
            userState: USER_STATES.GUEST,
            userType: USER_TYPES.ES_USER,
            expectSuccess: true
        },
    ],
    // Bizum-specific tests
    BIZUM_TESTS: [
        // Bizum success scenario
       {
            paymentMethod: PAYMENT_METHODS.BIZUM,
            checkoutType: CHECKOUT_TYPES.CLASSIC,
            productType: PRODUCT_TYPES.BIZUM_SUCCESS,
            userState: USER_STATES.GUEST,
            userType: USER_TYPES.ES_USER,
            expectSuccess: true
        },
        {
            paymentMethod: PAYMENT_METHODS.BIZUM,
            checkoutType: CHECKOUT_TYPES.BLOCK,
            productType: PRODUCT_TYPES.BIZUM_SUCCESS,
            userState: USER_STATES.GUEST,
            userType: USER_TYPES.ES_USER,
            expectSuccess: true
        },
        // Bizum failure scenario
        {
            paymentMethod: PAYMENT_METHODS.BIZUM,
            checkoutType: CHECKOUT_TYPES.CLASSIC,
            productType: PRODUCT_TYPES.BIZUM_FAILS,
            userState: USER_STATES.GUEST,
            userType: USER_TYPES.ES_USER,
            expectSuccess: false
        }
    ],

    // Portugal-specific payment methods
    PORTUGAL_TESTS: [
        {
            paymentMethod: PAYMENT_METHODS.MULTIBANCO,
            checkoutType: CHECKOUT_TYPES.CLASSIC,
            productType: PRODUCT_TYPES.SIMPLE,
            userState: USER_STATES.GUEST,
            userType: USER_TYPES.PT_USER,
            expectSuccess: true
        },
        {
            paymentMethod: PAYMENT_METHODS.MBWAY,
            checkoutType: CHECKOUT_TYPES.BLOCK,
            productType: PRODUCT_TYPES.SIMPLE,
            userState: USER_STATES.LOGGED_IN,
            userType: USER_TYPES.PT_USER,
            expectSuccess: true
        }
    ],

    // Credit card comprehensive tests
    CREDIT_CARD_TESTS: generateTestConfigurations({
        paymentMethods: [PAYMENT_METHODS.CREDIT_CARD_SUCCESS, PAYMENT_METHODS.CREDIT_CARD_HOSTED],
        checkoutTypes: Object.values(CHECKOUT_TYPES),
        productTypes: [PRODUCT_TYPES.SIMPLE, PRODUCT_TYPES.VARIABLE],
        userStates: Object.values(USER_STATES),
        userTypes: Object.values(USER_TYPES)
    }),

    // Subscription tests
    SUBSCRIPTION_TESTS: generateTestConfigurations({
        productTypes: [PRODUCT_TYPES.WOO_SUBSCRIPTION, PRODUCT_TYPES.YITH_SUBSCRIPTION],
        paymentMethods: Object.values(PAYMENT_METHODS).filter(pm => pm.isApplicableToSubscription),
        checkoutTypes: [CHECKOUT_TYPES.CLASSIC, CHECKOUT_TYPES.BLOCK],
        userStates: [USER_STATES.LOGGED_IN], // Subscriptions usually require login
        userTypes: [USER_TYPES.ES_USER, USER_TYPES.US_USER]
    })
};