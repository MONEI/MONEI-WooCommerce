=== MONEI Payments for WooCommerce ===
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 7.3.6
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 11.0

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

= v7.3.6 - 2026-10-20 =
-   fix: pass store language to MONEI components in classic checkout (#129) ([7005a38](https://github.com/MONEI/MONEI-WooCommerce/commit/7005a38)), closes [#129](https://github.com/MONEI/MONEI-WooCommerce/issues/129)
-   test(e2e): note express specs need a test account without a cardholder name rule (#128) ([3a381cb](https://github.com/MONEI/MONEI-WooCommerce/commit/3a381cb)), closes [#128](https://github.com/MONEI/MONEI-WooCommerce/issues/128)
-   docs: regenerate readme changelog for 7.3.4 ([c0dd4b9](https://github.com/MONEI/MONEI-WooCommerce/commit/c0dd4b9))

= v7.3.4 - 2026-10-14 =
-   fix: stop gateway constructors from calling the payment methods API (#127) ([fa735a5](https://github.com/MONEI/MONEI-WooCommerce/commit/fa735a5)), closes [#127](https://github.com/MONEI/MONEI-WooCommerce/issues/127)
-   test: stop seeding the pre-7.3.0 card field layout (#125) ([5c848e4](https://github.com/MONEI/MONEI-WooCommerce/commit/5c848e4)), closes [#125](https://github.com/MONEI/MONEI-WooCommerce/issues/125)
-   docs: regenerate readme changelog for 7.3.3 ([af742a8](https://github.com/MONEI/MONEI-WooCommerce/commit/af742a8))

= v7.3.3 - 2026-09-11 =
-   fix: centre the text in the card fields (#124) ([4e51138](https://github.com/MONEI/MONEI-WooCommerce/commit/4e51138)), closes [#124](https://github.com/MONEI/MONEI-WooCommerce/issues/124)
-   test: compare how the payment methods render at checkout (#122) ([a52e09d](https://github.com/MONEI/MONEI-WooCommerce/commit/a52e09d)), closes [#122](https://github.com/MONEI/MONEI-WooCommerce/issues/122)
-   test: fail the Bizum case when its availability cannot be determined (#123) ([f1b0ae3](https://github.com/MONEI/MONEI-WooCommerce/commit/f1b0ae3)), closes [#123](https://github.com/MONEI/MONEI-WooCommerce/issues/123)
-   docs: regenerate readme changelog for 7.3.2 ([b21bdd3](https://github.com/MONEI/MONEI-WooCommerce/commit/b21bdd3))

= v7.3.2 - 2026-09-08 =
-   fix: stop polling the API on hosts where transients do not persist ([00797d1](https://github.com/MONEI/MONEI-WooCommerce/commit/00797d1))
-   style: align the UNAVAILABLE array the way phpcs wants it ([7111b93](https://github.com/MONEI/MONEI-WooCommerce/commit/7111b93))
-   docs: regenerate readme changelog for 7.3.1 ([95993a7](https://github.com/MONEI/MONEI-WooCommerce/commit/95993a7))

= v7.3.1 - 2026-09-07 =
-   fix: cache a failed payment-methods lookup so a wrong key does not poll the API per render (#120) ([0fc7c17](https://github.com/MONEI/MONEI-WooCommerce/commit/0fc7c17)), closes [#120](https://github.com/MONEI/MONEI-WooCommerce/issues/120)
-   docs: regenerate readme changelog for 7.3.0 ([131ac73](https://github.com/MONEI/MONEI-WooCommerce/commit/131ac73))

= v7.3.0 - 2026-08-27 =
-   feat: show the card fields as separate fields by default (#119) ([9b6a1b2](https://github.com/MONEI/MONEI-WooCommerce/commit/9b6a1b2)), closes [#119](https://github.com/MONEI/MONEI-WooCommerce/issues/119)
-   chore: track the browser preview launch config ([e36eb5f](https://github.com/MONEI/MONEI-WooCommerce/commit/e36eb5f))
-   docs: regenerate readme changelog for 7.2.4 ([9689a4c](https://github.com/MONEI/MONEI-WooCommerce/commit/9689a4c))

= v7.2.4 - 2026-08-27 =
-   fix: pre-authorize wallet payments and let the capture actually stick (#118) ([2da42fa](https://github.com/MONEI/MONEI-WooCommerce/commit/2da42fa)), closes [#118](https://github.com/MONEI/MONEI-WooCommerce/issues/118)
-   test: pay with express PayPal on a store that does not ship (#117) ([c70b798](https://github.com/MONEI/MONEI-WooCommerce/commit/c70b798)), closes [#117](https://github.com/MONEI/MONEI-WooCommerce/issues/117)
-   docs: regenerate readme changelog for 7.2.3 ([689a789](https://github.com/MONEI/MONEI-WooCommerce/commit/689a789))

= v7.2.3 - 2026-08-26 =
-   test: skip the PayPal specs instead of failing when unconfigured ([ac21f78](https://github.com/MONEI/MONEI-WooCommerce/commit/ac21f78))
-   docs: regenerate readme changelog for 7.2.2 ([ec4dcb6](https://github.com/MONEI/MONEI-WooCommerce/commit/ec4dcb6))

= v7.2.2 - 2026-08-26 =
-   test: pay with a real PayPal sandbox account end to end ([a3fdb41](https://github.com/MONEI/MONEI-WooCommerce/commit/a3fdb41))
-   test: pay with PayPal on the surface that can complete the order ([79ccdda](https://github.com/MONEI/MONEI-WooCommerce/commit/79ccdda))
-   fix: surface an express checkout failure on the cart, not only the checkout ([ae72255](https://github.com/MONEI/MONEI-WooCommerce/commit/ae72255))
-   docs: regenerate readme changelog for 7.2.1 ([19ef57a](https://github.com/MONEI/MONEI-WooCommerce/commit/19ef57a))

= v7.2.1 - 2026-08-26 =
-   fix: report express failures on every surface, not just the checkout block ([87471bb](https://github.com/MONEI/MONEI-WooCommerce/commit/87471bb))
-   docs: regenerate readme changelog for 7.2.0 ([967c529](https://github.com/MONEI/MONEI-WooCommerce/commit/967c529))