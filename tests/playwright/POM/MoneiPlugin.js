const { expect } = require('@playwright/test');
const { WooCommercePlugin } = require('./WooCommercePlugin');

class MoneiPlugin {
  /**
   * @param {import('@playwright/test').Page} page
   */
  constructor(page) {
    this.page = page;
    const baseUrl = process.env.BASE_URL || 'https://monei.ddev.site';
    const wooCommercePlugin = new WooCommercePlugin(page);
    this.orderSuccessPage = wooCommercePlugin.successPage;
    this.orderFailurePage = wooCommercePlugin.paymentPage;
    this.orderPendingPage = wooCommercePlugin.paymentPage;
    this.orderStatus = wooCommercePlugin.orderStatusLocator;
    this.orderNotice = wooCommercePlugin.orderNoticeLocator;
    this.pluginSettingsPage = `${baseUrl}/wp-admin/admin.php?page=monei-plugin-settings`;
  }
  get paymentMethods() {
    return ['credit card', 'bizum', 'cofidis', 'paypal'];
  }

  async verifyOrderSuccess($orderId) {
    await expect(this.orderSuccessPage).toBeVisible();
    await WooCommercePlugin.gotoOrderPage($orderId);
    await expect(this.orderStatus).toHaveText(wooCommercePlugin.orderStatuses.success);
    await expect(this.orderNotice).toHaveText('Thank you. Your order has been received.');
  }

  async verifyOrderFailure($orderId) {
    await expect(this.orderFailurePage).toBeVisible();
    await WooCommercePlugin.gotoOrderPage($orderId);
    await expect(this.orderStatus).toHaveText(wooCommercePlugin.orderStatuses.failed);
    await expect(this.orderNotice).toHaveText('Unfortunately your order cannot be processed.');
  }

  async verifyOrderPending($orderId) {
    await expect(this.orderPendingPage).toBeVisible();
    await WooCommercePlugin.gotoOrderPage($orderId);
    await expect(this.orderStatus).toHaveText(wooCommercePlugin.orderStatuses.pending);
    await expect(this.orderNotice).toHaveText('Your order is pending.');
  }

  async onboardPlugin() {
    await this.page.goto(this.pluginSettingsPage);
    await this.enterTestApiKey();
  }

  async processPayment($paymentMethod, $expectedState) {
    //handle the popup
    //enter the payment details, strategy based on the payment method
    //click the pay button
  }
}

module.exports = { MoneiPlugin };