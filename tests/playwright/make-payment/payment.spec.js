const { test } = require('../fixtures');
const { WooCommerceBlockCheckoutPage } = require('../POM/WooCommerceBlockCheckoutPage');
const { WooCommerceShortcodeCheckoutPage } = require('../POM/WooCommerceShortcodeCheckoutPage');
const { MoneiPlugin } = require('../POM/MoneiPlugin');
const { WooCommercePlugin } = require('../POM/WooCommercePlugin');
const { paymentMethods } = require('../POM/MoneiPlugin');

test.describe('Make Payment Transactions with Monei Plugin', () => {
  test.beforeEach(async ({ page }) => {
    const moneiPlugin = new MoneiPlugin(page);
    await moneiPlugin.onboardPlugin();
    await moneiPlugin.resetShop();
  });

  const checkoutPages = ['WooCommerce block checkout', 'WooCommerce shortcode checkout'];

  const statuses = Object.keys(new WooCommercePlugin().orderStatuses);

  const scenarios = [];

  for (const paymentMethod of paymentMethods) {
    for (const checkoutPage of checkoutPages) {
      for (const status of statuses) {
        scenarios.push({
          paymentMethod,
          checkoutPage,
          expectedState: WooCommercePlugin.orderStatuses[status],
        });
      }
    }
  }

  for (const scenario of scenarios) {
    test(`Transaction with ${scenario.paymentMethod} on ${scenario.checkoutPage} should be ${scenario.expectedState}`, async ({ page }) => {
      // Background: Given the shop is ready to checkout
      await addToCart(1, 1);
      const moneiPlugin = new MoneiPlugin(page);

      // Given I visit the WooCommerce block/shortcode checkout
      // When I select payment method
      // And I complete the payment in the popup with the magic number
      let checkoutPage;
      if (scenario.checkoutPage === 'WooCommerce block checkout') {
        checkoutPage = new WooCommerceBlockCheckoutPage(page);
      } else {
        checkoutPage = new WooCommerceShortcodeCheckoutPage(page);
      }

      await checkoutPage.goto();
      await checkoutPage.fillCheckoutForm(scenario.details);
      await checkoutPage.selectPaymentMethod(scenario.paymentMethod);
      await checkoutPage.placeOrder();
      await moneiPlugin.processPayment(scenario.paymentMethod, scenario.expectedState);

      // Then the transaction is expected state
      if (scenario.expectedState === WooCommercePlugin.orderStatuses.success) {
        await moneiPlugin.verifyOrderSuccess($orderId);
      } else if (scenario.expectedState === WooCommercePlugin.orderStatuses.failed) {
        await moneiPlugin.verifyOrderFailure($orderId);
      } else if (scenario.expectedState === WooCommercePlugin.orderStatuses.pending) {
        await moneiPlugin.verifyOrderPending($orderId);
      }
    });
  }
});
