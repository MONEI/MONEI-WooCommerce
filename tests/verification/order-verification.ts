import { Page, expect } from '@playwright/test';

export class OrderVerification {
    private readonly successSelector = '.wc-block-order-confirmation-status';
    private readonly failureSelector = '.woocommerce-error';
    private readonly orderNumberSelector = '.woocommerce-order-overview__order strong';
    private readonly orderItemsSelector = '.woocommerce-table--order-details tbody tr';

    constructor(private page: Page) {}

    async verifySuccessfulOrder() {
        await this.page.waitForSelector(this.successSelector, { timeout: 30000 });

        const isSuccessVisible = await this.page.isVisible(this.successSelector);
        expect(isSuccessVisible).toBeTruthy();

        const orderNumber = await this.getOrderNumber();
        expect(orderNumber).toBeTruthy();
    }

    async verifyFailedPayment() {
        await this.page.waitForSelector(this.failureSelector, { timeout: 30000 });

        const isErrorVisible = await this.page.isVisible(this.failureSelector);
        expect(isErrorVisible).toBeTruthy();

        const errorMessage = await this.page.textContent(this.failureSelector);
        expect(errorMessage).toBeTruthy();
    }

    private async getOrderNumber(): Promise<string> {
        // Try the block checkout format first
        const blockOrderNumberElement = await this.page.$('.wc-block-order-confirmation-summary-list-item:has-text("Order #") .wc-block-order-confirmation-summary-list-item__value');

        if (blockOrderNumberElement) {
            return await blockOrderNumberElement.textContent() || '';
        }

        // Fall back to the classic checkout format
        return await this.page.textContent(this.orderNumberSelector) || '';
    }

    private async getOrderItems(): Promise<Array<{ name: string; total: string }>> {
        const items = await this.page.$$(this.orderItemsSelector);
        const orderItems = [];

        for (const item of items) {
            const name = await item.$eval('.product-name', el => el.textContent?.trim() || '');
            const total = await item.$eval('.product-total', el => el.textContent?.trim() || '');
            orderItems.push({ name, total });
        }

        return orderItems;
    }
}