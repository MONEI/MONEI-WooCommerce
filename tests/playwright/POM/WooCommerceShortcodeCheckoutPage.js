const { expect } = require('@playwright/test');

class WooCommerceShortcodeCheckoutPage {
  /**
   * @param {import('@playwright/test').Page} page
   */
  constructor(page) {
    this.page = page;
    this.firstNameInput = page.locator('input[name="billing_first_name"]');
    this.lastNameInput = page.locator('input[name="billing_last_name"]');
    this.addressInput = page.locator('input[name="billing_address_1"]');
    this.cityInput = page.locator('input[name="billing_city"]');
    this.postcodeInput = page.locator('input[name="billing_postcode"]');
    this.phoneInput = page.locator('input[name="billing_phone"]');
    this.emailInput = page.locator('input[name="billing_email"]');
    this.paymentMethodSelector = page.locator('input[name="payment_method"]');
    this.checkoutButton = page.locator('button[name="woocommerce_checkout_place_order"]');
  }

  async goto() {
    const baseUrl = process.env.BASE_URL || 'https://monei.ddev.site';
    await this.page.goto(`${baseUrl}/woocommerce-shortcode-checkout`);
  }

  async fillCheckoutForm(details) {
    await this.firstNameInput.fill(details.firstName);
    await this.lastNameInput.fill(details.lastName);
    await this.addressInput.fill(details.address);
    await this.cityInput.fill(details.city);
    await this.postcodeInput.fill(details.postcode);
    await this.phoneInput.fill(details.phone);
    await this.emailInput.fill(details.email);
  }

  async selectPaymentMethod(method) {
    await this.paymentMethodSelector.check({ hasText: method });
  }

  async placeOrder() {
    await this.checkoutButton.click();
  }
}

module.exports = { WooCommerceShortcodeCheckoutPage };