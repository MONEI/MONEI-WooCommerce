=== MONEI Payments for WooCommerce ===
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 6.3.2
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 9.8

Accept Card, Apple Pay, Google Pay, Bizum, PayPal and many more payment methods in your WooCommerce store using MONEI payment gateway.

== Description ==

= ACCEPT ONLINE PAYMENTS WITH MONEI =
MONEI is an e-commerce payment gateway for WooCommerce (and other e-commerce platforms).


Its payment gateway is the choice of many Spain and Andorra based e-commerce businesses. Use MONEI’s technology to accept and manage all major and alternative payment methods in a single platform.


MONEI is dedicated to helping you simplify your digital payments so you can focus on growing your online business.

= PAYMENT METHODS =
Use MONEI’s payment gateway to accept debit and credit card payments from customers worldwide in 230+ currencies.


Let shoppers pay from the convenience of their smartphone with digital wallets like Apple Pay, Google Pay, and PayPal. And accept local payment methods such as Bizum (Spain) and SEPA Direct Debit (EU).


Offering customers [many payment methods](https://monei.com/es/online-payment-methods/) leads to an increase in sales and customer satisfaction. 🚀

= WHY TO USE MONEI’S PAYMENT PLUGIN FOR WOOCOMMERCE =

MONEI’s serverless architecture allows you to scale and process a high volume of transactions. Its dynamic pricing model means as you sell more your transaction fees decrease. Once you’re an approved merchant, enjoy 1-day payment settlements.


Payment security is crucial. MONEI is PCI DSS compliant, 3D Secure, and uses payment tokenization to make sure sensitive payment information is never compromised.


Connect your custom domain to MONEI and customize the appearance of your checkout page to build trust and brand awareness.


With MONEI’s payment gateway for e-commerce, get real-time sales analytics via your customer dashboard.


Please go to the 👉 [signup page](https://dashboard.monei.com/?action=signUp) 👈 to create a new MONEI account. Contact support@monei.com if you have any questions or feedback about this plugin.


= PAYMENT GATEWAY FEATURES =
* Merchant support for all available MONEI payment methods
* Accept and manage all major and alternative payment methods in a single platform
* Quickly and easily integrate with your WooCommerce website using MONEI’s API
* Connect your custom domain to MONEI and customize the appearance of your checkout page
* Scale and process a high volume of transactions
* Dynamic pricing model — as you sell more your transaction fees decrease
* Verified merchants enjoy 1-day payment settlements
* PCI-DSS compliant
* Self-hosted flexible input fields
* Supports 3D Secure and 3D Secure exemptions
* Tokenization for deep integration of recurring billing + usage-based charges
* Subscriptions support for various payment methods via WooCommerce Subscriptions
* 13 languages available with auto-detection based on browser language
* Capture pre-authorized payments and process refunds within your WooCommerce admin Dashboard
* Notifications via email or SMS for customer information and monitoring your store
* Get real-time sales analytics via your customer dashboard


= GETTING STARTED WITH MONEI =
1. How do I open my MONEI account so I can plug in with WooCommerce?
Learn how to [get started with MONEI here ››](https://support.monei.com/hc/en-us/articles/360017801677-Get-started-with-MONEI)
2. What countries does MONEI support?
Currently, MONEI is available in Spain and Andorra, but our global expansion is happening fast. [Join our newsletter here](https://client.moonmail.io/ac8e391c-8cfb-46e3-aed9-e7a84d0fd830/forms/6bafcdbf-442a-4e3b-874f-7e2ed30ee001) to get notified once we support your country!
3. I have different questions about this plugin.
Please contact support@monei.com with your MONEI ID. Describe your problem in detail and include screenshots when necessary.

== Installation ==
* Go to wp-admin > Plugins
* Click Add new
* Search for MONEI
* Press Install
* Press Activate now
* Go to WooCommerce > Settings > Payments > MONEI
* Add your API Key.

= If you don't have API Key =

* Go to [MONEI Dashboard > Settings > API Access](https://dashboard.monei.com/settings/api)
* Click on "Create API Key"

= Use of 3rd Party Services =
This plugin is using [MONEI API](https://docs.monei.com/api/) to process payments as well as
[MONEI UI Components](https://docs.monei.com/docs/monei-js/overview/) to securely collect sensitive payment information during checkout.

By using this plugin you agree with MONEI [Terms of Service](https://monei.com/legal-notice/) and [Privacy Policy](https://monei.com/privacy-policy/)

== Screenshots ==

1. Apple Pay, Bizum, PayPal, credit Card
2. Google Pay, Bizum, PayPal, credit Card

== Changelog ==

2025-05-05 - version 6.3.2
* Fix - Error in checkout when no subscription plugin present
* Fix - Showing only available payment methods when subscription product in cart
* Fix - Error in API key selector expected test/live account id

2025-04-25 - version 6.3.1
* Fix - Checkout errors. Rollback to version 6.2.1

2025-04-24 - version 6.3.0
* Add - Selector for live/test API key, now we save both
* Add - Integration for YITH Subscriptions
* Fix - Change payment method for subscriptions
* Fix - Renewal process in WooCommerce Subscriptions

2025-04-07 - version 6.2.1
* Fix - Update Monei SDK to V2

2025-02-18 - version 6.2.0
* Add - PayPal method in block checkout
* Fix - Plugin check issues
* Fix - Show only the methods enabled in MONEI dashboard
* Fix - Show correct icon for Apple Pay and GooglePay
* Fix - Remove MONEI settings tab
* Fix - Remove support and review link from banner

2024-12-26 - version 6.1.2
* Fix - Cardholder Name not translated in block checkout
* Fix - Plugin check issues
* Fix - Move images to public folder

2024-11-27 - version 6.1.1
* Fix - Default css class in container

2024-11-26 - version 6.1.0
* Add - Multibanco payment method
* Add - MBWay payment method
* Fix - Add default css class for checkout inputs
* Fix - Add credit card icons with more cards

2024-11-22 - version 6.0.0
* Fix - Bump release number to 6.0.0

2024-11-21 - version 5.9.0
* Add - Credit card to block checkout
* Add - Bizum button to block checkout without redirect
* Add - Bizum button to short-code checkout
* Add - Apple and Google buttons as independent method to block checkout
* Add - Apple and Google buttons as independent method to short-code checkout
* Add - Credit card cardholder name in short-code checkout
* Add - Monei settings in a separated tab
* Add - Central API keys, and logs
* Fix - Disable gateways if no API keys
* Fix - Credit card fields follows WooCommerce styles
* Fix - Gateway disappear if no description provided

2024-8-29 - version 5.8.13
* Fix - Apple Validation file error
* Fix - Remove checkout Apple/Google buttons border
* Fix - Redirect to cart on fail - now we redirect to retry
* Fix - Onboarding message links
* Fix - Error message object on invalid Credit Card name
* Fix - Card input error message (@greguly)
* Fix - Log disabled if credit card logs disabled - now are independent
* Fix - Button render issues

2024-6-10 - version 5.8.12
* Update dependencies

2023-11-30 - version 5.7.0
* Update dependencies

2022-5-15 - version 5.6.6
* Monei PHP SDK upgrade. Guzzle 7.x

2022-2-11 - version 5.6.4
* Hide/Show Payment request button on tokenized card selection

2022-2-4 - version 5.6.3
* Pass billing and shipping information when transaction is created

2022-1-12 - version 5.6.1
* Readme Update.

2021-12-15 - version 5.6
* Apple / Google Pay Support.
* Minor fixes.
* API keys from different payment methods support.

2021-11-22 - version 5.5
* Adding Subscriptions Support.
* Minor fixes.

2021-10-13- version 5.4
* Adding Cofidis Support.
* Adding Pre-Auth to Paypal.
* Bug Fixing.

2021-10-4 - version 5.2
* Adding Component CC and Hosted CC Support.
* Fixing Warnings.

2021-7-27 - version 5.0
* Major refactor.