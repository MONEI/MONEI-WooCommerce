import { Page, expect } from '@playwright/test';
import { ProductType } from '../fixtures/product-types';

export class CartPage {
    readonly page: Page;

    // Locators
    readonly proceedToCheckoutButton = '.checkout-button';
    readonly cartItems = '.cart_item';
    readonly cartTotal = '.order-total .amount';
    readonly updateCartButton = '[name="update_cart"]';
    readonly removeItemButton = '.remove';

    constructor(page: Page) {
        this.page = page;
    }
    async addProductToCart(productType: ProductType): Promise<void> {
        const { page } = this;

        console.log(`Adding product to cart: ${productType.name} (ID: ${productType.sampleProductId})`);

        if (productType.sampleProductSlug) {
            await page.goto(`/product/${productType.sampleProductSlug}/`);
        } else {
            await page.goto('/shop/');
            await page.click(`[data-product_id="${productType.sampleProductId}"]`);
        }

        await page.waitForSelector('.single-product', { timeout: 10000 });

        if (productType.id === 'variable') {
            const variationSelects = page.locator('select[name^="attribute_"]');
            const count = await variationSelects.count();

            for (let i = 0; i < count; i++) {
                const select = variationSelects.nth(i);
                const firstOption = select.locator('option').nth(1); // Skip the "Choose an option" option
                await firstOption.click();
            }

            await page.waitForTimeout(1000);
        }

        const addToCartButton = page.locator('.single_add_to_cart_button:not(.disabled)');
        await addToCartButton.waitFor({ state: 'visible', timeout: 10000 });
        await addToCartButton.click();

        console.log(`Product ${productType.name} successfully added to cart`);
    }

    /**
     * Method 2: Add product to cart and go directly to checkout using UI navigation
     */
    async addProductToCartAndCheckout(productType: ProductType): Promise<void> {
        const { page } = this;

        await this.addProductToCart(productType);
        await page.goto('/checkout/');
        await page.waitForSelector('.woocommerce-checkout', { timeout: 10000 });

        console.log(`Product ${productType.name} added to cart, now on checkout page`);
    }

    /**
     * Method 3: Add product to cart using WooCommerce Store API
     */
    async addProductToCartViaAPI(productType: ProductType): Promise<void> {
        const { page } = this;

        console.log(`Adding product to cart via API: ${productType.name} (ID: ${productType.sampleProductId})`);

        await page.goto('/');
        const nonce = await page.evaluate(() => {
            return (window as any).wc_add_to_cart_params?.wc_ajax_url?.match(/wc-ajax=([^&]+)/)?.[1] ||
                (window as any).wp?.wpApiSettings?.nonce ||
                document.querySelector('meta[name="wc-store-api-nonce"]')?.getAttribute('content') ||
                '';
        });

        if (!nonce) {
            console.log('Could not get nonce, falling back to UI method');
            return await this.addProductToCart(productType);
        }

        // Make API request to add product to cart
        const response = await page.request.post('/wp-json/wc/store/v1/cart/add-item', {
            headers: {
                'X-WC-Store-API-Nonce': nonce,
                'Content-Type': 'application/json',
            },
            data: {
                id: productType.sampleProductId,
                quantity: 1
            }
        });

        if (response.ok()) {
            const result = await response.json();
            console.log(`Product ${productType.name} successfully added to cart via API`);
            console.log('Cart response:', result);
        } else {
            console.log(`API method failed (${response.status()}), falling back to UI method`);
            await this.addProductToCart(productType);
        }
    }

    /**
     * Helper method to wait for cart updates
     */
    private async waitForCartUpdate(): Promise<void> {
        const { page } = this;

        try {
            await Promise.race([
                page.waitForSelector('.woocommerce-message', { timeout: 5000 }),
                page.waitForSelector('.cart-count', { timeout: 5000 }),
                page.waitForTimeout(3000) // Fallback timeout
            ]);

            // Additional wait for AJAX to complete
            await page.waitForTimeout(1000);

        } catch (error) {
            console.log('Cart update wait completed');
        }
    }

    /**
     * Helper method to verify product was added to cart
     */
    async verifyProductInCart(productType: ProductType): Promise<boolean> {
        const { page } = this;

        try {
            await page.goto('/cart/');

            const productInCart = await page.locator(`[data-product_id="${productType.sampleProductId}"], .cart_item`).count();

            return productInCart > 0;
        } catch (error) {
            console.error('Error verifying product in cart:', error);
            return false;
        }
    }
}