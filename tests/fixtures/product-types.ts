// tests/fixtures/product-types.ts
export interface ProductType {
    id: string;
    sku: string;
    name: string;
    isSubscription: boolean;
    subscriptionPlugin?: string; // 'woocommerce' or 'yith'
    sampleProductId?: number; // ID of a sample product of this type for testing
    sampleProductSlug?: string; // Slug of a sample product
    price: number; // Price to expect
    // Add WooCommerce-specific fields
    woocommerce?: {
        type: 'simple' | 'variable' | 'subscription' | 'grouped' | 'external';
        status?: 'draft' | 'pending' | 'private' | 'publish';
        virtual?: boolean;
        downloadable?: boolean;
        catalog_visibility?: 'visible' | 'catalog' | 'search' | 'hidden';
        description?: string;
        short_description?: string;
        categories?: Array<{ id: number; name: string; }>;
        images?: Array<{ src: string; alt: string; }>;
        attributes?: any[];
        variations?: any[];
        meta_data?: Array<{ key: string; value: any; }>;
    };
}

/**
 * Available product types to test with
 */
export const PRODUCT_TYPES: Record<string, ProductType> = {
    // Simple product
    SIMPLE: {
        id: 'simple',
        sku: 'TEST-SIMPLE-01',
        name: 'Simple Product',
        isSubscription: false,
        sampleProductId: 63,
        sampleProductSlug: 'simple',
        price: 19.99,
        woocommerce: {
            type: 'simple',
            status: 'publish',
            catalog_visibility: 'visible',
            description: 'A simple test product for automated testing',
            short_description: 'Simple test product'
        }
    },
    BIZUM_SUCCESS: {
        id: 'bizum-success-simple-product',
        sku: 'TEST-BIZUM-S',
        name: 'Bizum Success Simple Product',
        isSubscription: false,
        sampleProductId: 64,
        sampleProductSlug: 'bizum-success-simple-product',
        price: 1.00,
        woocommerce: {
            type: 'simple',
            status: 'publish',
            virtual: true,
            downloadable: true,
            catalog_visibility: 'visible',
            description: 'A simple test product for automated testing',
            short_description: 'Simple test product'
        }
    },
    BIZUM_FAILS: {
        id: 'bizum-fails-simple-product',
        sku: 'TEST-BIZUM-02',
        name: 'Bizum Fails Simple Product',
        isSubscription: false,
        sampleProductId: 65,
        sampleProductSlug: 'bizum-fails-simple-product',
        price: 10.00,
        woocommerce: {
            type: 'simple',
            status: 'publish',
            catalog_visibility: 'visible',
            description: 'A simple test product for automated testing',
            short_description: 'Simple test product'
        }
    },

    // Variable product
    VARIABLE: {
        id: 'variable',
        sku: 'TEST-VARIABLE-01',
        name: 'Variable Product',
        isSubscription: false,
        sampleProductId: 66,
        sampleProductSlug: 'variable',
        price: 29.99,
        woocommerce: {
            type: 'variable',
            status: 'publish',
            catalog_visibility: 'visible',
            description: 'A variable test product for automated testing',
            short_description: 'Variable test product',
            attributes: [
                {
                    id: 1,
                    name: 'Size',
                    options: ['Small', 'Medium', 'Large'],
                    visible: true,
                    variation: true
                }
            ]
        }
    }
};

// Helper to get specific product types
export const getProductsByType = (type: 'simple' | 'variable' | 'subscription') => {
    return Object.values(PRODUCT_TYPES).filter(product => {
        if (type === 'subscription') return product.isSubscription;
        if (type === 'variable') return product.woocommerce?.type === 'variable';
        if (type === 'simple') return product.woocommerce?.type === 'simple' && !product.isSubscription;
        return false;
    });
};

// Helper to get products by subscription plugin
export const getSubscriptionProducts = (plugin?: 'woocommerce' | 'yith') => {
    return Object.values(PRODUCT_TYPES).filter(product =>
        product.isSubscription && (!plugin || product.subscriptionPlugin === plugin)
    );
};