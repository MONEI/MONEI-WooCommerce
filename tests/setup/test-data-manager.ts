import { PRODUCT_TYPES, ProductType } from '../fixtures/product-types';
import { WordPressApiClient } from './wordpress-api-client';

export class TestDataManager {
    constructor(private apiClient: WordPressApiClient) {}

    async setupTestProducts(productKeys?: string[]) {
        // Use all products if no specific keys provided
        const keysToSetup = productKeys || Object.keys(PRODUCT_TYPES);

        console.log(`Setting up ${keysToSetup.length} test products...`);

        for (const key of keysToSetup) {
            const productType = PRODUCT_TYPES[key];
            if (!productType) {
                console.warn(`Product type ${key} not found in PRODUCT_TYPES`);
                continue;
            }

            await this.ensureProductExists(productType);
        }
    }

    async ensureProductExists(productType: ProductType) {
        const existingProduct = await this.apiClient.getProductBySku(productType.sku);

        if (!existingProduct) {
            console.log(`Creating product: ${productType.name} (${productType.sku})`);
            const productData = this.convertToWooCommerceProduct(productType);
            console.log('Product data:', productData);
            await this.apiClient.createProduct(productData);
        } else {
            console.log(`Product exists: ${productType.name} (${productType.sku})`);
            // Optionally update if needed
            const productData = this.convertToWooCommerceProduct(productType);
            //await this.apiClient.updateProduct(existingProduct.id, productData);
        }
    }
    private convertToWooCommerceProduct(productType: ProductType) {
        const baseProduct = {
            name: productType.name,
            sku: productType.sku,
            regular_price: productType.price.toString(),
            type: productType.woocommerce?.type || 'simple',
            status: productType.woocommerce?.status || 'publish',
            catalog_visibility: productType.woocommerce?.catalog_visibility || 'visible',
            description: productType.woocommerce?.description || `Test product: ${productType.name}`,
            short_description: productType.woocommerce?.short_description || `Test product for automated testing`,
        };

        // Add subscription-specific data
        if (productType.isSubscription && productType.woocommerce?.meta_data) {
            return {
                ...baseProduct,
                meta_data: productType.woocommerce.meta_data
            };
        }

        // Add variable product data
        if (productType.woocommerce?.type === 'variable' && productType.woocommerce.attributes) {
            return {
                ...baseProduct,
                attributes: productType.woocommerce.attributes
            };
        }

        return baseProduct;
    }
    async setupProductsByType(type: 'simple' | 'variable' | 'subscription') {
        const productKeys = Object.keys(PRODUCT_TYPES).filter(key => {
            const product = PRODUCT_TYPES[key];
            if (type === 'subscription') return product.isSubscription;
            if (type === 'variable') return product.woocommerce?.type === 'variable';
            if (type === 'simple') return product.woocommerce?.type === 'simple' && !product.isSubscription;
            return false;
        });

        await this.setupTestProducts(productKeys);
    }

    // Method to setup subscription products for specific plugin
    async setupSubscriptionProducts(plugin?: 'woocommerce' | 'yith') {
        const productKeys = Object.keys(PRODUCT_TYPES).filter(key => {
            const product = PRODUCT_TYPES[key];
            return product.isSubscription && (!plugin || product.subscriptionPlugin === plugin);
        });

        await this.setupTestProducts(productKeys);
    }

    async setupTestPages() {
        const requiredPages = [
            {
                slug: 'checkout-classic',
                title: 'Classic Checkout',
                content: '[woocommerce_checkout]',
                template: 'page'
            },
            {
                slug: 'checkout-block',
                title: 'Block Checkout',
                content: '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout"></div><!-- /wp:woocommerce/checkout -->',
                template: 'page'
            }
        ];

        for (const pageData of requiredPages) {
            await this.ensurePageExists(pageData);
        }
    }

    async ensurePageExists(pageData: any) {
        const existingPage = await this.apiClient.getPageBySlug(pageData.slug);

        if (!existingPage) {
            console.log(`Creating page: ${pageData.title}`);
            await this.apiClient.createPage(pageData);
        } else {
            console.log(`Page exists: ${pageData.title}`);
        }
    }

    async setupMoneiPaymentMethods() {
        // Ensure MONEI settings are configured
        await this.apiClient.updateOption('woocommerce_monei_settings', {
            enabled: 'yes',
            apikey: process.env.MONEI_TEST_API_KEY,
            accountid: process.env.MONEI_TEST_ACCOUNT_ID,
            testmode: 'yes'
        });

        // Enable specific payment methods
        const paymentMethods = ['monei_bizum', 'monei_paypal', 'monei_multibanco'];
        for (const method of paymentMethods) {
            await this.apiClient.updateOption(`woocommerce_${method}_settings`, {
                enabled: 'yes'
            });
        }
    }
}