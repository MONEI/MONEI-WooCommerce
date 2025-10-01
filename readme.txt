=== MONEI Payments for WooCommerce ===
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 6.3.10
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 9.8

Accept Card, Apple Pay, Google Pay, Bizum, PayPal and many more payment methods in your WooCommerce store using MONEI payment gateway.

== Description ==

= ACCEPT ONLINE PAYMENTS WITH MONEI =
MONEI is an e-commerce payment gateway for WooCommerce (and other e-commerce platforms).


Its payment gateway is the choice of many Spain and Andorra based e-commerce businesses. Use MONEI's technology to accept and manage all major and alternative payment methods in a single platform.


MONEI is dedicated to helping you simplify your digital payments so you can focus on growing your online business.

= PAYMENT METHODS =
Use MONEI's payment gateway to accept debit and credit card payments from customers worldwide in 230+ currencies.


Let shoppers pay from the convenience of their smartphone with digital wallets like Apple Pay, Google Pay, and PayPal. And accept local payment methods such as Bizum (Spain) and SEPA Direct Debit (EU).


Offering customers [many payment methods](https://monei.com/es/online-payment-methods/) leads to an increase in sales and customer satisfaction. 🚀

= WHY TO USE MONEI'S PAYMENT PLUGIN FOR WOOCOMMERCE =

MONEI's serverless architecture allows you to scale and process a high volume of transactions. Its dynamic pricing model means as you sell more your transaction fees decrease. Once you're an approved merchant, enjoy 1-day payment settlements.


Payment security is crucial. MONEI is PCI DSS compliant, 3D Secure, and uses payment tokenization to make sure sensitive payment information is never compromised.


Connect your custom domain to MONEI and customize the appearance of your checkout page to build trust and brand awareness.


With MONEI's payment gateway for e-commerce, get real-time sales analytics via your customer dashboard.


Please go to the 👉 [signup page](https://dashboard.monei.com/?action=signUp) 👈 to create a new MONEI account. Contact support@monei.com if you have any questions or feedback about this plugin.


= PAYMENT GATEWAY FEATURES =
* Merchant support for all available MONEI payment methods
* Accept and manage all major and alternative payment methods in a single platform
* Quickly and easily integrate with your WooCommerce website using MONEI's API
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

= v5.1.0 - 2021-07-28 =
* - check if WooCommerce plugin is activated (5d7b61c)
* - decode secret token to get channel credentials (53dae33)
* - document code (6bff421)
* - move api methods to a separate class (f376cfb)
* - refactor all public methods to be uniquely namespaced (4e5f5a9)
* - refactor callback logic to use our endpoint (b479f5f)
* - replace payon wiget with monei widget (17bb89d)
* - save monei transaction status in order (93b5001)
* - support basic configurations (5025616)
* adapting bizum settings (9b7c4ed)
* Add file via upload (6fac72e)
* add monei logo to the repo (6d907ce)
* add paypal, bitocin, alipay to payment methods (f6cb3f1)
* add readme.txt (d47c98e)
* Added refunds. (5006311)
* adding action to ipn failure (2916a19)
* Adding basic API SDK wrapper (0ff57c0)
* Adding custom logger wrapper (2fe3c52)
* adding empty bizum class (538bca0)
* adding new core functions (1bf26a0)
* Adding some docs (6f1d322)
* Adding tokenisation to checkout (f3ad25c)
* Adding tokenisation to checkout form (316db95)
* Adding wordpress readme.txt (3d21c40)
* assents (ec382e4)
* AUTH + IPN (cc56c06)
* bumping v (a982f16)
* cancel pre-auth orders. (374da8e)
* capture payments (b3459eb)
* changing order status when refunded. (a7e05c2)
* changing redirect filename (cc8cc23)
* cleanup (3ab1e18)
* components not available yet. (bafd4db)
* Create main.yml (bd5a978)
* Creating new foundation for the plugin. (568cc01)
* Delete .DS_Store (4a548b9)
* Delete .DS_Store (7d09a15)
* Delete .DS_Store (3b56c3f)
* Delete redsys.png (0659238)
* first commit to credit card component (e482e7d)
* first revision after testing. (f974cb5)
* fix action typo (5a95956)
* fix param typo (07a9d7c)
* fix settings link (5e4fb6d)
* fixing logging file name (2adff74)
* fixing typo (1597343)
* generate a dynamic link to the transaction in admin view (1497eed)
* get test_mode from decoded token (2a2a5d3)
* handle failUrl from monei. (fb565fc)
* Including IPN class to handle callbacks from Monei (7e139fa)
* indentation (96ebd26)
* Initial commit (d209425)
* js errors done right. (e84a098)
* minor fixes and code cleanup (a1d81b6)
* more bizum (12a3201)
* more refactor (5faddfc)
* on monei selected, trigger init (5832539)
* on user action back to store, we shouldn't cancel, rather redirect them to checkout page. (77e2141)
* order on-hold on auth (84212f0)
* pass wp locale to widget (50f5303)
* password not longer used. (2028088)
* paypal payment method (46c3968)
* plugin (784b568)
* provide shipping address (5a9e5e7)
* refactor to hosted payment methods. (6ddd6db)
* refactoring gateway constructor, removing unused functionality (914832b)
* refactoring on valid IPN (0e16c9d)
* refactoring process payment. (9a1e8d1)
* refunds done right. (2a0114b)
* remove type annotations (ffa7a0e)
* removed unnecessary metadata (e6d36bd)
* rename decode => woo_monei_decode_token (8c390e6)
* rename not allowed function names (fb409d7)
* replace monei.net -> monei.com (cd376cd)
* replace money logo (f565662)
* Revert (7f005fa)
* saving expiration date (1416c2b)
* sending payment method as card (61670c9)
* show ssl error only on monei settings page (50832ce)
* some cleanup (1156edb)
* some extra refactor en payment gateway (47e0a4a)
* some refactoring (5620321)
* support most of presentational parameters (b8c191b)
* tabs (7def901)
* Tokenisation support for "add card" on profile. (4220318)
* Update (e255552)
* update copy (80c9edd)
* update copy (6a0b599)
* update copy (243f18e)
* Update main.yml (499bb3f)
* Update main.yml (b9b32fd)
* Update main.yml (b765061)
* Update module (dc63a73)
* update readme (9a0944a)
* update readme (1abd27a)
* update readme (ee09f85)
* update readme (d32f16a)
* Update readme (f2d88a8)
* Update readme (0ccce57)
* Update Readme (5583e03)
* Update README.md (19ab018)
* Update README.md (9812a11)
* Update README.md (073b1e7)
* Update README.md (6e216f4)
* update stable tag (3c64e5a)
* update version (941093b)
* updating php sdk (edfe873)
* use chosen.js to make brands selection user friendly (85d9a1e)
* Version (e46553a)
* Version 2.0.0 (87c48f7)
* Version 2.1 (b8de90d)
* Version 3.0 (9906102)
* Version 4.2.1 (933186f)

2025-08-25 - version 6.3.8
* Fix - Move ApplePay and Google Pay to a separated gateway
* Fix - Add settings shortcut to plugins page
* Fix - Add missing space in webhook notice

2025-07-02 - version 6.3.7
* Fix - Send correct useragent version
* Fix - plugin crashes when updating from older version

2025-06-05 - version 6.3.7
* Fix - Remove old _payment_method transients on activation and update

2025-06-04 - version 6.3.5
* Fix - Remove Cofidis payment method as is not supported
* Fix - Reduce caching of payment methods and change to Monei SDK

2025-05-30 - version 6.3.4
* Fix - Redirect flow not working in classic checkout

2025-05-14 - version 6.3.3
* Fix - Error copying old keys that hides the gateway
* Fix - Component buttons not showing in classic checkout
* Fix - PayPal button not working in block checkout

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
