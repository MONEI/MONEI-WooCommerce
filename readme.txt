=== MONEI Payments for WooCommerce ===
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 7.2.4
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

= v7.2.0 - 2026-08-26 =
-   test: give the cart-restore case the billing its refusal path needs ([09322cc](https://github.com/MONEI/MONEI-WooCommerce/commit/09322cc))
-   fix: use the billing address when a wallet returns an empty shipping one ([09cd33d](https://github.com/MONEI/MONEI-WooCommerce/commit/09cd33d))
-   feat: cover the page while an express payment is being completed ([937df01](https://github.com/MONEI/MONEI-WooCommerce/commit/937df01))
-   docs: regenerate readme changelog for 7.1.3 ([4a917cf](https://github.com/MONEI/MONEI-WooCommerce/commit/4a917cf))

= v7.1.3 - 2026-08-25 =
-   fix: size the single-line card field to match the cardholder input ([805d3e0](https://github.com/MONEI/MONEI-WooCommerce/commit/805d3e0))
-   docs: regenerate readme changelog for 7.1.2 ([c625d73](https://github.com/MONEI/MONEI-WooCommerce/commit/c625d73))

= v7.1.2 - 2026-08-25 =
-   fix: keep the wallet button inside its container on blocks checkout ([be063e7](https://github.com/MONEI/MONEI-WooCommerce/commit/be063e7))
-   fix: stop the wallet container shifting when the button renders ([0cc63c2](https://github.com/MONEI/MONEI-WooCommerce/commit/0cc63c2))
-   docs: regenerate readme changelog for 7.1.1 ([9f28c17](https://github.com/MONEI/MONEI-WooCommerce/commit/9f28c17))

= v7.1.1 - 2026-08-25 =
-   fix: do not register an express wallet the account cannot serve ([9467afb](https://github.com/MONEI/MONEI-WooCommerce/commit/9467afb))
-   fix: give the card field the same bottom margin as the cardholder field ([bb3478e](https://github.com/MONEI/MONEI-WooCommerce/commit/bb3478e))
-   fix: give the save-card checkbox its gap instead of padding the card field ([a0e256c](https://github.com/MONEI/MONEI-WooCommerce/commit/a0e256c))
-   fix: refuse an express order the wallet gave no email for ([9335fba](https://github.com/MONEI/MONEI-WooCommerce/commit/9335fba))
-   fix: stop reporting a dismissed wallet sheet as a checkout error ([1790ba9](https://github.com/MONEI/MONEI-WooCommerce/commit/1790ba9))
-   fix: stop string severities bypassing the log level and filing as errors ([3900a10](https://github.com/MONEI/MONEI-WooCommerce/commit/3900a10))
-   fix: tell the wallet when an address cannot be shipped to ([60e3597](https://github.com/MONEI/MONEI-WooCommerce/commit/60e3597))
-   docs: regenerate readme changelog for 7.1.0 ([c952589](https://github.com/MONEI/MONEI-WooCommerce/commit/c952589))

= v7.1.0 - 2026-08-25 =
-   docs: drop the key-shaped placeholder from the e2e setup ([6fb42e6](https://github.com/MONEI/MONEI-WooCommerce/commit/6fb42e6))
-   docs: hyphenate end-to-end ([2da6a7d](https://github.com/MONEI/MONEI-WooCommerce/commit/2da6a7d))
-   docs: match the e2e README to the wp-env suite ([3cfae74](https://github.com/MONEI/MONEI-WooCommerce/commit/3cfae74))
-   docs: name the key the e2e suite actually requires ([3eebdac](https://github.com/MONEI/MONEI-WooCommerce/commit/3eebdac))
-   docs: point the css lint example at the auto-fixer and refresh the release examples ([fc2cff9](https://github.com/MONEI/MONEI-WooCommerce/commit/fc2cff9))
-   docs: record the multi-package shipping limitation of express ([2e7c30b](https://github.com/MONEI/MONEI-WooCommerce/commit/2e7c30b))
-   docs: require node 22.13, the minimum pnpm 11 accepts ([f425dfd](https://github.com/MONEI/MONEI-WooCommerce/commit/f425dfd))
-   docs: update commands for pnpm ([b4554a1](https://github.com/MONEI/MONEI-WooCommerce/commit/b4554a1))
-   ci: honour MONEI_TEST_ACCOUNT_ID and fail on an account mismatch ([bd2e490](https://github.com/MONEI/MONEI-WooCommerce/commit/bd2e490))
-   ci: pin GitHub Actions to commit SHAs ([490bac9](https://github.com/MONEI/MONEI-WooCommerce/commit/490bac9))
-   ci: restrict workflow token scope and drop checkout credentials ([c532116](https://github.com/MONEI/MONEI-WooCommerce/commit/c532116))
-   ci: run the Jest and PHPUnit suites on every push ([deff5e3](https://github.com/MONEI/MONEI-WooCommerce/commit/deff5e3))
-   fix: ask the supported payment methods endpoint, not the deprecated one ([dd37356](https://github.com/MONEI/MONEI-WooCommerce/commit/dd37356))
-   fix: clear the wallet token on every express failure path ([920e50e](https://github.com/MONEI/MONEI-WooCommerce/commit/920e50e))
-   fix: escape values interpolated into gateway icon and card brand markup ([9b65330](https://github.com/MONEI/MONEI-WooCommerce/commit/9b65330))
-   fix: gate the new-install notice dismissal on a capability check ([616a21e](https://github.com/MONEI/MONEI-WooCommerce/commit/616a21e))
-   fix: keep payment methods when the MONEI lookup fails ([018b0f2](https://github.com/MONEI/MONEI-WooCommerce/commit/018b0f2))
-   fix: keep the borrowed cart until the server confirms its release ([b16fbbc](https://github.com/MONEI/MONEI-WooCommerce/commit/b16fbbc))
-   fix: load the MONEI SDK on the Bizum block checkout ([7ed5312](https://github.com/MONEI/MONEI-WooCommerce/commit/7ed5312))
-   fix: make formatting and sanitizer checks real gates ([7106cbf](https://github.com/MONEI/MONEI-WooCommerce/commit/7106cbf))
-   fix: make the express button style descriptions translatable ([21ca305](https://github.com/MONEI/MONEI-WooCommerce/commit/21ca305))
-   fix: match split card field styling to surrounding form inputs ([f5fa435](https://github.com/MONEI/MONEI-WooCommerce/commit/f5fa435))
-   fix: pass amount and currency to CardInput for monei.js v3 ([c186523](https://github.com/MONEI/MONEI-WooCommerce/commit/c186523))
-   fix: re-cache the payment methods fallback during an API outage ([0d0ee4d](https://github.com/MONEI/MONEI-WooCommerce/commit/0d0ee4d))
-   fix: register block script translations against the monei text domain ([6b9e532](https://github.com/MONEI/MONEI-WooCommerce/commit/6b9e532))
-   fix: reject express ajax replies that carry no usable body ([a32cfed](https://github.com/MONEI/MONEI-WooCommerce/commit/a32cfed))
-   fix: retry card init after a failed attempt and destroy the old card group ([5efe05b](https://github.com/MONEI/MONEI-WooCommerce/commit/5efe05b))
-   fix: round the cart total to minor units instead of truncating it ([47c4271](https://github.com/MONEI/MONEI-WooCommerce/commit/47c4271))
-   fix: run formatter after linters so prettier has the last word ([2155469](https://github.com/MONEI/MONEI-WooCommerce/commit/2155469))
-   fix: save the express session before calling the gateway ([37b5c00](https://github.com/MONEI/MONEI-WooCommerce/commit/37b5c00))
-   fix: stop express block payments running under the previous method ([3076218](https://github.com/MONEI/MONEI-WooCommerce/commit/3076218))
-   fix: stop the express payment observer from aborting non-express checkouts ([c20ffa9](https://github.com/MONEI/MONEI-WooCommerce/commit/c20ffa9))
-   fix: stop writing the whole cart snapshot to the log ([f35daf9](https://github.com/MONEI/MONEI-WooCommerce/commit/f35daf9))
-   fix: translate two strings in the plugin text domain instead of WooCommerce's ([187227f](https://github.com/MONEI/MONEI-WooCommerce/commit/187227f))
-   fix: trim CI secrets and name the key shape when MONEI refuses it ([19e852b](https://github.com/MONEI/MONEI-WooCommerce/commit/19e852b))
-   fix: wait for a pending amount update before tokenizing the card ([01b6813](https://github.com/MONEI/MONEI-WooCommerce/commit/01b6813))
-   test: add playwright e2e coverage for card payments ([5332374](https://github.com/MONEI/MONEI-WooCommerce/commit/5332374))
-   test: cover the Bizum block checkout SDK registration ([d830750](https://github.com/MONEI/MONEI-WooCommerce/commit/d830750))
-   test: cover the express amount check against the running server ([1ad9125](https://github.com/MONEI/MONEI-WooCommerce/commit/1ad9125))
-   test: give each e2e run its own MONEI order id range ([a133e70](https://github.com/MONEI/MONEI-WooCommerce/commit/a133e70))
-   test: keep answering 3DS challenges until the order lands ([a7c8640](https://github.com/MONEI/MONEI-WooCommerce/commit/a7c8640))
-   test: make the e2e tooling fail fast and diagnosably ([b087c66](https://github.com/MONEI/MONEI-WooCommerce/commit/b087c66))
-   test: pay a real express checkout order end to end ([02bcbfc](https://github.com/MONEI/MONEI-WooCommerce/commit/02bcbfc))
-   test: require both E2E site targets explicitly ([e3a7287](https://github.com/MONEI/MONEI-WooCommerce/commit/e3a7287))
-   test: run the Bizum component test only where MONEI offers Bizum ([55db567](https://github.com/MONEI/MONEI-WooCommerce/commit/55db567))
-   test: run the card journeys only where 3DS can complete ([ab26ffe](https://github.com/MONEI/MONEI-WooCommerce/commit/ab26ffe))
-   test: run the e2e suite against wp-env and pay a real express order ([2a1ec61](https://github.com/MONEI/MONEI-WooCommerce/commit/2a1ec61))
-   style: apply the project phpcs standard to the plugin bootstrap class ([90c22d0](https://github.com/MONEI/MONEI-WooCommerce/commit/90c22d0))
-   chore: add explicit stylelint config so prettier and stylelint agree ([2697d6b](https://github.com/MONEI/MONEI-WooCommerce/commit/2697d6b))
-   chore: bootstrap jest test infrastructure ([2df1d7b](https://github.com/MONEI/MONEI-WooCommerce/commit/2df1d7b))
-   chore: bootstrap phpunit for pure helper tests ([a3b7e57](https://github.com/MONEI/MONEI-WooCommerce/commit/a3b7e57))
-   chore: declare support for WordPress 7.0 and WooCommerce 11.0 ([3e30592](https://github.com/MONEI/MONEI-WooCommerce/commit/3e30592))
-   chore: gitignore local implementation plans ([ba53e44](https://github.com/MONEI/MONEI-WooCommerce/commit/ba53e44))
-   chore: migrate from yarn to pnpm ([8fe7f96](https://github.com/MONEI/MONEI-WooCommerce/commit/8fe7f96))
-   chore: regenerate the translation files for the express checkout strings ([6a3f61f](https://github.com/MONEI/MONEI-WooCommerce/commit/6a3f61f))
-   chore: translate the express checkout title ([dd2a593](https://github.com/MONEI/MONEI-WooCommerce/commit/dd2a593))
-   feat: add card field layout setting for split card fields ([b984c72](https://github.com/MONEI/MONEI-WooCommerce/commit/b984c72))
-   feat: add express cart details and shipping options endpoints ([3a7e901](https://github.com/MONEI/MONEI-WooCommerce/commit/3a7e901))
-   feat: add express checkout ajax handler scaffolding and bootstrap ([b2a092a](https://github.com/MONEI/MONEI-WooCommerce/commit/b2a092a))
-   feat: add express checkout settings and gating ([46be780](https://github.com/MONEI/MONEI-WooCommerce/commit/46be780))
-   feat: add express checkout wallet buttons to the cart page ([7c12c27](https://github.com/MONEI/MONEI-WooCommerce/commit/7c12c27))
-   feat: add express checkout wallet buttons to the checkout page ([1f50315](https://github.com/MONEI/MONEI-WooCommerce/commit/1f50315))
-   feat: add express checkout wallet buttons to the product page ([bfb3b7b](https://github.com/MONEI/MONEI-WooCommerce/commit/bfb3b7b))
-   feat: add split card fields to blocks checkout ([0ca3a45](https://github.com/MONEI/MONEI-WooCommerce/commit/0ca3a45))
-   feat: add split card fields to classic checkout ([cf4a864](https://github.com/MONEI/MONEI-WooCommerce/commit/cf4a864))
-   feat: add the PayPal express checkout button to all three surfaces ([c3a0bd1](https://github.com/MONEI/MONEI-WooCommerce/commit/c3a0bd1))
-   feat: add useMoneiCardGroup hook for split card fields ([19589de](https://github.com/MONEI/MONEI-WooCommerce/commit/19589de))
-   feat: create the express order server side and verify its amount ([8fef615](https://github.com/MONEI/MONEI-WooCommerce/commit/8fef615))
-   feat: let express checkout renew subscription products ([1d03892](https://github.com/MONEI/MONEI-WooCommerce/commit/1d03892))
-   feat: migrate monei.js from v2 to v3 ([7daa493](https://github.com/MONEI/MONEI-WooCommerce/commit/7daa493))
-   feat: normalize express wallet addresses and update shipping method ([3e231d9](https://github.com/MONEI/MONEI-WooCommerce/commit/3e231d9))
-   feat: save and restore the cart around a product page express payment ([7ec3c0b](https://github.com/MONEI/MONEI-WooCommerce/commit/7ec3c0b))
-   feat: style split card fields ([a628e54](https://github.com/MONEI/MONEI-WooCommerce/commit/a628e54))
-   refactor: replace deprecated createToken with instance.submit ([093e5d1](https://github.com/MONEI/MONEI-WooCommerce/commit/093e5d1))
-   refactor: simplify the express beacon field assembly ([8e756de](https://github.com/MONEI/MONEI-WooCommerce/commit/8e756de))

= v7.0.3 - 2026-03-26 =
-   fix: filter release commits and standardize markdown headers in readme generator ([e9491b3](https://github.com/MONEI/MONEI-WooCommerce/commit/e9491b3))
-   fix: skip token creation in CC block checkout redirect mode ([b8e1ff8](https://github.com/MONEI/MONEI-WooCommerce/commit/b8e1ff8))