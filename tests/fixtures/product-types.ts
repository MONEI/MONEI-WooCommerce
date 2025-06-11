/**
 * Represents the configuration for a product type
 */
export interface ProductType {
    id: string;
    name: string;
    isSubscription: boolean;
    subscriptionPlugin?: string; // 'woocommerce' or 'yith'
    sampleProductId?: number; // ID of a sample product of this type for testing
    sampleProductSlug?: string; // Slug of a sample product
    price?: number; // Price to expect
}

/**
 * Available product types to test with
 */
export const PRODUCT_TYPES: Record<string, ProductType> = {
    // Simple product
    SIMPLE: {
        id: 'simple',
        name: 'Simple Product',
        isSubscription: false,
        sampleProductId: 63,
        sampleProductSlug: 'simple',
        price: 19.99
    },

    // Variable product
    VARIABLE: {
        id: 'variable',
        name: 'Variable Product',
        isSubscription: false,
        sampleProductId: 456,
        sampleProductSlug: 'sample-variable-product',
        price: 29.99
    },

    // WooCommerce Subscription
    WC_SUBSCRIPTION: {
        id: 'subscription',
        name: 'WooCommerce Subscription',
        isSubscription: true,
        subscriptionPlugin: 'woocommerce',
        sampleProductId: 789,
        sampleProductSlug: 'sample-wc-subscription',
        price: 9.99
    },

    // YITH Subscription
    YITH_SUBSCRIPTION: {
        id: 'yith_subscription',
        name: 'YITH Subscription',
        isSubscription: true,
        subscriptionPlugin: 'yith',
        sampleProductId: 1011,
        sampleProductSlug: 'sample-yith-subscription',
        price: 14.99
    }
};