### WooCommerce
- **WooCommerce Block Checkout page**: Refers to the WooCommerce checkout block, which is the default checkout page.
- **WooCommerce Shortcode Checkout page**: Refers to the WooCommerce checkout shortcode, which is the checkout page that is created using a shortcode.
- **WooCommerce Payment Retry Checkout Page**: The page that is displayed when a payment fails during the normal checkout process and needs to be retried, or when a manual order is created and the payment is sent to the user.
- **Transaction**: The process of completing a purchase.
- **WooCommerce Cart Page**: The page where customers can view the products they have added to their cart and proceed to checkout.
- **WooCommerce Order Received Page**: The page that confirms the customer's order has been received and provides order details.
- **WooCommerce My Account Page**: The page where customers can view their account details, order history, and manage their account settings.
- **WooCommerce Payment Methods Page**: The page where customers can manage their saved payment methods and add new ones.
- **WooCommerce Shop Page**: The main page of the WooCommerce store where customers can browse and search for products.
- **WooCommerce Product Page**: The page that displays detailed information about a specific product, including price, description, and reviews.
- **WooCommerce Thank You Page**: The page that customers see after successfully completing a purchase, thanking them for their order.
- **User**: A person who uses the WooCommerce store to purchase products.
- **Merchant**: A person who owns the WooCommerce store and manages the products and orders.
- **Order**: A purchase made by a customer.
- **Refund**: The process of returning money to a customer for a purchase.
- **Subscription**: A recurring payment made by a customer for a product or service. Using WooCommerce Subscription plugin.

### Monei Plugin
- **Monei plugin**: The payment plugin named "Monei".
- **Monei test API keys**: The API keys for the Monei plugin.
- **Monei live API keys**: The API keys for the Monei plugin.
- **Monei test mode**: The mode of the Monei plugin.
- **Monei live mode**: The mode of the Monei plugin.
- **Monei dashboard**: The dashboard of the Monei plugin at https://dashboard.monei.com/.
- **Success magic number**: The number 1234567890123456.
- **Failure magic number**: The number 1234567890123457.
- **Pending magic number**: The number 1234567890123458.
- **Monei payment method settings fields**: The fields in the settings page of the Monei payment method: 
            - **Enable/Disable**
            - **Use Redirect Flow**
            - **Apple Pay / Google Pay**
            - **Test mode**
            - **Title**
            - **Description**
            - **Hide Logo**
            - **Account ID*** 
            - **API Key*** 
            - **Saved cards**
            - **Pre-Authorize**
            - **What to do after payment?**
            - **Debug Log**


### Test Actions
- **I visit**: Used for navigating to a page.
- **I should see**: Used for asserting that a certain element is visible on the page.
- **I should not see**: Used for asserting that a certain element is not visible on the page.
- **I select**: Used for selecting an option from a dropdown.
- **I fill in**: Used for filling in a form field.
- **I click**: Used for clicking a button.
- **I press**: Used for pressing a key.
- **I wait for**: Used for waiting for a certain element to appear on the page.

### Preconditions
- **the shop is ready to checkout**: The shop is ready to checkout, the product is in the cart, and the customer can complete the purchase.
- **the Monei plugin is onboarded**: The Monei plugin is installed and activated, and the Monei API keys are set.
- **the Monei plugin is activated**: The Monei plugin is activated, but not onboarded.
- **the Monei plugin is deactivated**: The Monei plugin is deactivated.
- **the Monei plugin is uninstalled**: The Monei plugin is uninstalled.
- **the User is logged in**: The User is logged in.
- **the User is not logged in**: The User is not logged in.
- **the User has a saved payment method**: The User has a saved payment method.
- **the User does not have a saved payment method**: The User does not have a saved payment method.
- **the User makes a payment in hosted mode with Monei**: The User makes a payment in hosted mode with Monei.

### Assertions
- **Successful transaction**: A transaction that is completed without errors, and the customer is redirected to the success page, and the order is marked as processing.
- **Failed transaction**: A transaction that is completed with errors, and the customer is redirected to the failure page, and the order is marked as failed.
- **Pending transaction**: A transaction that is completed, but the order is not marked as processing, and the customer is redirected to the pending page.
