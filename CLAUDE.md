# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MONEI Payments for WooCommerce - Official WordPress plugin for accepting payments through MONEI's payment gateway. Supports Credit/Debit Cards, Apple Pay, Google Pay, Bizum, PayPal, and other payment methods.

## Development Commands

### Build & Development
```bash
# Install dependencies
composer install
yarn install

# Build production assets (required after JS/CSS changes)
yarn build

# Development build with watch mode
yarn start

# Lint and fix code
yarn lint:js-fix
yarn lint:css
```

### Release Process
```bash
# Create new release (automated versioning based on conventional commits)
yarn release

# Test release without making changes
yarn release --dry-run

# Manual version bump
yarn release --increment patch   # 6.3.8 → 6.3.9
yarn release --increment minor   # 6.3.8 → 6.4.0
```

**IMPORTANT**: All commits MUST follow conventional commit format:
- `feat:` - New feature
- `fix:` - Bug fix
- `refactor:` - Code refactoring
- `chore:` - Dependencies, build changes

Commits are validated by Husky + Commitlint.

## Docker Development Environment

The plugin is developed using Docker containers in `/Users/dmitriy/Work/woocommerce/`:

### Services

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart WordPress (e.g., after config changes)
docker-compose restart wordpress

# View logs
docker-compose logs -f wordpress
```

**Containers**:
- `wordpress` - WordPress site (http://localhost:8080)
- `db` - MariaDB database
- `phpmyadmin` - Database admin UI (http://localhost:8180)
- `ngrok` - Public URL tunneling for webhooks/testing
- `wp-cli` - WordPress CLI tool

### Plugin Development Setup

The plugin directory is mounted as a volume:
```yaml
- ./wp-content/plugins/MONEI-WooCommerce:/var/www/html/wp-content/plugins/MONEI-WooCommerce
```

**This means**:
- Edit files in `/Users/dmitriy/Work/woocommerce/wp-content/plugins/MONEI-WooCommerce/`
- Changes are immediately reflected in the WordPress container
- No need to rebuild containers after code changes
- Must run `yarn build` after JS/CSS changes to regenerate assets

### WordPress Configuration

Debug mode is enabled in `docker-compose.yml`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
```

**Check errors**:
```bash
# View debug log
docker-compose exec wordpress cat /var/www/html/wp-content/debug.log

# Follow logs in real-time
docker-compose exec wordpress tail -f /var/www/html/wp-content/debug.log
```

### Database Access

- **phpMyAdmin**: http://localhost:8180
  - Server: `db`
  - Username: `root`
  - Password: `password`
  - Database: `wordpress`

- **Direct connection**:
  ```bash
  docker-compose exec db mysql -uroot -ppassword wordpress
  ```

### WP-CLI Usage

```bash
# Run WP-CLI commands
docker-compose exec wp-cli wp plugin list
docker-compose exec wp-cli wp user list
docker-compose exec wp-cli wp cache flush

# Install/activate plugins
docker-compose exec wp-cli wp plugin install woocommerce --activate
```

### Ngrok Public URLs

Ngrok provides a public HTTPS URL for local development (useful for webhooks and external payment testing). The WordPress site automatically redirects localhost to the ngrok URL.

Check ngrok URL: http://localhost:4040

### Common Docker Issues

**"Critical error" after code changes**:
1. Check debug log: `docker-compose exec wordpress cat /var/www/html/wp-content/debug.log`
2. Usually a PHP syntax error - fix the file and refresh
3. No container restart needed

**Plugin changes not appearing**:
1. Clear WordPress cache: `docker-compose exec wp-cli wp cache flush`
2. Rebuild assets: `yarn build`
3. Check file permissions if needed

**Database reset**:
```bash
# Warning: This deletes all data
docker-compose down -v
docker-compose up -d
```

## Architecture

### PHP Structure (PSR-4 Autoloaded via `Monei\` namespace)

```
src/
├── Core/                          # Dependency injection container
│   ├── ContainerProvider.php     # DI container singleton
│   └── container-definitions.php # Service definitions
├── Gateways/
│   ├── Abstracts/                # Base gateway classes
│   ├── PaymentMethods/           # Payment method implementations
│   │   ├── WCGatewayMoneiCC.php              # Credit Card
│   │   ├── WCGatewayMoneiBizum.php           # Bizum
│   │   ├── WCGatewayMoneiPaypal.php          # PayPal
│   │   └── WCGatewayMoneiAppleGoogle.php     # Apple/Google Pay
│   └── Blocks/                   # WooCommerce Blocks integration
│       ├── MoneiCCBlocksSupport.php
│       ├── MoneiBizumBlocksSupport.php
│       └── MoneiPaypalBlocksSupport.php
├── Services/
│   ├── ApiKeyService.php         # API key management
│   ├── PaymentMethodsService.php # Payment methods API integration
│   └── payment/                  # Payment processing services
├── Features/
│   └── Subscriptions/            # WooCommerce/YITH subscriptions support
└── Templates/                     # Template rendering service
```

### JavaScript Architecture

**Classic Checkout** (assets/js/monei-*-classic.js):
- jQuery-based traditional checkout forms
- Direct MONEI.js SDK integration
- Files: `monei-cc-classic.js`, `monei-bizum-classic.js`, `monei-paypal-classic.js`, `monei-apple-google-classic.js`

**Block Checkout** (assets/js/monei-block-checkout-*.js):
- React-based WooCommerce Blocks checkout
- Uses `wp.element` (React) and WooCommerce Blocks APIs
- Shared components in `assets/js/components/` and `assets/js/helpers/`

**Build Process**:
- Webpack via `@wordpress/scripts`
- Source: `assets/js/*.js` and `assets/css/*.css`
- Output: `public/js/*.min.js` and `public/css/*.css`
- Auto-generated files in `public/` are gitignored

### Payment Method Settings

Each payment method has:
1. Settings file in `includes/admin/monei-*-settings.php` (returns array of WooCommerce settings fields)
2. Gateway class in `src/Gateways/PaymentMethods/WCGatewayMonei*.php` (handles payment processing)
3. Blocks support class in `src/Gateways/Blocks/Monei*BlocksSupport.php` (WooCommerce Blocks integration)
4. Classic JS in `assets/js/monei-*-classic.js`
5. Block JS in `assets/js/monei-block-checkout-*.js`

**JSON Style Configuration Pattern**:
- Settings have `*_style` fields (e.g., `card_input_style`, `bizum_style`, `paypal_style`)
- Defaults match PrestaShop plugin for consistency
- Validation via `validate_*_style_field()` methods in gateway classes
- Passed to JavaScript via `wp_localize_script()` and `json_decode()`
- Applied to MONEI.js components (CardInput, Bizum, PayPal, PaymentRequest)

## Critical Patterns

### Adding/Modifying Payment Method Styles

1. **Settings File** (`includes/admin/monei-*-settings.php`):
   ```php
   'style_field' => array(
       'title'       => __( 'Component Style', 'monei' ),
       'type'        => 'textarea',
       'description' => __( 'JSON format...', 'monei' ),
       'default'     => '{"height": "42"}',
   ),
   ```

2. **Gateway Class Validation** (src/Gateways/PaymentMethods/WCGatewayMonei*.php):
   ```php
   public function validate_style_field_field( $key, $value ) {
       if ( empty( $value ) ) {
           return $value;
       }
       json_decode( $value );
       if ( json_last_error() !== JSON_ERROR_NONE ) {
           WC_Admin_Settings::add_error( ... );
           return $this->get_option( 'style_field', '{"height": "42"}' );
       }
       return $value;
   }
   ```

3. **Pass to Classic JS**:
   ```php
   $style = $this->get_option( 'style_field', '{}' );
   wp_localize_script( 'script-handle', 'params', array(
       'style' => json_decode( $style ),
   ));
   ```

4. **Pass to Block Checkout** (src/Gateways/Blocks/*BlocksSupport.php):
   ```php
   $style = $this->get_setting( 'style_field' ) ?? '{}';
   $data = array(
       'componentStyle' => json_decode( $style ),
   );
   ```

5. **Apply in JavaScript**:
   ```javascript
   // Classic
   monei.Component({
       style: params.style || {},
   });

   // Block
   monei.Component({
       style: componentData.componentStyle || {},
   });
   ```

### Dependency Injection

Services are registered in `src/Core/container-definitions.php` and accessed via:
```php
$container = ContainerProvider::getInstance()->getContainer();
$service = $container->get( ServiceClass::class );
```

### WooCommerce Blocks Integration

Block checkout payment methods must implement:
- `is_active()` - Check if payment method is available
- `get_payment_method_script_handles()` - Register JS assets
- `get_payment_method_data()` - Pass data to React component

### MONEI SDK Integration

- PHP SDK: `monei/monei-php-sdk` (Composer)
- JS SDK: Loaded via `https://js.monei.com/v2/monei.js`
- Components: CardInput, Bizum, PayPal, PaymentRequest (Apple/Google Pay)

## Testing

WooCommerce Blocks checkout requires testing in WordPress with WooCommerce Blocks plugin activated. Classic checkout works with standard WooCommerce.

## Code Quality & Linting

### Overview

The project uses static analysis and code style tools to catch bugs before runtime and maintain consistent code style:

- **PHPStan** - Static analysis to catch type errors, undefined methods, and bugs
- **PHPCS** - WordPress Coding Standards enforcement
- **PHPCBF** - Automatic code style fixer
- **Pre-commit hooks** - Automated checks before committing code

### Running Linters

```bash
# Run all linters (PHP + JS + CSS)
yarn lint

# PHP Static Analysis (PHPStan)
yarn lint:php:phpstan
composer phpstan

# PHP Code Style (PHPCS)
yarn lint:php:phpcs
composer phpcs

# Auto-fix PHP code style issues
yarn lint:php:fix
composer phpcbf

# JavaScript linting
yarn lint:js
yarn lint:js-fix

# CSS linting
yarn lint:css
```

### PHPStan Configuration

**Configuration**: `phpstan.neon`
- Level 4 analysis (good balance of strictness vs. WordPress compatibility)
- WordPress/WooCommerce stubs for function/class definitions
- MONEI SDK scanning for type information
- Bootstrap file (`tests/phpstan-bootstrap.php`) for plugin constants and legacy classes

**Key Ignores**:
- WordPress action callbacks can return values (WordPress ignores them)
- Missing generic types (common WordPress pattern)
- Dynamic properties (WordPress pattern)

**Running on specific files**:
```bash
composer phpstan -- src/Gateways/PaymentMethods/WCGatewayMoneiCC.php
```

### PHPCS WordPress Coding Standards

**Configuration**: `.phpcs.xml.dist`
- WordPress-Core ruleset
- PSR-4 autoloading compatibility
- Tabs for indentation (WordPress standard)
- File naming follows WordPress conventions

**Auto-fixing issues**:
```bash
# Fix all files in src/
composer phpcbf

# Fix specific file
composer phpcbf -- src/Gateways/PaymentMethods/WCGatewayMoneiCC.php
```

### Pre-commit Hooks

**File**: `.husky/pre-commit`

Automatically runs PHPStan on staged PHP files before each commit. This prevents committing code with type errors.

**To bypass** (not recommended):
```bash
git commit --no-verify -m "message"
```

### Common PHPStan Errors & Fixes

#### 1. Missing Namespace Backslash

**Error**: `Class "Monei\Gateways\PaymentMethods\WC_Admin_Settings" not found`

**Fix**: Add leading backslash for global classes:
```php
// Wrong
WC_Admin_Settings::add_error();

// Correct
\WC_Admin_Settings::add_error();
```

#### 2. Undefined Method on Interface

**Error**: `Call to an undefined method Interface::method()`

**Fix**: Add method to interface definition:
```php
interface MyInterface {
    public function missing_method(): bool;
}
```

#### 3. Parameter Type Mismatch

**Error**: `Parameter $value (null) should be compatible with (string)`

**Fix**: Update docblock to allow null:
```php
/**
 * @param string|null $value
 */
public function method( $value = null ) {
```

#### 4. Outdated SDK References

**Error**: `Class "OpenAPI\Client\Model\Payment" not found`

**Fix**: Update to current SDK namespace:
```php
// Old SDK v1.x
use OpenAPI\Client\Model\Payment;

// New SDK v2.x+
use Monei\Model\Payment;
```

### Bootstrap File (`tests/phpstan-bootstrap.php`)

Defines constants and legacy classes for PHPStan static analysis:

- Plugin constants (MONEI_VERSION, MONEI_GATEWAY_ID, etc.)
- Helper functions (WC_Monei(), monei_price_format(), etc.)
- Legacy classes from includes/ (WC_Monei_IPN, WC_Monei_Logger)

**When to update**:
- Adding new plugin constants
- Adding new global helper functions
- PHPStan reports "Constant/Function not found" errors

### WordPress-Specific Patterns

PHPStan configuration accounts for WordPress patterns:

1. **Global Functions**: WordPress/WooCommerce functions defined via stubs
2. **Dynamic Properties**: WordPress objects often have dynamic properties (ignored)
3. **Action/Filter Returns**: WordPress actions can have return values (they're ignored by WordPress core)
4. **Type Hints**: WordPress rarely uses strict typing (we use PHPDoc for static analysis)

### Best Practices

1. **Always run linters** before committing:
   ```bash
   yarn lint
   ```

2. **Fix auto-fixable issues** first:
   ```bash
   composer phpcbf
   yarn lint:js-fix
   ```

3. **Check pre-commit hook** is working:
   ```bash
   chmod +x .husky/pre-commit
   ```

4. **Add type hints** where possible (helps PHPStan):
   ```php
   public function process_payment( int $order_id ): array {
   ```

5. **Update docblocks** when changing method signatures:
   ```php
   /**
    * @param string|null $value
    * @return \Monei\Model\Payment
    * @throws \Monei\ApiException
    */
   ```

### Memory Issues with PHPStan

If PHPStan runs out of memory:

```bash
# Already configured in composer.json with 1GB
composer phpstan

# Manual override if needed
vendor/bin/phpstan analyse --memory-limit=2G
```

**Configuration**: `phpstan.neon` uses single process mode and 1GB memory limit to prevent crashes.

## Common Tasks

### After modifying JavaScript:
```bash
yarn build
```

### After modifying settings or gateway classes:
Clear WordPress transients and WooCommerce cache if payment methods don't appear.

### PrestaShop Parity:
When adding features, check PrestaShop plugin at `/Users/dmitriy/Work/prestashop/modules/monei/` for consistency in defaults and behavior.
