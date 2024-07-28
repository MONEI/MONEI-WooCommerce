const { expect } = require('@playwright/test');

class WooCommercePlugin {
  /**
   * @param {import('@playwright/test').Page} page
   */
  constructor(page) {
    this.page = page;
    const baseUrl = process.env.BASE_URL || 'https://monei.ddev.site';
    this.checkoutPages = {
      block: `${baseUrl}/woocommerce-block-checkout`,
      shortcode: `${baseUrl}/woocommerce-shortcode-checkout`
    };
    this.orderStatuses = {
      success: 'processing',
      failed: 'pending',
      pending: 'on-hold'
    };
    this.paymentPage = `${baseUrl}/payment`;
    this.successPage = `${baseUrl}/order-received`;
    this.orderStatusLocator = page.locator('.order-status');
    this.orderNoticeLocator = page.locator('.woocommerce-notice');
    this.ordersPage = `${baseUrl}/my-account/orders`;
  }

  async gotoOrdersPage() {
    await this.page.goto(this.ordersPage);
  }

  async gotoOrder(orderId) {
    await this.page.goto(`${this.ordersPage}/${orderId}`);
  }

}

module.exports = { WooCommercePlugin };