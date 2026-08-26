=== MONEI Payments for WooCommerce ===
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 7.2.0
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

= v7.0.2 - 2025-11-20 =
-   fix: prevent wp_sanitize_redirect from stripping domain in payment URLs ([a982699](https://github.com/MONEI/MONEI-WooCommerce/commit/a982699))

= v7.0.1 - 2025-10-14 =
-   fix: upgrade to PHP 8.0+ to resolve PHP-DI compatibility issue ([95f9ffd](https://github.com/MONEI/MONEI-WooCommerce/commit/95f9ffd))
-   PHP 7.4 users cannot upgrade. PHP 8.0 is now the
    minimum required version. PHP 7.4 reached end-of-life in November
2022. WordPress 6.8 officially supports PHP 8.0-8.3.

= v7.0.0 - 2025-10-10 =
-   chore: add PHPCS rule to enforce namespace use statements ([248d8bb](https://github.com/MONEI/MONEI-WooCommerce/commit/248d8bb))
-   chore: add PHPCS rule to enforce use statements over fully qualified names ([eb53879](https://github.com/MONEI/MONEI-WooCommerce/commit/eb53879))
-   chore: remove pre-push hook to prevent direct pushes to master/main branch ([abad3bf](https://github.com/MONEI/MONEI-WooCommerce/commit/abad3bf))
-   chore: setup comprehensive linting workflow with lint-staged ([db39b8a](https://github.com/MONEI/MONEI-WooCommerce/commit/db39b8a))
-   chore: update .gitignore and package.json for translation support ([f8b1cbe](https://github.com/MONEI/MONEI-WooCommerce/commit/f8b1cbe))
-   chore: update GitHub Actions workflow for code quality checks ([24c8082](https://github.com/MONEI/MONEI-WooCommerce/commit/24c8082))
-   fix: add has_fields() method to CC gateway for component mode visibility ([0efb59f](https://github.com/MONEI/MONEI-WooCommerce/commit/0efb59f))
-   fix: add hide logo option to Apple/Google Pay ([af7e120](https://github.com/MONEI/MONEI-WooCommerce/commit/af7e120))
-   fix: add include for payment method display and fix PHPStan errors ([70ca589](https://github.com/MONEI/MONEI-WooCommerce/commit/70ca589))
-   fix: add null checks and fallbacks to all classic payment methods ([0488427](https://github.com/MONEI/MONEI-WooCommerce/commit/0488427))
-   fix: allow payment retry recovery for failed orders in classic checkout ([4f2adce](https://github.com/MONEI/MONEI-WooCommerce/commit/4f2adce))
-   fix: always include payment ID in card payment redirect URL ([8d3f062](https://github.com/MONEI/MONEI-WooCommerce/commit/8d3f062))
-   fix: Apple Pay domain verification automatic registration ([354e290](https://github.com/MONEI/MONEI-WooCommerce/commit/354e290))
-   fix: conditionally render monei-text span in blocks checkout labels ([bcfa80f](https://github.com/MONEI/MONEI-WooCommerce/commit/bcfa80f))
-   fix: correct card input container padding to zero ([499c7fe](https://github.com/MONEI/MONEI-WooCommerce/commit/499c7fe))
-   fix: display error text in cardholder name validation ([45cdfa9](https://github.com/MONEI/MONEI-WooCommerce/commit/45cdfa9))
-   fix: ensure consistent fieldset layout across all payment methods ([f9a1625](https://github.com/MONEI/MONEI-WooCommerce/commit/f9a1625))
-   fix: filter card brands by key instead of localized title ([3db424c](https://github.com/MONEI/MONEI-WooCommerce/commit/3db424c))
-   fix: filter default card brand by key instead of localized title ([866070b](https://github.com/MONEI/MONEI-WooCommerce/commit/866070b))
-   fix: fix redirect mode for payment methods and description field visibility ([624872e](https://github.com/MONEI/MONEI-WooCommerce/commit/624872e))
-   fix: handle dynamic form IDs in Bizum create_hidden_input ([bd25b6b](https://github.com/MONEI/MONEI-WooCommerce/commit/bd25b6b))
-   fix: handle error objects properly in classic checkout and hooks ([fee6b06](https://github.com/MONEI/MONEI-WooCommerce/commit/fee6b06))
-   fix: harden amount validation to prevent replay attacks ([26b9a35](https://github.com/MONEI/MONEI-WooCommerce/commit/26b9a35))
-   fix: hide description in component mode for Bizum Classic checkout ([074b5c0](https://github.com/MONEI/MONEI-WooCommerce/commit/074b5c0))
-   fix: hide description in component mode for CC Blocks checkout ([bea5f04](https://github.com/MONEI/MONEI-WooCommerce/commit/bea5f04))
-   fix: improve Apple/Google Pay title hiding and standardize settings field order ([435162b](https://github.com/MONEI/MONEI-WooCommerce/commit/435162b))
-   fix: improve payment component re-initialization and code quality ([eaf9107](https://github.com/MONEI/MONEI-WooCommerce/commit/eaf9107))
-   fix: improve payment method description field behavior and consistency ([32cb917](https://github.com/MONEI/MONEI-WooCommerce/commit/32cb917))
-   fix: improve payment method label spacing ([1ef97b6](https://github.com/MONEI/MONEI-WooCommerce/commit/1ef97b6))
-   fix: improve spacing and layout in monei-label-container ([92f8094](https://github.com/MONEI/MONEI-WooCommerce/commit/92f8094))
-   fix: migrate onCheckoutSuccess to async/await pattern with proper response objects ([c1b4a38](https://github.com/MONEI/MONEI-WooCommerce/commit/c1b4a38))
-   fix: move MONEI_MAIN_FILE constant to bootstrap file and fix type hints ([953cdab](https://github.com/MONEI/MONEI-WooCommerce/commit/953cdab))
-   fix: move PHPStan to pre-commit to catch errors immediately ([c370b92](https://github.com/MONEI/MONEI-WooCommerce/commit/c370b92))
-   fix: prevent blocks detection from blocking scripts on order-pay pages ([4fb3443](https://github.com/MONEI/MONEI-WooCommerce/commit/4fb3443))
-   fix: prevent classic checkout CSS from loading on blocks checkout ([0f25185](https://github.com/MONEI/MONEI-WooCommerce/commit/0f25185))
-   fix: prevent race conditions in payment processing with atomic locks ([8561db1](https://github.com/MONEI/MONEI-WooCommerce/commit/8561db1))
-   fix: properly format card gateway description in redirect mode ([30adf5d](https://github.com/MONEI/MONEI-WooCommerce/commit/30adf5d))
-   fix: refactor Apple/Google Pay component and fix React hooks violations ([e9bb3ef](https://github.com/MONEI/MONEI-WooCommerce/commit/e9bb3ef))
-   fix: resolve all PHPStan type safety errors ([f36f8c5](https://github.com/MONEI/MONEI-WooCommerce/commit/f36f8c5))
-   fix: resolve conflicting CSS margin/padding properties ([c7fabb9](https://github.com/MONEI/MONEI-WooCommerce/commit/c7fabb9))
-   fix: resolve infinite render loop and tokenization checkbox issues ([2a894d5](https://github.com/MONEI/MONEI-WooCommerce/commit/2a894d5))
-   fix: resolve order-pay page issues for all payment methods ([8aa2787](https://github.com/MONEI/MONEI-WooCommerce/commit/8aa2787))
-   fix: resolve PHPCS security warnings ([4d2665f](https://github.com/MONEI/MONEI-WooCommerce/commit/4d2665f))
-   fix: resolve redirect mode and race condition issues for Bizum/PayPal ([dd538d9](https://github.com/MONEI/MONEI-WooCommerce/commit/dd538d9))
-   fix: stabilize React hooks and fix function initialization order ([02ed272](https://github.com/MONEI/MONEI-WooCommerce/commit/02ed272))
-   fix: stabilize React hooks to prevent excessive re-renders ([0e40a91](https://github.com/MONEI/MONEI-WooCommerce/commit/0e40a91))
-   fix: standardize payment method labels and configure ESLint ([7f2cf64](https://github.com/MONEI/MONEI-WooCommerce/commit/7f2cf64))
-   fix: standardize redirect mode field names across payment methods ([9f9c47a](https://github.com/MONEI/MONEI-WooCommerce/commit/9f9c47a))
-   fix: update payment request amounts on cart changes in blocks checkout ([13e7fa4](https://github.com/MONEI/MONEI-WooCommerce/commit/13e7fa4))
-   fix: use correct option key for order completion setting in redirect ([d9d2c41](https://github.com/MONEI/MONEI-WooCommerce/commit/d9d2c41))
-   fix: use custom overlay class to prevent WooCommerce spinner ([c6d7deb](https://github.com/MONEI/MONEI-WooCommerce/commit/c6d7deb))
-   fix: wrap redirect description in div for proper rendering in classic checkout ([3c29598](https://github.com/MONEI/MONEI-WooCommerce/commit/3c29598))
-   feat: add (Test Mode) suffix to payment method titles in checkout ([4dcfffd](https://github.com/MONEI/MONEI-WooCommerce/commit/4dcfffd))
-   feat: add dynamic card brand icons to credit card payment method ([a9850a7](https://github.com/MONEI/MONEI-WooCommerce/commit/a9850a7))
-   feat: add extensive debug logging to Apple Pay domain registration ([362a39c](https://github.com/MONEI/MONEI-WooCommerce/commit/362a39c))
-   feat: add hide title option for all payment methods ([3f3315d](https://github.com/MONEI/MONEI-WooCommerce/commit/3f3315d))
-   feat: add internationalization support with 13 languages ([3ed2918](https://github.com/MONEI/MONEI-WooCommerce/commit/3ed2918))
-   feat: add method description to Apple/Google Pay gateway ([a78995b](https://github.com/MONEI/MONEI-WooCommerce/commit/a78995b))
-   feat: add PHPStan static analysis and PayPal classic mode ([837b0d7](https://github.com/MONEI/MONEI-WooCommerce/commit/837b0d7))
-   feat: add Prettier code formatter integration ([28d0bf1](https://github.com/MONEI/MONEI-WooCommerce/commit/28d0bf1))
-   feat: add separate titles for Apple Pay and Google Pay with conditional display ([9fb5bec](https://github.com/MONEI/MONEI-WooCommerce/commit/9fb5bec))
-   feat: add skeleton loading for payment request components ([c8bf857](https://github.com/MONEI/MONEI-WooCommerce/commit/c8bf857))
-   feat: add user-friendly localized error messages ([8d544ae](https://github.com/MONEI/MONEI-WooCommerce/commit/8d544ae))
-   feat: auto-format JSON style settings on save ([0e1dfe6](https://github.com/MONEI/MONEI-WooCommerce/commit/0e1dfe6))
-   feat: display payment method label in admin and customer views ([55d0811](https://github.com/MONEI/MONEI-WooCommerce/commit/55d0811))
-   feat: enhance IPN webhook handler with enterprise-grade reliability ([4f3628c](https://github.com/MONEI/MONEI-WooCommerce/commit/4f3628c))
-   feat: implement log level system with performance optimizations ([7664d63](https://github.com/MONEI/MONEI-WooCommerce/commit/7664d63))
-   feat: improve settings descriptions and UI consistency ([4386c2a](https://github.com/MONEI/MONEI-WooCommerce/commit/4386c2a))
-   feat: move orderdo and pre-authorize to global settings ([b2159c4](https://github.com/MONEI/MONEI-WooCommerce/commit/b2159c4))
-   feat: show payment method descriptions only in redirect mode ([2fce098](https://github.com/MONEI/MONEI-WooCommerce/commit/2fce098))
-   feat: show Test account badge consistently for all payment methods ([4f958e2](https://github.com/MONEI/MONEI-WooCommerce/commit/4f958e2))
-   feat: standardize payment method descriptions ([d2d0cd8](https://github.com/MONEI/MONEI-WooCommerce/commit/d2d0cd8))
-   feat: update default PayPal style to include disableMaxWidth ([24ef194](https://github.com/MONEI/MONEI-WooCommerce/commit/24ef194))
-   refactor: clean up Apple Pay domain registration debug logging ([134f866](https://github.com/MONEI/MONEI-WooCommerce/commit/134f866))
-   refactor: configure PHPStan to scan actual includes files instead of stubs ([53db43d](https://github.com/MONEI/MONEI-WooCommerce/commit/53db43d))
-   refactor: convert Bizum/PayPal classic params to camelCase ([ac52d42](https://github.com/MONEI/MONEI-WooCommerce/commit/ac52d42))
-   refactor: extract common instance creation logic in PayPal and Bizum components ([a81eac4](https://github.com/MONEI/MONEI-WooCommerce/commit/a81eac4))
-   refactor: fix CSS class naming and remove duplicate method ([ea72233](https://github.com/MONEI/MONEI-WooCommerce/commit/ea72233))
-   refactor: improve Apple Pay / Google Pay naming ([cbb1556](https://github.com/MONEI/MONEI-WooCommerce/commit/cbb1556))
-   refactor: improve button state management and clean up CSS ([e2f74d9](https://github.com/MONEI/MONEI-WooCommerce/commit/e2f74d9))
-   refactor: remove duplicate method and overly broad event handler ([33371d3](https://github.com/MONEI/MONEI-WooCommerce/commit/33371d3))
-   refactor: remove locking mechanism and idempotency flag ([0109306](https://github.com/MONEI/MONEI-WooCommerce/commit/0109306))
-   refactor: reorder settings fields to place description after redirect mode ([f8fd9b5](https://github.com/MONEI/MONEI-WooCommerce/commit/f8fd9b5))
-   refactor: separate classic and blocks checkout CSS files ([aaa14b6](https://github.com/MONEI/MONEI-WooCommerce/commit/aaa14b6))
-   refactor: standardize all blocks params to camelCase ([7eab4e3](https://github.com/MONEI/MONEI-WooCommerce/commit/7eab4e3))
-   refactor: standardize all localized params to camelCase ([eda9920](https://github.com/MONEI/MONEI-WooCommerce/commit/eda9920))
-   refactor: streamline payment method initialization and enhance error handling ([9c04008](https://github.com/MONEI/MONEI-WooCommerce/commit/9c04008))
-   refactor: use React state for error handling in blocks payment methods ([a825329](https://github.com/MONEI/MONEI-WooCommerce/commit/a825329))
-   docs: add critical warning against using --no-verify ([ebe46bd](https://github.com/MONEI/MONEI-WooCommerce/commit/ebe46bd))
-   style: align card brand icons to the right on mobile ([34b67cd](https://github.com/MONEI/MONEI-WooCommerce/commit/34b67cd))
-   style: make card brand icons responsive with flex-wrap ([903f01c](https://github.com/MONEI/MONEI-WooCommerce/commit/903f01c))
-   style: normalize CSS units to use em instead of px ([3fd55a1](https://github.com/MONEI/MONEI-WooCommerce/commit/3fd55a1))
-   style: prevent payment method title text from wrapping ([9267c10](https://github.com/MONEI/MONEI-WooCommerce/commit/9267c10))
-   Removed lock and \_monei_payment_id_processed flag
Analysis revealed WooCommerce creates orders BEFORE payment (unlike PrestaShop),
so duplicate order creation is impossible. The lock and processed flag were:
1. Broken - wp_cache not persistent without external cache
2. Harmful - flag blocked AUTHORIZED→SUCCEEDED and SUCCEEDED→REFUNDED transitions
3. Unnecessary - WooCommerce's payment_complete() is already idempotent
Removed components:
-   WC_Monei_Lock_Helper class
-   Lock acquisition/release in IPN and redirect handlers
-   \_monei_payment_id_processed flag checks and setting
-   wp_cache stubs from PHPStan bootstrap
The order status check provides sufficient protection against duplicate processing.
Any duplicate order notes are cosmetic and acceptable.

= v6.4.0 - 2025-10-01 =
-   feat: add custom readme generator to show latest 10 releases ([371e09c](https://github.com/MONEI/MONEI-WooCommerce/commit/371e09c))
-   feat: configure GitHub release notes with conventional changelog ([226db8f](https://github.com/MONEI/MONEI-WooCommerce/commit/226db8f))
-   chore: remove unused generate-wp-readme package ([4e06b1b](https://github.com/MONEI/MONEI-WooCommerce/commit/4e06b1b))
-   chore: update CHANGELOG.md with corrected tag hash ([f9b0dfa](https://github.com/MONEI/MONEI-WooCommerce/commit/f9b0dfa))
-   fix: add changelog length limit to show all versions ([c135b7c](https://github.com/MONEI/MONEI-WooCommerce/commit/c135b7c))
-   fix: correct changelog template to show actual 6.3.8 release ([0efe693](https://github.com/MONEI/MONEI-WooCommerce/commit/0efe693))
-   fix: limit changelog to last 10 releases ([1a3f468](https://github.com/MONEI/MONEI-WooCommerce/commit/1a3f468))
-   fix: normalize changelog chronological order ([a3b1d8a](https://github.com/MONEI/MONEI-WooCommerce/commit/a3b1d8a))
-   fix: show all changelog versions, remove manual entries ([dbd53a1](https://github.com/MONEI/MONEI-WooCommerce/commit/dbd53a1))