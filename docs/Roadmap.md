# MONEI WooCommerce Plugin Roadmap

## 1. Bug Fixing

### Description
Update the plugin header to require PHP 7.4 instead of 7.2, and plan for phasing out older versions in line with WooCommerce and WordPress.

### Subtasks
  - Update the plugin header to require PHP 7.4.
  - Plan for phasing out support for older PHP versions.

### Acceptance Criteria
  GIVEN the Monei plugin is onboarded
  WHEN I check the plugin header requirements
  THEN it should require PHP 7.4

### Labels
  bug, compatibility, php-version

### Code Reference

### Description
Ensure all plugin settings are translated correctly when changing the WordPress language.

### Subtasks
  - Decide what languages to support.
  - Review existing translations at WordPress.org.
  - Add missing translations.
  - Ensure all strings are translatable in the plugin.
  - Add workflow to include translations in the pipeline: update pot file on push.
  - Test the translations in different languages.

### Acceptance Criteria
  GIVEN the Monei plugin is onboarded
  WHEN I change the WordPress language
  THEN all plugin settings should be translated correctly

### Labels
  bug, translation, internationalization

### Code Reference

### Description
Ensure that transactions are logged only when explicitly selected by the user. This should be handled in the logging mechanism of the plugin.

### Subtasks
  - Update logging mechanism to check user selection before logging.
  - Ensure concise output for logs.

### Acceptance Criteria
  GIVEN the Monei plugin is onboarded
  WHEN I enable logging for each payment method
  THEN the logs should include transactions in a compact way.
  WHEN I disable logging for transactions
  THEN there should not be logging

### Labels
  bug, logging, user-experience

### Code Reference

### Description
Implement error handling to catch errors and display consistent, user-friendly messages across the plugin.

### Subtasks
  - Implement error handling in all critical paths.
  - Ensure error messages are user-friendly and consistent.

### Acceptance Criteria
  GIVEN the Monei plugin is onboarded
  WHEN an error occurs during a transaction
  THEN I should see a consistent, user-friendly error message

### Labels
  bug, error-handling, user-experience

### Code Reference

### Description
Validate and secure all user inputs to prevent security vulnerabilities.

### Subtasks
  - Implement input validation.
  - Ensure inputs are sanitized and secured.

### Acceptance Criteria
  GIVEN the Monei plugin is onboarded
  WHEN I run the CS and Psalm tool
  THEN no errors should be found regarding input validation

### Labels
  bug, security, input-validation

### Code Reference

### Description
Ensure that Apple processes run only if selected by the user.

### Subtasks
  - Update Apple authorization logic to check user selection before performing any process.

### Acceptance Criteria
  GIVEN the Monei plugin is onboarded
  WHEN I do not select Apple Pay
  THEN the plugin should not check for Apple authorization
  AND no notice should be visible in the admin

### Labels
  bug, performance, user-settings

### Code Reference

### Description
Centralize account and ID settings for Bizum, PayPal, credit card, and Cofidis to avoid confusion. Allow test mode to be set per payment method.

### Subtasks
  - Centralize account and key settings in a single page.
  - Allow test mode to be set individually for each payment method.

### Acceptance Criteria
  GIVEN the Monei plugin is onboarded
  WHEN I navigate to the plugins settings page
  THEN I should see account and API key settings fields that will be used for all payment methods
  AND I should be able to set test mode individually for each payment method

  GIVEN the Monei plugin is not onboarded
  WHEN I navigate to the plugins settings page
  THEN I should see account and API key settings fields that will be used for all payment methods
  AND I should not see the payment methods listed in the WooCommerce Payment Methods Page

### Labels
  bug, settings, configuration

### Code Reference

## 2. End-to-End Testing
### 2.1 Implement Playwright for E2E Tests
  - ~~Configure Playwright for the project.~~
  - ~~Write initial tests covering the happy path.~~
  - Gradually add tests for all critical paths.

## 3. Continuous Integration (CI) Setup
### 3.1 Integrate Psalm for Static Analysis
  - Configure Psalm in the CI pipeline.
  - Set up a baseline for existing issues to be fixed incrementally.

### 3.2 Code Style and Linting
  - Configure PHP_CodeSniffer with WordPress coding standards.
  - Configure ESLint for JavaScript files.
  - Add linting checks to the CI pipeline.

## 4. Architectural Improvements
### 4.1 Implement PSR-4 Autoloading and Namespaces
  - Refactor existing classes to use namespaces.
  - Update the autoload configuration in `composer.json`.
  - Ensure all classes are autoloaded correctly.
  - remove index.php file

### 4.2 Apply Object-Oriented Principles and IoC (to expand)
  - Remove all hooks from constructors use dependency inversion when possible.
  - Implement dependency injection where applicable.
  - Rework the payment methods to use the strategy pattern.
  - Refactor existing code to group by object in the domain.
  - Document the chosen architectural patterns and their implementation.

## 5. New Features
### 5.1 Bizum, Cofidis, and Bizum in Block Checkout Integration
  - Integrate Bizum and Cofidis payment methods in the WooCommerce block checkout.
  - Validate the payment process using Bizum and Cofidis.
  - Add test scenarios for Bizum and Cofidis transactions in block checkout.

### 5.2 Improve User Experience
  - Identify areas for improvement and create issues
  - Implement identified improvements.
  - Gather user feedback and iterate.

### 5.3 Subscriptions Integration
  - Implement support for WooCommerce Subscriptions.
  - Add test scenarios for subscription transactions.

### 5.4 Express Buttons for Apple and Google Pay in Checkout
  - Add Apple Pay and Google Pay express buttons to the checkout page.
  - Ensure compatibility with the Monei plugin.
  - Add test scenarios for express button transactions.

### 5.5 Integration of Buttons in Cart, Minicart, and Product Page
  - Add Apple Pay and Google Pay express buttons to the cart, minicart, and product pages.
  - Validate the payment process using express buttons on these pages.
  - Add test scenarios for express button transactions on these pages.

## 6. Documentation
### 6.1 Improve Developer Documentation
  - Add documentation for old features.
  - Add workflow to include documentation in the pipeline.

## 7. Build and Package Optimization
### 7.1 Minimize Assets with Webpack
  - Configure Webpack with WordPress scripts to minimize JavaScript and CSS assets.
  - Ensure all assets are minimized and bundled correctly.
  - Update the build process to use Webpack.

### 7.2 Optimize Shipped Package
  - Review the files included in the package.
  - Exclude unnecessary files.


