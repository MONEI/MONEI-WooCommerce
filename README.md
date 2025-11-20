# MONEI Payments for WooCommerce
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 7.0.2
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 9.8

Accept Card, Apple Pay, Google Pay, Bizum, PayPal and many more payment methods in your WooCommerce store using MONEI payment gateway.

## Description

### ACCEPT ONLINE PAYMENTS WITH MONEI
MONEI is an e-commerce payment gateway for WooCommerce (and other e-commerce platforms).


Its payment gateway is the choice of many Spain and Andorra based e-commerce businesses. Use MONEI's technology to accept and manage all major and alternative payment methods in a single platform.


MONEI is dedicated to helping you simplify your digital payments so you can focus on growing your online business.

### PAYMENT METHODS
Use MONEI's payment gateway to accept debit and credit card payments from customers worldwide in 230+ currencies.


Let shoppers pay from the convenience of their smartphone with digital wallets like Apple Pay, Google Pay, and PayPal. And accept local payment methods such as Bizum (Spain) and SEPA Direct Debit (EU).


Offering customers [many payment methods](https://monei.com/es/online-payment-methods/) leads to an increase in sales and customer satisfaction. 🚀

### WHY TO USE MONEI'S PAYMENT PLUGIN FOR WOOCOMMERCE

MONEI's serverless architecture allows you to scale and process a high volume of transactions. Its dynamic pricing model means as you sell more your transaction fees decrease. Once you're an approved merchant, enjoy 1-day payment settlements.


Payment security is crucial. MONEI is PCI DSS compliant, 3D Secure, and uses payment tokenization to make sure sensitive payment information is never compromised.


Connect your custom domain to MONEI and customize the appearance of your checkout page to build trust and brand awareness.


With MONEI's payment gateway for e-commerce, get real-time sales analytics via your customer dashboard.


Please go to the 👉 [signup page](https://dashboard.monei.com/?action=signUp) 👈 to create a new MONEI account. Contact support@monei.com if you have any questions or feedback about this plugin.


### PAYMENT GATEWAY FEATURES
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


### GETTING STARTED WITH MONEI
1. How do I open my MONEI account so I can plug in with WooCommerce?
Learn how to [get started with MONEI here ››](https://support.monei.com/hc/en-us/articles/360017801677-Get-started-with-MONEI)
2. What countries does MONEI support?
Currently, MONEI is available in Spain and Andorra, but our global expansion is happening fast. [Join our newsletter here](https://client.moonmail.io/ac8e391c-8cfb-46e3-aed9-e7a84d0fd830/forms/6bafcdbf-442a-4e3b-874f-7e2ed30ee001) to get notified once we support your country!
3. I have different questions about this plugin.
Please contact support@monei.com with your MONEI ID. Describe your problem in detail and include screenshots when necessary.

## Installation
* Go to wp-admin > Plugins
* Click Add new
* Search for MONEI
* Press Install
* Press Activate now
* Go to WooCommerce > Settings > Payments > MONEI
* Add your API Key.

### If you don't have API Key

* Go to [MONEI Dashboard > Settings > API Access](https://dashboard.monei.com/settings/api)
* Click on "Create API Key"

### Use of 3rd Party Services
This plugin is using [MONEI API](https://docs.monei.com/api/) to process payments as well as
[MONEI UI Components](https://docs.monei.com/docs/monei-js/overview/) to securely collect sensitive payment information during checkout.

By using this plugin you agree with MONEI [Terms of Service](https://monei.com/legal-notice/) and [Privacy Policy](https://monei.com/privacy-policy/)

## Screenshots

1. Apple Pay, Bizum, PayPal, credit Card
2. Google Pay, Bizum, PayPal, credit Card

## Changelog

### v7.0.2 - 2025-11-20
-   fix: prevent wp_sanitize_redirect from stripping domain in payment URLs ([a982699](https://github.com/MONEI/MONEI-WooCommerce/commit/a982699))

### v7.0.1 - 2025-10-14
-   fix: upgrade to PHP 8.0+ to resolve PHP-DI compatibility issue ([95f9ffd](https://github.com/MONEI/MONEI-WooCommerce/commit/95f9ffd))
-   PHP 7.4 users cannot upgrade. PHP 8.0 is now the
    minimum required version. PHP 7.4 reached end-of-life in November
2022. WordPress 6.8 officially supports PHP 8.0-8.3.

### v7.0.0 - 2025-10-10
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

### v6.4.0 - 2025-10-01
-   feat: add custom readme generator to show latest 10 releases ([371e09c](https://github.com/MONEI/MONEI-WooCommerce/commit/371e09c))
-   feat: configure GitHub release notes with conventional changelog ([226db8f](https://github.com/MONEI/MONEI-WooCommerce/commit/226db8f))
-   chore: remove unused generate-wp-readme package ([4e06b1b](https://github.com/MONEI/MONEI-WooCommerce/commit/4e06b1b))
-   chore: update CHANGELOG.md with corrected tag hash ([f9b0dfa](https://github.com/MONEI/MONEI-WooCommerce/commit/f9b0dfa))
-   fix: add changelog length limit to show all versions ([c135b7c](https://github.com/MONEI/MONEI-WooCommerce/commit/c135b7c))
-   fix: correct changelog template to show actual 6.3.8 release ([0efe693](https://github.com/MONEI/MONEI-WooCommerce/commit/0efe693))
-   fix: limit changelog to last 10 releases ([1a3f468](https://github.com/MONEI/MONEI-WooCommerce/commit/1a3f468))
-   fix: normalize changelog chronological order ([a3b1d8a](https://github.com/MONEI/MONEI-WooCommerce/commit/a3b1d8a))
-   fix: show all changelog versions, remove manual entries ([dbd53a1](https://github.com/MONEI/MONEI-WooCommerce/commit/dbd53a1))

### v6.3.12 - 2025-10-01
-   fix: add changelog length limit to show all versions ([c135b7c](https://github.com/MONEI/MONEI-WooCommerce/commit/c135b7c))
-   fix: correct changelog template to show actual 6.3.8 release ([0efe693](https://github.com/MONEI/MONEI-WooCommerce/commit/0efe693))
-   fix: limit changelog to last 10 releases ([1a3f468](https://github.com/MONEI/MONEI-WooCommerce/commit/1a3f468))
-   fix: normalize changelog chronological order ([a3b1d8a](https://github.com/MONEI/MONEI-WooCommerce/commit/a3b1d8a))
-   chore: update CHANGELOG.md with corrected tag hash ([f9b0dfa](https://github.com/MONEI/MONEI-WooCommerce/commit/f9b0dfa))

### v6.3.9 - 2025-10-01
-   Fix amount when checkout data is updated ([2013a03](https://github.com/MONEI/MONEI-WooCommerce/commit/2013a03))
-   Fix card input style ([6c12a5a](https://github.com/MONEI/MONEI-WooCommerce/commit/6c12a5a))
-   Remove minified assets from vcs ([5a6fd99](https://github.com/MONEI/MONEI-WooCommerce/commit/5a6fd99))
-   Update monei sdk ([38a134a](https://github.com/MONEI/MONEI-WooCommerce/commit/38a134a))
-   Update setUserAgent to include comment ([f6d85df](https://github.com/MONEI/MONEI-WooCommerce/commit/f6d85df))
-   chore: add auto-generated CHANGELOG.md ([50e9983](https://github.com/MONEI/MONEI-WooCommerce/commit/50e9983))
-   chore: auto-remove README.md after generation ([b299478](https://github.com/MONEI/MONEI-WooCommerce/commit/b299478))
-   chore: modernize build and release pipeline ([21384f0](https://github.com/MONEI/MONEI-WooCommerce/commit/21384f0))
-   chore: remove redundant changelog.txt ([1703044](https://github.com/MONEI/MONEI-WooCommerce/commit/1703044))
-   chore: remove unnecessary README.md auto-deletion ([86c727e](https://github.com/MONEI/MONEI-WooCommerce/commit/86c727e))
-   chore: setup automated changelog generation ([e83b384](https://github.com/MONEI/MONEI-WooCommerce/commit/e83b384))
-   fix: properly configure changelog generation with placeholder ([2cefc8c](https://github.com/MONEI/MONEI-WooCommerce/commit/2cefc8c))
-   fix: remove version limit from changelog generation ([cfe33a3](https://github.com/MONEI/MONEI-WooCommerce/commit/cfe33a3))
-   fix: run changelog generation after tag creation ([f9aedb5](https://github.com/MONEI/MONEI-WooCommerce/commit/f9aedb5))
-   fix: specify main plugin file for generate-wp-readme ([f93edd3](https://github.com/MONEI/MONEI-WooCommerce/commit/f93edd3))
-   fix: update 6.3.9 changelog entry with correct date and content ([6050b35](https://github.com/MONEI/MONEI-WooCommerce/commit/6050b35))
-   refactor: move release-it config to separate file ([18bf445](https://github.com/MONEI/MONEI-WooCommerce/commit/18bf445))
-   docs: document changelog generation system ([3217a25](https://github.com/MONEI/MONEI-WooCommerce/commit/3217a25))

### v6.3.8 - 2025-09-10
-   Add 3ds credit card automated tests ([0c7faf9](https://github.com/MONEI/MONEI-WooCommerce/commit/0c7faf9))
-   Add api key and method visibility tests ([cf6615a](https://github.com/MONEI/MONEI-WooCommerce/commit/cf6615a))
-   Add Bizum processor ([d266a94](https://github.com/MONEI/MONEI-WooCommerce/commit/d266a94))
-   Add bizum success and fail ([80909a4](https://github.com/MONEI/MONEI-WooCommerce/commit/80909a4))
-   Add cc vaulting tests ([a955cb4](https://github.com/MONEI/MONEI-WooCommerce/commit/a955cb4))
-   Add data-testid ([11abfd9](https://github.com/MONEI/MONEI-WooCommerce/commit/11abfd9))
-   Add e2e tests for transactions ([ca8c7c5](https://github.com/MONEI/MONEI-WooCommerce/commit/ca8c7c5))
-   Add google tests ([ceab68d](https://github.com/MONEI/MONEI-WooCommerce/commit/ceab68d))
-   Add missing space in webhook notice ([4d4a5a1](https://github.com/MONEI/MONEI-WooCommerce/commit/4d4a5a1))
-   Add order to clean up ([0f6d32e](https://github.com/MONEI/MONEI-WooCommerce/commit/0f6d32e))
-   add pay-order-page tests ([1083afc](https://github.com/MONEI/MONEI-WooCommerce/commit/1083afc))
-   Add PayPal processor tests ([8ced045](https://github.com/MONEI/MONEI-WooCommerce/commit/8ced045))
-   Add settings shortcut to plugins page ([dbcd179](https://github.com/MONEI/MONEI-WooCommerce/commit/dbcd179))
-   Add transaction component no 3ds working ([3a3f6ff](https://github.com/MONEI/MONEI-WooCommerce/commit/3a3f6ff))
-   Add transaction hosted working ([51330f9](https://github.com/MONEI/MONEI-WooCommerce/commit/51330f9))
-   Add user setup ([54fe52e](https://github.com/MONEI/MONEI-WooCommerce/commit/54fe52e))
-   Call hook directly ([fe83d7e](https://github.com/MONEI/MONEI-WooCommerce/commit/fe83d7e))
-   Extract method ([6485670](https://github.com/MONEI/MONEI-WooCommerce/commit/6485670))
-   Fix incorrect method call and ignored return value ([898c83d](https://github.com/MONEI/MONEI-WooCommerce/commit/898c83d))
-   Fix pages and product creation ([3846588](https://github.com/MONEI/MONEI-WooCommerce/commit/3846588))
-   Global setup create products ([3a8e0ef](https://github.com/MONEI/MONEI-WooCommerce/commit/3a8e0ef))
-   Improve token creation ([7857d47](https://github.com/MONEI/MONEI-WooCommerce/commit/7857d47))
-   Log in case of error ([14380b8](https://github.com/MONEI/MONEI-WooCommerce/commit/14380b8))
-   Migrate keys in case no credit card setting was saved ([0f9efa0](https://github.com/MONEI/MONEI-WooCommerce/commit/0f9efa0))
-   Refactor apple-google and cc scripts into react components ([fda37d4](https://github.com/MONEI/MONEI-WooCommerce/commit/fda37d4))
-   Refactor ApplePay and GooglePay into separate gateway ([44fa266](https://github.com/MONEI/MONEI-WooCommerce/commit/44fa266))
-   Refactor to reduce db calls ([1b1432d](https://github.com/MONEI/MONEI-WooCommerce/commit/1b1432d))
-   Remove automated tests from this PR ([302c9af](https://github.com/MONEI/MONEI-WooCommerce/commit/302c9af))
-   Remove log and follow convention ([ee74140](https://github.com/MONEI/MONEI-WooCommerce/commit/ee74140))
-   remove logs ([9ca86e9](https://github.com/MONEI/MONEI-WooCommerce/commit/9ca86e9))
-   Remove looking into settings again when updating keys ([e484889](https://github.com/MONEI/MONEI-WooCommerce/commit/e484889))
-   Remove old payment methods transients on activation and update ([c1cbad1](https://github.com/MONEI/MONEI-WooCommerce/commit/c1cbad1))
-   Revert version to 6.3.6 in package.json ([6edd048](https://github.com/MONEI/MONEI-WooCommerce/commit/6edd048))
-   Set user agent in client after instantiation ([a23d91c](https://github.com/MONEI/MONEI-WooCommerce/commit/a23d91c))
-   Update after:bump hook in package.json to remove build command ([c1d8f31](https://github.com/MONEI/MONEI-WooCommerce/commit/c1d8f31))
-   Update changelog ([544f709](https://github.com/MONEI/MONEI-WooCommerce/commit/544f709))
-   Update changelog ([4279361](https://github.com/MONEI/MONEI-WooCommerce/commit/4279361))
-   Update dependencies ([d1d8323](https://github.com/MONEI/MONEI-WooCommerce/commit/d1d8323))
-   Update package manager version ([ab66343](https://github.com/MONEI/MONEI-WooCommerce/commit/ab66343))
-   Update package version to 6.3.6 ([859bde9](https://github.com/MONEI/MONEI-WooCommerce/commit/859bde9))
-   Update plugin version for 6.3.7 release ([f00178c](https://github.com/MONEI/MONEI-WooCommerce/commit/f00178c))
-   Update tests ([6626d08](https://github.com/MONEI/MONEI-WooCommerce/commit/6626d08))
-   Update tests ([116fbfb](https://github.com/MONEI/MONEI-WooCommerce/commit/116fbfb))
-   Update to 6.3.6 version for release ([e60b6ac](https://github.com/MONEI/MONEI-WooCommerce/commit/e60b6ac))
-   Update version number ([4bb2309](https://github.com/MONEI/MONEI-WooCommerce/commit/4bb2309))
-   Update version number ([9966921](https://github.com/MONEI/MONEI-WooCommerce/commit/9966921))
-   Update version number ([1276822](https://github.com/MONEI/MONEI-WooCommerce/commit/1276822))
-   Update version to 6.3.7 in readme and package.json ([279670b](https://github.com/MONEI/MONEI-WooCommerce/commit/279670b))
-   Uppercase Key in API Key ([1d263b1](https://github.com/MONEI/MONEI-WooCommerce/commit/1d263b1))
-   Use rounding ([cb79abd](https://github.com/MONEI/MONEI-WooCommerce/commit/cb79abd))
-   Use Woo api client ([9c5362d](https://github.com/MONEI/MONEI-WooCommerce/commit/9c5362d))

### v6.3.5 - 2025-06-04
-   Add 30 seconds caching ([73a4d1a](https://github.com/MONEI/MONEI-WooCommerce/commit/73a4d1a))
-   Change payment methods check to sdk ([5e045eb](https://github.com/MONEI/MONEI-WooCommerce/commit/5e045eb))
-   Remove cofidis ([fef0d3b](https://github.com/MONEI/MONEI-WooCommerce/commit/fef0d3b))
-   Require php 7.4 for the package ([841acfb](https://github.com/MONEI/MONEI-WooCommerce/commit/841acfb))
-   Update version to 6.3.5 for release ([ba2437a](https://github.com/MONEI/MONEI-WooCommerce/commit/ba2437a))

### v6.3.4 - 2025-05-30
-   Copy old keys only when no new keys are there ([14b066f](https://github.com/MONEI/MONEI-WooCommerce/commit/14b066f))
-   Declare $handler to avoid dynamic-property deprecation ([0a4aa60](https://github.com/MONEI/MONEI-WooCommerce/commit/0a4aa60))
-   Delete old key options ([131f7f8](https://github.com/MONEI/MONEI-WooCommerce/commit/131f7f8))
-   Do not load script if there is redirect flow setting ([64f7135](https://github.com/MONEI/MONEI-WooCommerce/commit/64f7135))
-   Do not load script if there is redirect flow setting ([0265b73](https://github.com/MONEI/MONEI-WooCommerce/commit/0265b73))
-   Fix live account description ([aa3005c](https://github.com/MONEI/MONEI-WooCommerce/commit/aa3005c))
-   Fix subscription check when no subscription present ([c23050e](https://github.com/MONEI/MONEI-WooCommerce/commit/c23050e))
-   Get correct account id for classic checkout ([865d23d](https://github.com/MONEI/MONEI-WooCommerce/commit/865d23d))
-   Remove bizum and google/apple when subs ([b4c7df6](https://github.com/MONEI/MONEI-WooCommerce/commit/b4c7df6))
-   Remove redundant parameter() call and simplify factory ([404e237](https://github.com/MONEI/MONEI-WooCommerce/commit/404e237))
-   Return boolean when cart has subscription with yith ([5852018](https://github.com/MONEI/MONEI-WooCommerce/commit/5852018))
-   Send correct token to PayPal component ([d0c74fa](https://github.com/MONEI/MONEI-WooCommerce/commit/d0c74fa))
-   Show API key settings button even no gateway available ([fdec15c](https://github.com/MONEI/MONEI-WooCommerce/commit/fdec15c))
-   Show CC when subscription in Block ([2f851a5](https://github.com/MONEI/MONEI-WooCommerce/commit/2f851a5))
-   Update changelog, readme and version ([474c3c6](https://github.com/MONEI/MONEI-WooCommerce/commit/474c3c6))
-   Update date for release ([b2182d5](https://github.com/MONEI/MONEI-WooCommerce/commit/b2182d5))
-   Update readme ([0432ba0](https://github.com/MONEI/MONEI-WooCommerce/commit/0432ba0))
-   Update readme ([91ac9bc](https://github.com/MONEI/MONEI-WooCommerce/commit/91ac9bc))
-   Update tested version ([6138a3a](https://github.com/MONEI/MONEI-WooCommerce/commit/6138a3a))
-   Update version for release 6.3.4 ([636bbda](https://github.com/MONEI/MONEI-WooCommerce/commit/636bbda))
-   Update version to 6.3.3 ([0e0c71a](https://github.com/MONEI/MONEI-WooCommerce/commit/0e0c71a))
-   Use central API key for PayPal method ([0132a7c](https://github.com/MONEI/MONEI-WooCommerce/commit/0132a7c))
-   Use different accountId depending on selector ([712c295](https://github.com/MONEI/MONEI-WooCommerce/commit/712c295))
-   Use empty string if API option is missing ([74d88ca](https://github.com/MONEI/MONEI-WooCommerce/commit/74d88ca))

### v6.3.1 - 2025-04-24
-   Bail on renewal if already processing ([718bc42](https://github.com/MONEI/MONEI-WooCommerce/commit/718bc42))
-   Fix change payment method in my account ([48e2f07](https://github.com/MONEI/MONEI-WooCommerce/commit/48e2f07))
-   Fix CS ([b84f8ed](https://github.com/MONEI/MONEI-WooCommerce/commit/b84f8ed))
-   Refactor to integrate with YITH subscriptions ([d94ea68](https://github.com/MONEI/MONEI-WooCommerce/commit/d94ea68))
-   Update to release version to 6.3.0 ([790b5f6](https://github.com/MONEI/MONEI-WooCommerce/commit/790b5f6))
-   Use 2 API keys ([97fdd93](https://github.com/MONEI/MONEI-WooCommerce/commit/97fdd93))