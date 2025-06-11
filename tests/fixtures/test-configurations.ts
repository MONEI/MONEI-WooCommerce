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
export const TEST_CONFIGURATIONS = {
    QUICK: generateTestConfigurations({
        paymentMethods: [PAYMENT_METHODS.CREDIT_CARD_HOSTED],
        checkoutTypes: [CHECKOUT_TYPES.CLASSIC],
        productTypes: [PRODUCT_TYPES.SIMPLE],
        userStates: [USER_STATES.GUEST],
        expectSuccess: true,
        userTypes: [USER_TYPES.ES_USER]
    }),

    FULL_MATRIX: generateTestConfigurations({}),

    SUBSCRIPTION_TESTS: generateTestConfigurations({
        productTypes: [PRODUCT_TYPES.WC_SUBSCRIPTION, PRODUCT_TYPES.YITH_SUBSCRIPTION],
        paymentMethods: Object.values(PAYMENT_METHODS).filter(pm => pm.isApplicableToSubscription),
        checkoutTypes: [CHECKOUT_TYPES.CLASSIC],
        userStates: [USER_STATES.LOGGED_IN],
        expectSuccess: true,
        userTypes: [USER_TYPES.ES_USER]
    })
};