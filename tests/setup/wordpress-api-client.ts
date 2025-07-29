// tests/setup/wordpress-api-client.ts
const WooCommerceRestApi = require("@woocommerce/woocommerce-rest-api").default;

interface WooCommerceError {
    code: string;
    message: string;
    data: {
        status: number;
    };
}

interface WordPressError {
    code: string;
    message: string;
    data?: any;
}

export class WordPressApiClient {
    private baseUrl: string;
    private wpAuth: string;
    wooCommerce: any;

    constructor() {
        this.baseUrl = process.env.TESTSITE_URL || 'http://localhost:8080';
        this.initializeAuth();
        this.initializeWooCommerce();
        this.logInitialization();
    }

    private initializeAuth(): void {
        const wpUser = process.env.WORDPRESS_ADMIN_USER;
        const wpPassword = process.env.WP_API_APP_PASSWORD || process.env.WORDPRESS_ADMIN_PASSWORD;

        if (!wpUser || !wpPassword) {
            throw new Error('WordPress credentials are required. Please set WORDPRESS_ADMIN_USER and WP_API_APP_PASSWORD (or WORDPRESS_ADMIN_PASSWORD) environment variables.');
        }

        this.wpAuth = Buffer.from(`${wpUser}:${wpPassword}`).toString('base64');
    }

    private initializeWooCommerce(): void {
        const consumerKey = process.env.WC_CONSUMER_KEY;
        const consumerSecret = process.env.WC_CONSUMER_SECRET;

        if (!consumerKey || !consumerSecret) {
            throw new Error('WooCommerce Consumer Key and Secret are required. Please set WC_CONSUMER_KEY and WC_CONSUMER_SECRET environment variables.');
        }

        this.wooCommerce = new WooCommerceRestApi({
            url: this.baseUrl,
            consumerKey: consumerKey,
            consumerSecret: consumerSecret,
            version: 'wc/v3',
            queryStringAuth: true // Force Basic Authentication as query string for compatibility
        });
    }

    private logInitialization(): void {
        console.log('🔧 API Client initialized with:');
        console.log('  📍 Base URL:', this.baseUrl);
        console.log('  👤 WP User:', process.env.WORDPRESS_ADMIN_USER);
        console.log('  🔐 WP Auth configured:', !!this.wpAuth);
        console.log('  🛒 WooCommerce configured:', !!this.wooCommerce);
        console.log('  🔑 Using', process.env.WP_API_APP_PASSWORD ? 'application password' : 'regular password', 'authentication');
    }

    private logApiCall(method: string, endpoint: string, identifier?: string): void {
        const id = identifier ? ` (${identifier})` : '';
        console.log(`🌐 ${method.toUpperCase()} ${endpoint}${id}`);
    }

    private handleWooCommerceError(error: any, operation: string): never {
        console.error(`❌ WooCommerce API error during ${operation}:`, {
            message: error.message,
            code: error.code,
            data: error.data,
            status: error.response?.status,
            statusText: error.response?.statusText
        });

        if (error.code === 'ECONNREFUSED') {
            throw new Error(`Cannot connect to WordPress site at ${this.baseUrl}. Is the site running?`);
        }

        if (error.code === 'CERT_HAS_EXPIRED' || error.code === 'UNABLE_TO_VERIFY_LEAF_SIGNATURE') {
            throw new Error(`SSL certificate error for ${this.baseUrl}. Try using HTTP instead of HTTPS for local development.`);
        }

        // Handle WooCommerce specific errors
        if (error.response?.data?.code) {
            const wooError: WooCommerceError = error.response.data;
            throw new Error(`WooCommerce API error [${wooError.code}]: ${wooError.message} (Status: ${wooError.data.status})`);
        }

        throw new Error(`${operation} failed: ${error.message}`);
    }

    private handleWordPressError(error: any, operation: string): never {
        console.error(`❌ WordPress API error during ${operation}:`, {
            message: error.message,
            status: error.status,
            statusText: error.statusText
        });

        if (error.status === 401) {
            throw new Error(`WordPress authentication failed. Check your credentials.`);
        }

        if (error.status === 403) {
            throw new Error(`WordPress permission denied. User may not have sufficient privileges.`);
        }

        throw new Error(`${operation} failed: ${error.message || `HTTP ${error.status}`}`);
    }

    async healthCheck(): Promise<boolean> {
        console.log('🏥 Performing health checks...');

        try {
            // Check WooCommerce API
            this.logApiCall('GET', '/wp-json/wc/v3/', 'health check');
            await this.wooCommerce.get('');
            console.log('  ✅ WooCommerce API: Accessible');

            // Check WordPress API
            const wpResponse = await fetch(`${this.baseUrl}/wp-json/wp/v2/`, {
                headers: {
                    'Authorization': `Basic ${this.wpAuth}`,
                    'Content-Type': 'application/json'
                },
                signal: AbortSignal.timeout(5000)
            });

            if (wpResponse.ok) {
                console.log('  ✅ WordPress API: Accessible');
            } else {
                throw new Error(`WordPress API returned ${wpResponse.status}`);
            }

            console.log('🎉 All health checks passed!');
            return true;

        } catch (error) {
            console.error('💥 Health check failed');
            this.handleWooCommerceError(error, 'health check');
        }
    }

    async getProductBySku(sku: string): Promise<any | null> {
        this.logApiCall('GET', '/wp-json/wc/v3/products', `SKU: ${sku}`);

        try {
            const response = await this.wooCommerce.get('products', { sku });
            const products = response.data;

            console.log(`  📦 Products found: ${products.length}`);

            if (products.length > 0) {
                console.log(`  ✅ Product found: ${products[0].name} (ID: ${products[0].id})`);
                return products[0];
            } else {
                console.log(`  ℹ️  No product found with SKU: ${sku}`);
                return null;
            }

        } catch (error) {
            this.handleWooCommerceError(error, `fetching product by SKU: ${sku}`);
        }
    }

    async createProduct(productData: any): Promise<any> {
        this.logApiCall('POST', '/wp-json/wc/v3/products', `Creating: ${productData.name}`);

        try {
            const response = await this.wooCommerce.post('products', productData);
            const product = response.data;

            console.log(`  ✅ Product created: ${product.name} (ID: ${product.id}, SKU: ${product.sku})`);
            return product;

        } catch (error) {
            this.handleWooCommerceError(error, `creating product: ${productData.name}`);
        }
    }

    async updateProduct(productId: number, productData: any): Promise<any> {
        this.logApiCall('PUT', `/wp-json/wc/v3/products/${productId}`, `Updating: ${productData.name || productId}`);

        try {
            const response = await this.wooCommerce.put(`products/${productId}`, productData);
            const product = response.data;

            console.log(`  ✅ Product updated: ${product.name} (ID: ${product.id})`);
            return product;

        } catch (error) {
            this.handleWooCommerceError(error, `updating product ID: ${productId}`);
        }
    }

    async getPageBySlug(slug: string): Promise<any | null> {
        this.logApiCall('GET', '/wp-json/wp/v2/pages', `Slug: ${slug}`);

        try {
            const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/pages?slug=${slug}`, {
                headers: {
                    'Authorization': `Basic ${this.wpAuth}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const pages = await response.json();

            console.log(`  📄 Pages found: ${pages.length}`);

            if (pages.length > 0) {
                console.log(`  ✅ Page found: ${pages[0].title.rendered} (ID: ${pages[0].id})`);
                return pages[0];
            } else {
                console.log(`  ℹ️  No page found with slug: ${slug}`);
                return null;
            }

        } catch (error) {
            this.handleWordPressError(error, `fetching page by slug: ${slug}`);
        }
    }

    async createPage(pageData: any): Promise<any> {
        this.logApiCall('POST', '/wp-json/wp/v2/pages', `Creating: ${pageData.title}`);

        try {
            const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/pages`, {
                method: 'POST',
                headers: {
                    'Authorization': `Basic ${this.wpAuth}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    title: pageData.title,
                    content: pageData.content,
                    slug: pageData.slug,
                    status: 'publish'
                })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const page = await response.json();
            console.log(`  ✅ Page created: ${page.title.rendered} (ID: ${page.id}, Slug: ${page.slug})`);
            return page;

        } catch (error) {
            this.handleWordPressError(error, `creating page: ${pageData.title}`);
        }
    }

    async updateOption(optionName: string, optionValue: any): Promise<any> {
        this.logApiCall('POST', '/wp-json/wp/v2/settings', `Option: ${optionName}`);

        try {
            const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/settings`, {
                method: 'POST',
                headers: {
                    'Authorization': `Basic ${this.wpAuth}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ [optionName]: optionValue })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const result = await response.json();
            console.log(`  ✅ Option updated: ${optionName}`);
            return result;

        } catch (error) {
            console.warn(`  ⚠️  Could not update option ${optionName} via REST API:`, error.message);
            console.log(`  ℹ️  This might be normal if the option is not exposed via REST API`);

            // Return a success-like response to maintain compatibility
            return { [optionName]: optionValue };
        }
    }

    async updateGatewaySettings(gatewayId, settings) {
        try {
            const response = await this.wooCommerce.put(`payment_gateways/${gatewayId}`, settings);
            return response.data;
        } catch (error) {
            this.handleWooCommerceError(error, 'Error updating gateway');
        }
    }

    // Additional WooCommerce helper methods
    async getTaxRates(): Promise<any[]> {
        this.logApiCall('GET', '/wp-json/wc/v3/taxes', 'All tax rates');

        try {
            const response = await this.wooCommerce.get('taxes');
            const taxes = response.data;
            console.log(`  💰 Tax rates found: ${taxes.length}`);
            return taxes;

        } catch (error) {
            this.handleWooCommerceError(error, 'fetching tax rates');
        }
    }

    async createTaxRate(taxData: any): Promise<any> {
        this.logApiCall('POST', '/wp-json/wc/v3/taxes', `Creating tax: ${taxData.name}`);

        try {
            const response = await this.wooCommerce.post('taxes', taxData);
            const tax = response.data;
            console.log(`  ✅ Tax rate created: ${tax.name} (${tax.rate}%)`);
            return tax;

        } catch (error) {
            this.handleWooCommerceError(error, `creating tax rate: ${taxData.name}`);
        }
    }

    async getShippingZones(): Promise<any[]> {
        this.logApiCall('GET', '/wp-json/wc/v3/shipping/zones', 'All shipping zones');

        try {
            const response = await this.wooCommerce.get('shipping/zones');
            const zones = response.data;
            console.log(`  🚚 Shipping zones found: ${zones.length}`);
            return zones;

        } catch (error) {
            this.handleWooCommerceError(error, 'fetching shipping zones');
        }
    }

    async createShippingMethod(zoneId: number, methodData: any): Promise<any> {
        this.logApiCall('POST', `/wp-json/wc/v3/shipping/zones/${zoneId}/methods`, `Creating shipping method`);

        try {
            const response = await this.wooCommerce.post(`shipping/zones/${zoneId}/methods`, methodData);
            const method = response.data;
            console.log(`  ✅ Shipping method created: ${method.method_title} (ID: ${method.id})`);
            return method;

        } catch (error) {
            this.handleWooCommerceError(error, `creating shipping method for zone ${zoneId}`);
        }
    }
}