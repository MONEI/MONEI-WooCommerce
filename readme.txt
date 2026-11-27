=== MONEI Payments for WooCommerce ===
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 7.5.2
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
* Go to WooCommerce > Settings > MONEI Settings
* Click "Connect with MONEI", sign in to MONEI and approve the connection. The shop gets its own API keys for Test and Live mode. You never copy a key.
* Select the API Key Mode (Test or Live) and save.

To disconnect, click "Disconnect" on the same page. The keys stay active until you revoke them in [MONEI Dashboard > Settings > API Access](https://dashboard.monei.com/settings/api).

= Enter API keys manually =

Connect with MONEI needs a shop on a public HTTPS address. On other shops, or if you prefer, enter the keys yourself:

* Go to [MONEI Dashboard > Settings > API Access](https://dashboard.monei.com/settings/api)
* Click on "Create API Key"
* In WooCommerce > Settings > MONEI Settings, add your Account ID and API Key. If the page shows "Connect with MONEI", click "Enter API keys manually" first.

= Use of 3rd Party Services =
This plugin is using [MONEI API](https://docs.monei.com/api/) to process payments as well as
[MONEI UI Components](https://docs.monei.com/docs/monei-js/overview/) to securely collect sensitive payment information during checkout.
"Connect with MONEI" uses the MONEI authorization service (https://oauth.monei.com) to create the shop's API keys after you approve the connection.

By using this plugin you agree with MONEI [Terms of Service](https://monei.com/legal-notice/) and [Privacy Policy](https://monei.com/privacy-policy/)

== Screenshots ==

1. Apple Pay, Bizum, PayPal, credit Card
2. Google Pay, Bizum, PayPal, credit Card

== Changelog ==

= v7.5.2 - 2026-11-27 =
* fix(deps): update monei-php-sdk to 2.18.3 for Connect on MONEI's demo store (#143) ([d323dff](https://github.com/MONEI/MONEI-WooCommerce/commit/d323dff)), closes [#143](https://github.com/MONEI/MONEI-WooCommerce/issues/143)
* test(ipn): mixed shop callbacks and the IPN status pass-through (#142) ([86df10c](https://github.com/MONEI/MONEI-WooCommerce/commit/86df10c)), closes [#142](https://github.com/MONEI/MONEI-WooCommerce/issues/142)

= v7.5.1 - 2026-11-25 =
-   fix(ipn): refuse a signed callback of the other mode (#141) ([482397b](https://github.com/MONEI/MONEI-WooCommerce/commit/482397b)), closes [#141](https://github.com/MONEI/MONEI-WooCommerce/issues/141)

= v7.5.0 - 2026-11-25 =
-   feat: connect with MONEI (#140) ([39d84cc](https://github.com/MONEI/MONEI-WooCommerce/commit/39d84cc)), closes [#140](https://github.com/MONEI/MONEI-WooCommerce/issues/140)
-   fix: cap the shopper-posted values in the express refusal log ([54f72b7](https://github.com/MONEI/MONEI-WooCommerce/commit/54f72b7))
-   fix: log every refused express checkout order ([1fab155](https://github.com/MONEI/MONEI-WooCommerce/commit/1fab155))
-   fix: pin the express refusal log to ERROR and log the fallback message ([b303b08](https://github.com/MONEI/MONEI-WooCommerce/commit/b303b08))

= v7.4.2 - 2026-11-07 =
-   fix: checkout page express fills a full billing address and uses the wallet's shipping pick (#138) ([81fd4a5](https://github.com/MONEI/MONEI-WooCommerce/commit/81fd4a5)), closes [#138](https://github.com/MONEI/MONEI-WooCommerce/issues/138)

= v7.4.1 - 2026-11-07 =
-   fix: give express orders a full billing address when PayPal returns only a country (#136) ([c740681](https://github.com/MONEI/MONEI-WooCommerce/commit/c740681)), closes [#136](https://github.com/MONEI/MONEI-WooCommerce/issues/136)
-   chore(release): regenerate the readme in the release commit so WordPress.org gets the new changelog ([982bde6](https://github.com/MONEI/MONEI-WooCommerce/commit/982bde6)), closes [#137](https://github.com/MONEI/MONEI-WooCommerce/issues/137)
-   docs: regenerate readme changelog for 7.4.0 ([11d5727](https://github.com/MONEI/MONEI-WooCommerce/commit/11d5727))

= v7.4.0 - 2026-11-07 =
-   fix: send the guest session ID when the buyer creates an account at checkout (#135) ([b77e85a](https://github.com/MONEI/MONEI-WooCommerce/commit/b77e85a)), closes [#135](https://github.com/MONEI/MONEI-WooCommerce/issues/135)
-   fix: stop shoppers paying with another user's saved card (#132) ([72fde93](https://github.com/MONEI/MONEI-WooCommerce/commit/72fde93)), closes [#132](https://github.com/MONEI/MONEI-WooCommerce/issues/132)
-   test(e2e): cover MONEI customers and unbreak the PayPal express spec (#134) ([b0ddfa0](https://github.com/MONEI/MONEI-WooCommerce/commit/b0ddfa0)), closes [#134](https://github.com/MONEI/MONEI-WooCommerce/issues/134)
-   feat: link payments to MONEI customers and delete them with the WordPress user (#133) ([feeb9ca](https://github.com/MONEI/MONEI-WooCommerce/commit/feeb9ca)), closes [#133](https://github.com/MONEI/MONEI-WooCommerce/issues/133)
-   docs: regenerate readme changelog for 7.3.7 ([2c1c556](https://github.com/MONEI/MONEI-WooCommerce/commit/2c1c556))

= v7.3.7 - 2026-10-22 =
-   fix(deps): update monei-php-sdk to 2.13 for bundled Guzzle security fixes (#131) ([adfdb48](https://github.com/MONEI/MONEI-WooCommerce/commit/adfdb48)), closes [#131](https://github.com/MONEI/MONEI-WooCommerce/issues/131)
-   docs: regenerate readme changelog for 7.3.6 ([720369d](https://github.com/MONEI/MONEI-WooCommerce/commit/720369d))

= v7.3.6 - 2026-10-21 =
-   fix: harden order verification on customer return (#130) ([90114b7](https://github.com/MONEI/MONEI-WooCommerce/commit/90114b7)), closes [#130](https://github.com/MONEI/MONEI-WooCommerce/issues/130)
-   docs: regenerate readme changelog for 7.3.5 ([dfcb63b](https://github.com/MONEI/MONEI-WooCommerce/commit/dfcb63b))

= v7.3.5 - 2026-10-20 =
-   fix: pass store language to MONEI components in classic checkout (#129) ([7005a38](https://github.com/MONEI/MONEI-WooCommerce/commit/7005a38)), closes [#129](https://github.com/MONEI/MONEI-WooCommerce/issues/129)
-   test(e2e): note express specs need a test account without a cardholder name rule (#128) ([3a381cb](https://github.com/MONEI/MONEI-WooCommerce/commit/3a381cb)), closes [#128](https://github.com/MONEI/MONEI-WooCommerce/issues/128)
-   docs: regenerate readme changelog for 7.3.4 ([c0dd4b9](https://github.com/MONEI/MONEI-WooCommerce/commit/c0dd4b9))

= v7.3.4 - 2026-10-14 =
-   fix: stop gateway constructors from calling the payment methods API (#127) ([fa735a5](https://github.com/MONEI/MONEI-WooCommerce/commit/fa735a5)), closes [#127](https://github.com/MONEI/MONEI-WooCommerce/issues/127)
-   test: stop seeding the pre-7.3.0 card field layout (#125) ([5c848e4](https://github.com/MONEI/MONEI-WooCommerce/commit/5c848e4)), closes [#125](https://github.com/MONEI/MONEI-WooCommerce/issues/125)
-   docs: regenerate readme changelog for 7.3.3 ([af742a8](https://github.com/MONEI/MONEI-WooCommerce/commit/af742a8))