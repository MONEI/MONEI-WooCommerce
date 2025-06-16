// tests/setup/wordpress-api-client.ts
export class WordPressApiClient {
    private baseUrl: string;
    private auth: string;

    constructor() {
        this.baseUrl = process.env.TESTSITE_URL || 'http://localhost:8080';

        const consumerKey = process.env.WC_CONSUMER_KEY;
        const consumerSecret = process.env.WC_CONSUMER_SECRET;

        if (!consumerKey || !consumerSecret) {
            throw new Error('WooCommerce Consumer Key and Secret are required. Please set WC_CONSUMER_KEY and WC_CONSUMER_SECRET environment variables.');
        }

        this.auth = Buffer.from(`${process.env.WP_API_USER}:${process.env.WP_API_PASS}`).toString('base64');

        console.log('API Client initialized with:');
        console.log('Base URL:', this.baseUrl);
        console.log('Auth user:', process.env.WP_API_USER);
        console.log('Auth configured:', !!this.auth);
    }

    async healthCheck() {
        console.log('Performing WooCommerce API health check...');

        try {
            const response = await fetch(`${this.baseUrl}/wp-json/wc/v3/`, {
                headers: {
                    'Authorization': `Basic ${this.auth}`,
                    'Content-Type': 'application/json'
                },
                signal: AbortSignal.timeout(5000)
            });

            console.log('WooCommerce API status:', response.status);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error:', errorText);
                throw new Error(`WooCommerce API not accessible: ${response.status} - ${errorText}`);
            }

            return true;
        } catch (error) {
            console.error('Health check failed:', error.message);
            throw new Error(`WooCommerce API health check failed: ${error.message}`);
        }
    }

    async getProductBySku(sku: string) {
        console.log(`Fetching product by SKU: ${sku}`);
        const url = `${this.baseUrl}/wp-json/wc/v3/products?sku=${sku}&consumer_key=${process.env.WC_CONSUMER_KEY}&consumer_secret=${process.env.WC_CONSUMER_SECRET}`;
        const products = await this.fetchData(url);
        console.log('Products found:', products.length);
        return products.length > 0 ? products[0] : null;
    }

    private async fetchData(url: string, body = null) {
        try {
            const options: RequestInit = {
                method: body ? 'POST' : 'GET', // Use POST if body exists, otherwise GET
                headers: {
                    'Authorization': `Basic ${this.auth}`,
                    'Content-Type': 'application/json',
                    'User-Agent': 'Playwright-Tests/1.0'
                },
                signal: AbortSignal.timeout(10000) // 10 second timeout
            };
            if (body !== null) {
                options.body = body;
            }
            const response = await fetch(url, options);

            //console.log('Response status:', response.status);
            //console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error Response:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            return  await response.json();

        } catch (error) {
            console.error('Fetch error details:', {
                message: error.message,
                cause: error.cause,
                stack: error.stack
            });

            // Check if it's a network error
            if (error.code === 'ECONNREFUSED') {
                throw new Error(`Cannot connect to WordPress site at ${this.baseUrl}. Is the site running?`);
            }

            if (error.code === 'CERT_HAS_EXPIRED' || error.code === 'UNABLE_TO_VERIFY_LEAF_SIGNATURE') {
                throw new Error(`SSL certificate error for ${this.baseUrl}. Try using HTTP instead of HTTPS for local development.`);
            }

            throw error;
        }
    }

    async createProduct(productData: any) {
        const url = `${this.baseUrl}/wp-json/wc/v3/products?consumer_key=${process.env.WC_CONSUMER_KEY}&consumer_secret=${process.env.WC_CONSUMER_SECRET}`;
        const body = JSON.stringify(productData);
        return await this.fetchData(url, body);
    }

    async getPageBySlug(slug: string) {
        const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/pages?slug=${slug}`, {
            headers: { 'Authorization': `Basic ${this.auth}` }
        });
        const pages = await response.json();
        return pages.length > 0 ? pages[0] : null;
    }

    async createPage(pageData: any) {
        const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/pages`, {
            method: 'POST',
            headers: {
                'Authorization': `Basic ${this.auth}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: pageData.title,
                content: pageData.content,
                slug: pageData.slug,
                status: 'publish'
            })
        });
        return response.json();
    }

    async updateOption(optionName: string, optionValue: any) {
        // Use WordPress REST API or custom endpoint for options
        const response = await fetch(`${this.baseUrl}/wp-json/wp/v2/settings`, {
            method: 'POST',
            headers: {
                'Authorization': `Basic ${this.auth}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ [optionName]: optionValue })
        });
        return response.json();
    }
}