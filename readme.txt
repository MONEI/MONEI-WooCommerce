=== MONEI Payments for WooCommerce ===
Tags: woocommerce, credit card, payment gateway, payments, ecommerce
Contributors: monei, furi3r
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 6.3.11
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

= v6.3.11 - 2025-10-01 =
* fix: correct changelog template to show actual 6.3.8 release (0efe693)
* chore: release v6.3.11 (86d825a)
* chore: update CHANGELOG.md with corrected tag hash (f9b0dfa)


= v6.3.9 - 2025-10-01 =
* Fix amount when checkout data is updated (2013a03)
* Fix card input style (6c12a5a)
* Remove minified assets from vcs (5a6fd99)
* Update monei sdk (38a134a)
* Update setUserAgent to include comment (f6d85df)
* chore: add auto-generated CHANGELOG.md (50e9983)
* chore: auto-remove README.md after generation (b299478)
* chore: modernize build and release pipeline (21384f0)
* chore: release v6.3.9 (79b2f41)
* chore: remove redundant changelog.txt (1703044)
* chore: remove unnecessary README.md auto-deletion (86c727e)
* chore: setup automated changelog generation (e83b384)
* fix: properly configure changelog generation with placeholder (2cefc8c)
* fix: remove version limit from changelog generation (cfe33a3)
* fix: run changelog generation after tag creation (f9aedb5)
* fix: specify main plugin file for generate-wp-readme (f93edd3)
* fix: update 6.3.9 changelog entry with correct date and content (6050b35)
* refactor: move release-it config to separate file (18bf445)
* docs: document changelog generation system (3217a25)


= v6.3.8 - 2025-09-10 =
* Add 3ds credit card automated tests (0c7faf9)
* Add api key and method visibility tests (cf6615a)
* Add Bizum processor (d266a94)
* Add bizum success and fail (80909a4)
* Add cc vaulting tests (a955cb4)
* Add data-testid (11abfd9)
* Add e2e tests for transactions (ca8c7c5)
* Add google tests (ceab68d)
* Add missing space in webhook notice (4d4a5a1)
* Add order to clean up (0f6d32e)
* add pay-order-page tests (1083afc)
* Add PayPal processor tests (8ced045)
* Add settings shortcut to plugins page (dbcd179)
* Add transaction component no 3ds working (3a3f6ff)
* Add transaction hosted working (51330f9)
* Add user setup (54fe52e)
* Call hook directly (fe83d7e)
* Extract method (6485670)
* Fix incorrect method call and ignored return value (898c83d)
* Fix pages and product creation (3846588)
* Global setup create products (3a8e0ef)
* Improve token creation (7857d47)
* Log in case of error (14380b8)
* Migrate keys in case no credit card setting was saved (0f9efa0)
* Refactor apple-google and cc scripts into react components (fda37d4)
* Refactor ApplePay and GooglePay into separate gateway (44fa266)
* Refactor to reduce db calls (1b1432d)
* Remove automated tests from this PR (302c9af)
* Remove log and follow convention (ee74140)
* remove logs (9ca86e9)
* Remove looking into settings again when updating keys (e484889)
* Remove old payment methods transients on activation and update (c1cbad1)
* Revert version to 6.3.6 in package.json (6edd048)
* Set user agent in client after instantiation (a23d91c)
* Update after:bump hook in package.json to remove build command (c1d8f31)
* Update changelog (544f709)
* Update changelog (4279361)
* Update dependencies (d1d8323)
* Update package manager version (ab66343)
* Update package version to 6.3.6 (859bde9)
* Update plugin version for 6.3.7 release (f00178c)
* Update tests (6626d08)
* Update tests (116fbfb)
* Update to 6.3.6 version for release (e60b6ac)
* Update version number (4bb2309)
* Update version number (9966921)
* Update version number (1276822)
* Update version to 6.3.7 in readme and package.json (279670b)
* Uppercase Key in API Key (1d263b1)
* Use rounding (cb79abd)
* Use Woo api client (9c5362d)
* chore: release v6.3.8 (9bed803)


= v6.3.5 - 2025-06-04 =
* Add 30 seconds caching (73a4d1a)
* Change payment methods check to sdk (5e045eb)
* Remove cofidis (fef0d3b)
* Require php 7.4 for the package (841acfb)
* Update version to 6.3.5 for release (ba2437a)


= v6.3.4 - 2025-05-30 =
* Copy old keys only when no new keys are there (14b066f)
* Declare $handler to avoid dynamic-property deprecation (0a4aa60)
* Delete old key options (131f7f8)
* Do not load script if there is redirect flow setting (64f7135)
* Do not load script if there is redirect flow setting (0265b73)
* Fix live account description (aa3005c)
* Fix subscription check when no subscription present (c23050e)
* Get correct account id for classic checkout (865d23d)
* Remove bizum and google/apple when subs (b4c7df6)
* Remove redundant parameter() call and simplify factory (404e237)
* Return boolean when cart has subscription with yith (5852018)
* Send correct token to PayPal component (d0c74fa)
* Show API key settings button even no gateway available (fdec15c)
* Show CC when subscription in Block (2f851a5)
* Update changelog, readme and version (474c3c6)
* Update date for release (b2182d5)
* Update readme (0432ba0)
* Update readme (91ac9bc)
* Update tested version (6138a3a)
* Update version for release 6.3.4 (636bbda)
* Update version to 6.3.3 (0e0c71a)
* Use central API key for PayPal method (0132a7c)
* Use different accountId depending on selector (712c295)
* Use empty string if API option is missing (74d88ca)


= v6.3.1 - 2025-04-24 =
* Bail on renewal if already processing (718bc42)
* Fix change payment method in my account (48e2f07)
* Fix CS (b84f8ed)
* Refactor to integrate with YITH subscriptions (d94ea68)
* Update to release version to 6.3.0 (790b5f6)
* Use 2 API keys (97fdd93)


= v6.2.1 - 2025-04-07 =
* Modify composer dependency installation (a8082b1)
* Update plugin version (caf01fb)
* Update release action to use composer no-dev (0063b26)
* Update SDK version to V2 (5cc7cb8)
* Use ramsey/composer-install (8927c67)


= v6.2.0 - 2025-02-18 =
* Add autoload and container (eb943be)
* Add notice if gateway disabled in dashboard (2ad3517)
* Add PayPal in blocks (c163d58)
* Add Requires php to readme (51a6877)
* Add services to handle blocks creation (c79e774)
* Add services to handle paymentmethods API call (35174dd)
* Add wp cs standard rules and run cbf (d54055c)
* Bail if no nonce (c260fee)
* Button renders and closes (f460e47)
* Check directory is string before using (aba5560)
* Check file before including (59af5fb)
* Fix card message in hosted (b4fa074)
* Fix CS (19d9441)
* Fix CS (24e498c)
* Fix error when index missing (a5a357e)
* Fix errors (95fb7ff)
* Fix errors and warnings (f5566cc)
* Fix icon url (6f0299a)
* Fix place order button locator (1123995)
* Fix template path error (46071b0)
* Fix webhooks (c10bb15)
* Hide settings tab (d58ed31)
* Import classes (752a907)
* Load css script in admin (f4611f9)
* Move to src folders and standard names (7a24a42)
* Put review link in header (c8e0fe6)
* Remove extra links in banner (cf50738)
* Remove includes and use classes and container (a9c2588)
* Show correct icon w/ apple google (0bf61ec)
* Show method only if enabled (8afcd97)
* Update branch with cs fixes (494ec57)
* Update changelog in dedicated file (a719a6c)
* Update composer to ramain in php7.4 (31c669f)
* Update filter input (b4741ba)
* Update readme and changelog for release (172b629)
* Update version and changelog (dde3109)
* Use correct locator for place order button (abb570d)


= v6.1.2 - 2024-12-26 =
* Add assets to distignore (02644f0)
* Add changelog (50ce762)
* Add translated strings in moneiData (799179a)
* Fix errors (cdd5602)
* Fix strings typo (830cb3d)
* Move images from assets to public (49f8e3f)
* Update plugin version (4300899)
* Update readme (0cb5441)
* Update readme (a1e6914)
* Update stable tag (1f60092)
* Update woo tested version (bd3ed53)
* Update woo tested version (6a09218)


= v6.1.1 - 2024-11-27 =
* Release 6.1.0 (c641eaf)
* Release 6.1.1 (1d845b8)


= v6.0.0 - 2024-11-22 =
* Add an action to create build packages (7fd452c)
* Build assets in main release action (a9243cf)
* change package name to monei (fe96779)
* Do not remove filtered before (1195e2f)
* Fix name in action (16eded5)
* Remove node_modules and filtered from package and rename package (e8d1bbb)
* Update version to 6.0.0 (3eb92f3)


= v5.9.1 - 2024-11-21 =
* Roll back to previous version in .org (fb33d82)


= v5.9.0 - 2024-11-21 =
* Add apple/google logo and title dinamically (5f3b864)
* Add bizum component in classic (698001b)
* Add Bizum payment to blocks (c587968)
* Add block flag to completePayment for bizum (f652de9)
* Add card logo in shortcode checkout (c3d9656)
* Add cardholder name input (c042cf8)
* Add cardholder name to cc payload (758df4e)
* Add fix lint (e5969eb)
* Add Google/Apple payment to blocks (2de8b4a)
* Add header to plugin settings (f07a37c)
* Add link to api key settings from payments (f36f6f3)
* Add logos (d78bd92)
* add monei sdk in backend (5787aec)
* Add plugin settings tab (ad77c12)
* Add strings to translate (97aa498)
* Add styles to components (54c9d7e)
* Add total, currency and language for bizum block (8de2189)
* Add validation for cardholder name (46747c5)
* Add webpack config (7eab06d)
* Add wp scripts to handle assets (e1dc2ea)
* Adda Cart and Checkout Blocks (74bf032)
* Adds Null Coalescing to settings (f36f9f4)
* Allows for translation of Cancelled by MONEI order note (91da56f)
* Bail validation if card input empty (ec6fb64)
* Better variable names and comments for better understanding of JS files (5525402)
* Center logo in blocks (6ad84c7)
* Centralize api keys and log (ca9c5e9)
* Change version number and wc tested (64a3386)
* Check is active on bizum (bb3ac18)
* Confirm payment in client if block (01d70e2)
* Convert amount to cents in payment request (d1a9736)
* Copy apikey from cc so the update is easier (072a638)
* Create no apikey disabled notice (4bb5de0)
* Default checked to "Save payment information " (7a55a57)
* Disable submit button if apple selected (04d5a47)
* Enable the button to click (55609aa)
* Extract Google/Apple to a different component (b0da166)
* file_get_contents() is discouraged. (59fed6b)
* Fix bizum component failing (83bb470)
* Fix bug with redirection to Hosted Page (79aa67f)
* Fix fatal error on new install (9c48602)
* Fix google/apple to start transaction on auth (80c5adb)
* Fix js lint (827435e)
* Fix js lint (68d58c7)
* Fixes for process_payment() (865b494)
* Follow woo to display errors (d232405)
* Follow woo to display errors (1ae8b96)
* Hide bizum if not spain (be70bdd)
* Hide checkout saved cards (8164170)
* Improve css (45fe4da)
* Isolate apple logic in monei script (2b9c5b6)
* Move settings header to the left (8605241)
* Move testmode to central settings (1546f72)
* Only allow to be active if settings correct (f9e9a18)
* Pass language to components (fe7c3b1)
* Redirect to pay page on cc fail (161fa3d)
* Remove helper script (ae9a0f0)
* Remove helper script (874607b)
* Remove logs (6604ef5)
* Remove margin-bottom in request block (b601cfe)
* Remove unused file (02d3517)
* Removes ', you will be redirected to MONEI" (f7473f7)
* Rename and update build assets (2dbffc8)
* return failure to checkout (e1942ee)
* Return whether or not setup is required to function. (d686e48)
* Revert "file_get_contents() is discouraged. Use wp_remote_get() for remote URLs instead." (9692bc9)
* Rewrite cc block script (d5ecb0c)
* Save method token also from blocks (3da8e49)
* Send correct paymentDetails and redirect (9cf3a51)
* Show apple in separated method in classic (fdf6050)
* Show correct notice if apikey not found (3094ec9)
* Show save method selector (7321d31)
* Upadate WC tested up to (a90a4ee)
* Update assets build (f2a379d)
* Update checkout-monei-cc.js (dc1d897)
* Update class-wc-monei-ipn.php (0cd53af)
* Update classic checkout (0bff922)
* update logos (4995a92)
* Update plugin version (25f190a)
* Update styles to follow block checkout (7497a68)
* Update styles to follow block checkout (6371d07)
* Updates WP & WC tested up to headers (a262570)
* Use bizum button component in blocks (2ac2042)
* use central api keys (075572d)
* Use central apikey or bail to cc (a5971cf)
* Use central debug (85c40fa)
* Use centralized api on cc and bizum (cc4752d)
* Use minified script version in public (f4e9bca)
* Use the new GoogleAppleGateway only in blocks (24c2ec0)
* Write Monei all uppercase (a71ab59)
* Chore: release 5.9.0 (27964d2)


= v5.8.13 - 2024-08-29 =
* Add composer.lock to .distignore (a524318)
* add css class to icons (11fd278)
* Add css to frame of buttons (12be5bf)
* Add js observer on retry page to init field on load (c9645fb)
* Add js observer on retry page to init field on load (f41297f)
* Add notice on fail, undo changes on cancel (9e34242)
* Echo url link (d54f881)
* Fix for #56 (420ca38), closes #56
* Fix for #56 (3c5434b), closes #56
* Log responseBody message on cardholder validation failure (282bc24)
* move log condition to class (753f1f2)
* return to pay page on failed and put to pending payment (38ae33c)
* update .gitignore (67845fa)
* Update class-wc-monei-redirect-hooks.php (ae675d3)
* Update failed payment message (679b8e1)
* Update validation rule (73999db)
* Update version and tested versions (8c3da27)
* Update yarn pnp after first build (edf27c1)
* update yarn version (09b070d)
* Use wc validation to show translated errors (1746131)
* chore: release v5.8.13 (30b3fff)


= v5.8.12 - 2024-06-10 =
* Fix webhook validation issue (a1a5e73)
* chore: release v5.8.12 (f3063e8)


= v5.8.11 - 2024-05-30 =
* add WooCommerce HPOS compatibility (0aa738b)
* chore: release v5.8.11 (efa6b4a)


= v5.8.10 - 2024-05-27 =
* update tested up to version (6a589ba)
* chore: release v5.8.10 (c0cb9bd)


= v5.8.9 - 2024-05-22 =
* update apple-developer-merchantid-domain-association (edc594e)
* chore: release v5.8.9 (c375859)


= v5.8.8 - 2024-05-06 =
* Fixes Partial refunds are displayed as full refunds (c66d8e6)
* update changelog (f366ad4)
* update yarn (e6a2f2c)
* chore: release v5.8.8 (05d065b)


= v5.8.7 - 2024-05-03 =
* - update monei-php-sdk to 2.4.0 (d168ca5)
* chore: release v5.8.7 (ce755e4)


= v5.8.6 - 2024-04-16 =
* dont export files (00d19b4)
* dont update body description (65b3e11)
* ignore git folder (b81cde3)
* update tested up to versions (8166e4a)
* chore: release v5.8.6 (3bbd858)


= v5.8.5 - 2024-04-08 =
* fix build (531a227)
* use zip action (23b6199)
* chore: release v5.8.5 (00ec6af)


= v5.8.4 - 2024-04-06 =
* update plugin name (6315f11)
* chore: release v5.8.4 (c317a3e)


= v5.8.3 - 2024-04-06 =
* create release archive (3bb67b0)
* chore: release v5.8.3 (4ad05c1)


= v5.8.2 - 2024-04-06 =
* Ignore vendor folder (473c322)
* remove vendor folder (cfd41a4)
* upgrade actions/checkout (491a4af)
* chore: release v5.8.2 (d62c6c9)


= v5.8.1 - 2024-04-05 =
* fix expose_on_domain_association_request (95b94ef)
* chore: release v5.8.1 (d26a74a)


= v5.8.0 - 2024-04-05 =
* - cleanup (7eb0223)
* add ! defined( 'ABSPATH' ) check (a67f0e9)
* Change plugin name (4bd0b4f)
* escape echos (9c651c2)
* file_get_contents() is discouraged. Use wp_remote_get() for remote URLs instead. (4bee441)
* fix translations (b3aa14a)
* remove old vendor filed (6abc4e0)
* Revert "update version to 6.0.0" (75a0b58)
* sanitize $_GETs (9ffecbb)
* sanitize $_SERVERs (34e16f9)
* sanitize filter_inputs (0af20e3)
* update plugin name (c263590)
* update readme (7f3679c)
* update version to 6.0.0 (dcd3d3c)
* update wp_verify_nonce (19d1040)
* chore: release v5.8.0 (e6857b1)


= v5.7.2 - 2024-03-20 =
* add license (cbac3af)
* update WordPress tested up to version (5f12044)
* chore: release v5.7.2 (d0c56b7)


= v5.7.1 - 2023-12-11 =
* update vendor dependencies (200370c)
* update vendor dependencies (2863ba7)
* chore: release v5.7.1 (d95a22a)


= v5.7.0 - 2023-11-30 =
* update dependencies (f346f86)
* chore: release v5.7.0 (fa33f68)


= v5.6.8 - 2023-08-28 =
* Update abstract-wc-monei-payment-gateway-hosted.php (df1b8cd)
* chore: release v5.6.8 (51ac76c)


= v5.6.7 - 2023-01-13 =
* updated versions metadata compatibility (d9abb80)
* chore: release v5.6.7 (799b35f)


= v5.6.6 - 2022-03-15 =
* Adding PHP requires (58fd3bf)
* Bumping woo tested version (cd5838c)
* Upgrading monei sdk/guzzle (2f3e845)
* chore: release v5.6.6 (ef2675f)


= v5.6.5 - 2022-02-22 =
* fix trailing coma (42ffd5b)
* chore: release v5.6.5 (e98c1f8)


= v5.6.4 - 2022-02-11 =
* hiding/showing payment request button on tokenised card selection (a5ddadf)
* chore: release v5.6.4 (cd0cd30)


= v5.6.3 - 2022-02-04 =
* adding shipping and billing info. (9f5f04e)
* Bump node-fetch from 2.6.1 to 2.6.7 in /build (e46de16)
* update readme (aba2b1b)
* upgrade dependencies (67341aa)
* chore: release v5.6.3 (70c903d)


= v5.6.2 - 2022-01-25 =
* Bug fixing for paymentRequest and Cofidis on update checkout event (5832a06)
* updating tested up (42fb2cf)
* chore: release v5.6.2 (abfd1a0)


= v5.6.1 - 2022-01-12 =
* fix typos (f7f15b9)
* Updating readme. (ef88921)
* chore: release v5.6.1 (4f0cb7f)


= v5.6.0 - 2021-12-15 =
* Adding apple verification (c1fdf55)
* automatic apple domain registration (f5894e3)
* bugfixing (54cf8d4)
* bumping monei sdk to 1.0 (0cc70fc)
* bumping version (9f93b75)
* dont expose file if setting not active (008a795)
* fix version (387dfb1)
* fixing api keys problem (fc1329a)
* google pay (8ef0bd5)
* Updating apple pay wording (2da6d39)
* chore: release v5.6.0 (45eb814)


= v5.5.0 - 2021-11-22 =
* adding free sing ups trial version (0 payment) (5638cf9)
* adding subscription payment creation (e35c2a2)
* adding subscription support (d8164ad)
* Adding support for change payment method in subscription. Enriching payment method name for subs. (e17355f)
* allowing multiple subscriptions (3df5f0c)
* autoreplace version on build (0508fc1)
* bumping wc support (aeb6c8d)
* change subscription payment method (d965a18)
* fixing bug (bab5f2b)
* fixing few bugs (099f3ca)
* renewal payment process and retry payment process (94fd503)
* saving sequence id on subscription payments (f31f263)
* subscription info saved on subscription level (aeccc44)
* update version (c13a481)
* updating changelog (f933173)
* chore: release v5.5.0 (44aa3c1)


= v5.4.0 - 2021-10-13 =
* 75 and 1000 included (a6aa160)
* Adding custom user agent to api request (0657f2e)
* Adding pre auth to cofidis and paypal (edcb14c)
* adding preauth to hosted methods (ae66d22)
* bumping sdk to 0.1.19 (65246ac)
* Bumping version (4662919)
* changing order description name (2dadc48)
* cleaning old methods (d4cac70)
* cleanup of not used payment methods settings (a7b687d)
* click to pay integration (b6d1bf9)
* cofidis and monei logo width (ca3c596)
* cofidis js enqueue variable conflict (a6c77ad)
* cofidis tunning (8d2b9d8)
* fix version (036d8f4)
* fixing bug for manual capture payments (9b3e615)
* implementing cofidis payment gatewat (05cc72f)
* pre auth for all monei methods (ce7f5ce)
* reformating code (8909246)
* removing clicktopay (845a459)
* update composer (b42f7e5)
* Updating readme (c4ec0bc)
* widget logic working right. (70fb569)
* chore: release v5.4.0 (28ca6f1)


= v5.3.1 - 2021-10-04 =
* bump version (a6a65a2)
* update hooks (29d26b6)
* chore: release v5.3.1 (70d5298)


= v5.3.0 - 2021-10-04 =
* update hooks (469c7b4)
* update hooks (deabcc9)
* update hooks (20241de)
* update hooks (d0c0795)
* update readme.txt (369ded9)
* chore: release v5.3.0 (6faa01e)


= v5.2.5 - 2021-10-04 =
* downgrade release-it (dc0b48e)
* downgrade release-it (77443b2)
* update hook (96f5bc0)
* chore: release v5.2.5 (da836c8)


= v5.2.4 - 2021-10-04 =
* remove minified version (b1ba844)
* update hook (d16de80)
* update version (d1973c5)
* use after init hook (b2a0860)
* chore: release v5.2.4 (e26a5cb)


= v5.2.3 - 2021-10-04 =
* add build script (bbf5c68)
* add hooks (83e9cc7)
* changes css (45a462e)
* fixing confirm payment bug usage case (e26efc2)
* fixing container bug (eb1905f)
* update readme (532e16b)
* chore: release v5.2.3 (e6f6c6f)


= v5.2.2 - 2021-10-04 =
* remove type declaration for bootstrap80.php (4adbf31)
* chore: release v5.2.2 (02744be)


= v5.2.1 - 2021-10-04 =
* remove type declaration for bootstrap80.php (c8c225e)
* upgrade dependencies (75dbff3)
* chore: release v5.2.1 (0a50df2)


= v5.2.0 - 2021-10-04 =
* 2 steps payment (5e7f4fe)
* Adding checks on saving settings to see if all data available. (550b2b6)
* Adding general CC class (f919374)
* adding minified version (b75afae)
* adding support to add payment method (d7b8f59)
* bug is_monei_saved_token_selected (8c9b869)
* bumping monei sdk (bd4f77b)
* bumping version (a015897)
* bumping version sdk 0.1.16 (ca9a7b9)
* do not export github folder (894cd40)
* fixing add payment method with component card (5c14b9d)
* fixing bug in monei_token_exists (10a2369)
* fixing bug on add payment cards when operation is a failure (0f2280f)
* fixing bug to tokenise card on its own payment method. (6d8fbb6)
* fixing bug with tokenised cards, assigning to the right cc provider. (6263299)
* fixing checkout js problems and my account add card issue. (8cc8162)
* improving styling (6ef35de)
* making compatible hosted and component within same class. (becc65c)
* Payment Token and submit (323a15b)
* payment with tokenised card in checkout screen. (c8a3f84)
* removing warnings (e8d7601)
* sdk (11bdd1c)
* solving bugs, missing cardname, missing on saved cc, initialize crd (fcc778c)
* support to add payment method (20cea64)
* update stable tag (716fac8)
* updated styles and bumped version to 5.2 (2dd306d)
* updating styling (d6ba7c4)
* chore: release v5.2.0 (262b5ed)


= v5.1.2 - 2021-07-28 =
* configure automatic releases (be2ee90)
* chore: release v5.1.2 (9bfcd7d)


= v5.1.1 - 2021-07-28 =
* update stable tag (47783fc)


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
