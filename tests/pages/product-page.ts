import { Page } from '@playwright/test';
import { ProductType } from '../fixtures/product-types';

export class ProductPage {
    readonly page: Page;

    // Locators
    // Locators
    readonly addToCartButton = 'button.single_add_to_cart_button';
    readonly variationDropdown = 'select[data-attribute_name]';
    readonly subscriptionOptionsContainer = '.wcsatt-options-wrapper';
    readonly viewCartLink = 'a.added_to_cart';


    constructor(page: Page) {
        this.page = page;
    }

    async navigateToProduct(productType: ProductType) {
        await this.page.goto(`/product/${productType.sampleProductSlug}`);
        await this.page.waitForSelector('h1.product_title');
    }

    async addSimpleProductToCart() {
        await this.page.click(this.addToCartButton);
        await this.page.waitForSelector(this.viewCartLink);
    }

    async addVariableProductToCart(variations: Record<string, string>) {
        for (const [attribute, value] of Object.entries(variations)) {
            await this.page.selectOption(`select[data-attribute_name="attribute_${attribute}"]`, value);
        }
        await this.page.click(this.addToCartButton);
        await this.page.waitForSelector(this.viewCartLink);
    }

    async addSubscriptionToCart(subscriptionOptions?: Record<string, string>) {
        if (subscriptionOptions) {
            for (const [option, value] of Object.entries(subscriptionOptions)) {
                await this.page.click(`${this.subscriptionOptionsContainer} input[value="${value}"]`);
            }
        }
        await this.page.click(this.addToCartButton);
        await this.page.waitForSelector(this.viewCartLink);
    }

    async goToCart() {
        await this.page.click(this.viewCartLink);
        await this.page.waitForSelector('.woocommerce-cart-form');
    }
}