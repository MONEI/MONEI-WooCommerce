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

-   `feat:` - New feature
-   `fix:` - Bug fix
-   `refactor:` - Code refactoring
-   `chore:` - Dependencies, build changes

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

-   `wordpress` - WordPress site (http://localhost:8080)
-   `db` - MariaDB database
-   `phpmyadmin` - Database admin UI (http://localhost:8180)
-   `ngrok` - Public URL tunneling for webhooks/testing
-   `wp-cli` - WordPress CLI tool

### Plugin Development Setup

The plugin directory is mounted as a volume:

```yaml
- ./wp-content/plugins/MONEI-WooCommerce:/var/www/html/wp-content/plugins/MONEI-WooCommerce
```

**This means**:

-   Edit files in `/Users/dmitriy/Work/woocommerce/wp-content/plugins/MONEI-WooCommerce/`
-   Changes are immediately reflected in the WordPress container
-   No need to rebuild containers after code changes
-   Must run `yarn build` after JS/CSS changes to regenerate assets

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

-   **phpMyAdmin**: http://localhost:8180

    -   Server: `db`
    -   Username: `root`
    -   Password: `password`
    -   Database: `wordpress`

-   **Direct connection**:
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

**Classic Checkout** (assets/js/monei-\*-classic.js):

-   jQuery-based traditional checkout forms
-   Direct MONEI.js SDK integration
-   Files: `monei-cc-classic.js`, `monei-bizum-classic.js`, `monei-paypal-classic.js`, `monei-apple-google-classic.js`

**Block Checkout** (assets/js/monei-block-checkout-\*.js):

-   React-based WooCommerce Blocks checkout
-   Uses `wp.element` (React) and WooCommerce Blocks APIs
-   Shared components in `assets/js/components/` and `assets/js/helpers/`

**Build Process**:

-   Webpack via `@wordpress/scripts`
-   Source: `assets/js/*.js` and `assets/css/*.css`
-   Output: `public/js/*.min.js` and `public/css/*.css`
-   Auto-generated files in `public/` are gitignored

### Payment Method Settings

Each payment method has:

1. Settings file in `includes/admin/monei-*-settings.php` (returns array of WooCommerce settings fields)
2. Gateway class in `src/Gateways/PaymentMethods/WCGatewayMonei*.php` (handles payment processing)
3. Blocks support class in `src/Gateways/Blocks/Monei*BlocksSupport.php` (WooCommerce Blocks integration)
4. Classic JS in `assets/js/monei-*-classic.js`
5. Block JS in `assets/js/monei-block-checkout-*.js`

**JSON Style Configuration Pattern**:

-   Settings have `*_style` fields (e.g., `card_input_style`, `bizum_style`, `paypal_style`)
-   Defaults match PrestaShop plugin for consistency
-   Validation via `validate_*_style_field()` methods in gateway classes
-   Passed to JavaScript via `wp_localize_script()` and `json_decode()`
-   Applied to MONEI.js components (CardInput, Bizum, PayPal, PaymentRequest)

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

2. **Gateway Class Validation** (src/Gateways/PaymentMethods/WCGatewayMonei\*.php):

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

4. **Pass to Block Checkout** (src/Gateways/Blocks/\*BlocksSupport.php):

    ```php
    $style = $this->get_setting( 'style_field' ) ?? '{}';
    $data = array(
        'componentStyle' => json_decode( $style ),
    );
    ```

5. **Apply in JavaScript**:

    ```javascript
    // Classic
    monei.Component( {
    	style: params.style || {},
    } );

    // Block
    monei.Component( {
    	style: componentData.componentStyle || {},
    } );
    ```

### Dependency Injection

Services are registered in `src/Core/container-definitions.php` and accessed via:

```php
$container = ContainerProvider::getInstance()->getContainer();
$service = $container->get( ServiceClass::class );
```

### WooCommerce Blocks Integration

Block checkout payment methods must implement:

-   `is_active()` - Check if payment method is available
-   `get_payment_method_script_handles()` - Register JS assets
-   `get_payment_method_data()` - Pass data to React component

### MONEI SDK Integration

-   PHP SDK: `monei/monei-php-sdk` (Composer)
-   JS SDK: Loaded via `https://js.monei.com/v2/monei.js`
-   Components: CardInput, Bizum, PayPal, PaymentRequest (Apple/Google Pay)

## Testing

WooCommerce Blocks checkout requires testing in WordPress with WooCommerce Blocks plugin activated. Classic checkout works with standard WooCommerce.

## Code Quality & Linting

### Overview

The project uses automated linting and static analysis tools with git hooks for an optimized developer workflow:

-   **Code Formatting**: Prettier (via `@wordpress/scripts`) - Auto-formats JS, CSS, JSON, YAML, Markdown
-   **JavaScript/CSS**: ESLint + Stylelint (via `@wordpress/scripts`)
-   **PHP Code Style**: PHPCS (WordPress Coding Standards) + PHPCBF (auto-fixer)
-   **PHP Static Analysis**: PHPStan Level 4 (type checking, bug detection)
-   **Git Hooks**: Husky + lint-staged for automatic fixing on commit
-   **Commit Messages**: Commitlint (conventional commits validation)

### Automated Workflow (Git Hooks)

**Pre-commit Hook**:

-   Runs `lint-staged` to auto-fix and validate staged files
-   Prettier: Auto-formats JS, CSS, JSON, YAML, Markdown
-   PHP: `phpcbf` (auto-fixes code style) + `phpstan` (static analysis)
-   JavaScript: `eslint --fix` (auto-fixes linting errors)
-   CSS: `stylelint --fix` (auto-fixes style errors)
-   **Result**: All fixable issues corrected, type errors caught immediately

**Commit-msg Hook** (instant):

-   Validates commit message follows conventional commits format
-   Types: `feat:`, `fix:`, `docs:`, `refactor:`, `chore:`, etc.
-   **Result**: Invalid commit messages are rejected

**Pre-push Hook** (instant):

-   **Branch Protection**: Blocks direct pushes to `master`/`main`
-   **Result**: Enforces feature branch workflow

### Running Linters Manually

```bash
# Auto-fix all issues at once (recommended workflow)
yarn lint:fix

# Individual auto-fixers
yarn format           # Auto-format JS, CSS, JSON, YAML, Markdown
yarn lint:js-fix      # Fix JavaScript
yarn lint:css-fix     # Fix CSS
yarn lint:php:fix     # Fix PHP code style (phpcbf)

# Linters only (no auto-fix)
yarn lint             # Check all (formatting + JS + CSS + PHP + PHPStan)
yarn format:check     # Check formatting without fixing
yarn lint:js          # Check JavaScript
yarn lint:css         # Check CSS
yarn lint:php         # Check PHP (PHPCS + PHPStan)
yarn lint:php:phpcs   # Check PHP code style only
yarn lint:php:phpstan # Check PHP static analysis only
```

### Developer Workflow

**Recommended workflow for best experience:**

1. **Make your changes** in the codebase
2. **Before staging**: Run `yarn lint:fix` to auto-fix all issues
3. **Stage your files**: `git add <files>`
4. **Commit**: `git commit -m "feat: your message"`
    - Pre-commit hook auto-fixes staged files
    - Pre-commit hook runs PHPStan to catch type errors
    - Commit-msg hook validates message format
5. **Push**: `git push`
    - Pre-push hook checks branch protection

**If commit fails (PHPStan errors)**:

-   Fix the type errors reported by PHPStan
-   Stage the fixes: `git add <files>`
-   Commit again

**Why this is better**:

-   Type errors are caught at commit time, not push time
-   Every commit in git history is guaranteed to be type-safe
-   No need to fix and re-commit after a failed push

### Configuration Files

-   **`.lintstagedrc.json`** - Auto-fix configuration for staged files
-   **`.husky/pre-commit`** - Runs lint-staged on commit
-   **`.husky/commit-msg`** - Validates commit messages
-   **`.husky/pre-push`** - Runs PHPStan + branch protection
-   **`.prettierrc.js`** - Code formatting rules (extends WordPress standards)
-   **`.prettierignore`** - Excludes build/vendor from formatting
-   **`.eslintrc.js`** - JavaScript linting rules
-   **`.eslintignore`** - Excludes `public/` from JS linting
-   **`.stylelintignore`** - Excludes `public/` from CSS linting
-   **`phpcs.xml`** - PHP code style rules (WordPress standards)
-   **`phpstan.neon`** - PHP static analysis configuration (Level 4)
-   **`commitlint.config.js`** - Commit message validation

### PHPStan Configuration

**Configuration**: `phpstan.neon`

-   Level 4 analysis (good balance of strictness vs. WordPress compatibility)
-   WordPress/WooCommerce stubs for function/class definitions
-   MONEI SDK scanning for type information
-   Bootstrap file (`tests/phpstan-bootstrap.php`) for plugin constants and legacy classes

**Key Ignores**:

-   WordPress action callbacks can return values (WordPress ignores them)
-   Missing generic types (common WordPress pattern)
-   Dynamic properties (WordPress pattern)

**Running on specific files**:

```bash
composer phpstan -- src/Gateways/PaymentMethods/WCGatewayMoneiCC.php
```

### PHPCS WordPress Coding Standards

**Configuration**: `phpcs.xml`

-   WordPress-Core ruleset
-   PSR-4 autoloading compatibility
-   Tabs for indentation (WordPress standard)
-   File naming follows WordPress conventions
-   Files checked: `src/` and `includes/`
-   Ignores warnings (only errors fail the build)

**Auto-fixing issues**:

```bash
# Fix all files in src/
composer phpcbf

# Fix specific file
composer phpcbf -- src/Gateways/PaymentMethods/WCGatewayMoneiCC.php
```

### Git Hooks Best Practices

**CRITICAL**: NEVER use `--no-verify` to bypass git hooks!

-   Git hooks auto-fix issues and catch errors before they reach the repository
-   If a hook fails, fix the actual errors instead of bypassing the check
-   Using `--no-verify` can introduce bugs and break the build
-   Pre-commit is fast (~0.9s) and only checks/fixes staged files

**Branch Protection**:

-   Direct pushes to `master`/`main` are automatically blocked
-   Always work in feature branches: `git checkout -b feat/my-feature`
-   Create pull requests for code review before merging to master

**Performance**:

-   lint-staged only processes staged files, not the entire codebase
-   PHPStan analyzes only staged PHP files and their dependencies
-   Pre-push hook is instant (only branch check)

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

-   Plugin constants (MONEI_VERSION, MONEI_GATEWAY_ID, etc.)
-   Helper functions (WC_Monei(), monei_price_format(), etc.)
-   Legacy classes from includes/ (WC_Monei_IPN, WC_Monei_Logger)

**When to update**:

-   Adding new plugin constants
-   Adding new global helper functions
-   PHPStan reports "Constant/Function not found" errors

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
