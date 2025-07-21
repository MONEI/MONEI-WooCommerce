import { Page } from '@playwright/test';

export class OrderReceivedPage {
    readonly page: Page;

    // Locators
    readonly orderNumber = '.woocommerce-order-overview__order strong';
    readonly orderDate = '.woocommerce-order-overview__date strong';
    readonly orderTotal = '.woocommerce-order-overview__total strong';
    readonly paymentMethod = '.woocommerce-order-overview__payment-method strong';
    readonly orderDetails = '.woocommerce-order-details';
    readonly customerDetails = '.woocommerce-customer-details';
    readonly thankYouMessage = '.woocommerce-thankyou-order-received';

    constructor(page: Page) {
        this.page = page;
    }

    async waitForPage() {
        await this.page.waitForSelector(this.thankYouMessage);
    }

    async getOrderNumber(): Promise<string> {
        return await this.page.textContent(this.orderNumber) || '';
    }

    async getOrderDate(): Promise<string> {
        return await this.page.textContent(this.orderDate) || '';
    }

    async getOrderTotal(): Promise<string> {
        return await this.page.textContent(this.orderTotal) || '';
    }

    async getPaymentMethod(): Promise<string> {
        return await this.page.textContent(this.paymentMethod) || '';
    }

    async isOrderDetailsVisible(): Promise<boolean> {
        return await this.page.isVisible(this.orderDetails);
    }

    async isCustomerDetailsVisible(): Promise<boolean> {
        return await this.page.isVisible(this.customerDetails);
    }

    async getThankYouMessage(): Promise<string> {
        return await this.page.textContent(this.thankYouMessage) || '';
    }

    async getOrderItems(): Promise<Array<{ name: string; total: string }>> {
        const items = await this.page.$$('.woocommerce-table--order-details tbody tr');
        const orderItems = [];

        for (const item of items) {
            const name = await item.$eval('.product-name', el => el.textContent?.trim() || '');
            const total = await item.$eval('.product-total', el => el.textContent?.trim() || '');
            orderItems.push({ name, total });
        }

        return orderItems;
    }

    async getBillingAddress(): Promise<string> {
        const billingAddress = await this.page.$('.woocommerce-customer-details .woocommerce-column--billing-address address');
        return billingAddress ? await billingAddress.textContent() || '' : '';
    }

    async getShippingAddress(): Promise<string> {
        const shippingAddress = await this.page.$('.woocommerce-customer-details .woocommerce-column--shipping-address address');
        return shippingAddress ? await shippingAddress.textContent() || '' : '';
    }

    async isPaymentSuccessful(): Promise<boolean> {
        return await this.page.isVisible('.woocommerce-notice--success');
    }

    async getTransactionId(): Promise<string | null> {
        const transactionIdElement = await this.page.$('.woocommerce-order-overview__transaction-id strong');
        return transactionIdElement ? await transactionIdElement.textContent() : null;
    }
}
