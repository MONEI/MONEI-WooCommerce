# MONEI Payments for WooCommerce

Official WordPress plugin for accepting payments through MONEI's payment gateway.

## Description

MONEI is an e-commerce payment gateway for WooCommerce that enables merchants to accept:

-   Credit/Debit Cards (230+ currencies)
-   Apple Pay & Google Pay
-   Bizum (Spain)
-   PayPal
-   SEPA Direct Debit (EU)
-   Multibanco & MBWay (Portugal)

## Development Setup

### Prerequisites

-   PHP 8.0 or higher
-   Node.js 22.13 or higher (the version in `.nvmrc`; pnpm 11 requires it)
-   Composer
-   pnpm 11 (pinned via the packageManager field)

### Installation

1. Clone the repository:

```bash
git clone git@github.com:MONEI/MONEI-WooCommerce.git
cd MONEI-WooCommerce
```

2. Install PHP dependencies:

```bash
composer install
```

3. Install Node dependencies and build assets:

```bash
pnpm install
pnpm build
```

## Development Workflow

### Making Changes

This project uses **conventional commits** to enable automated changelog generation. All commits must follow this format:

```
<type>: <description>

[optional body]
[optional footer]
```

**Types:**

-   `feat:` - New feature
-   `fix:` - Bug fix
-   `docs:` - Documentation changes
-   `style:` - Code style changes (formatting, etc.)
-   `refactor:` - Code refactoring
-   `perf:` - Performance improvements
-   `test:` - Adding or updating tests
-   `build:` - Build system changes
-   `ci:` - CI/CD changes
-   `chore:` - Other changes (dependencies, etc.)

**Examples:**

```bash
git commit -m "feat: add support for new payment method"
git commit -m "fix: resolve checkout error on mobile devices"
git commit -m "docs: update installation instructions"
```

Commits are automatically validated by Husky + Commitlint. Invalid commit messages will be rejected.

### Building Assets

```bash
# Development build with watch mode
pnpm start

# Production build
pnpm build
```

### Testing

```bash
# JavaScript unit tests
pnpm test:unit

# PHP unit tests
composer test

# End to end payment tests
pnpm test:e2e
```

`pnpm test:e2e` pays with real MONEI test cards on a real site, so it requires
`MONEI_E2E_BASE_URL` (the site the browser drives) and `MONEI_E2E_WP_DIR` (the
docker-compose directory of that same site). Neither has a default. Copy
`tests/playwright/.env.example` to `tests/playwright/.env` and fill both in —
see [tests/playwright/README.md](tests/playwright/README.md).

## Release Process

### Automated Release with Conventional Commits

1. **Ensure all changes are committed** with proper conventional commit messages:

```bash
git add .
git commit -m "feat: add new payment gateway"
git push
```

2. **Run the release command**:

```bash
pnpm release
```

This will automatically:

-   ✅ Bump version in `package.json`, `readme.txt`, `.readme-template`, `woocommerce-gateway-monei.php`, and `class-woocommerce-gateway-monei.php`
-   ✅ Generate changelog from git conventional commits
-   ✅ Update `readme.txt` with new changelog entries above manual entries
-   ✅ Generate `CHANGELOG.md` with full git history
-   ✅ Create git tag (e.g., `6.3.9`)
-   ✅ Generate GitHub release notes
-   ✅ Push changes and tags to GitHub

**Changelog System:**

-   New releases with conventional commits → auto-generated entries at the top
-   Historical releases → manual entries preserved below
-   `.readme-template` contains `{{__PLUGIN_CHANGELOG__}}` placeholder
-   `generate-wp-readme` replaces placeholder with git commit history
-   Manual changelog entries remain intact below the auto-generated section

3. **CI/CD takes over**:
    - GitHub Actions builds the plugin
    - Deploys to WordPress.org
    - Attaches plugin ZIP to GitHub release

### Dry Run (Testing)

Test the release process without making changes:

```bash
pnpm release --dry-run
```

### Manual Version Bump

To specify a version manually:

```bash
pnpm release --increment patch   # 6.3.8 → 6.3.9
pnpm release --increment minor   # 6.3.8 → 6.4.0
pnpm release --increment major   # 6.3.8 → 7.0.0
pnpm release 6.4.0              # Specific version
```

## Project Structure

```
├── assets/              # Source files (JS/CSS)
│   ├── js/             # JavaScript sources
│   └── css/            # CSS sources
├── public/             # Built assets (generated, gitignored)
│   ├── js/             # Built JavaScript
│   ├── css/            # Built CSS
│   └── images/         # Static images (tracked in git)
├── src/                # PHP source code
├── includes/           # PHP includes
├── .husky/             # Git hooks
│   └── commit-msg      # Commitlint hook
├── .github/workflows/  # CI/CD workflows
├── package.json        # Node dependencies & scripts
├── composer.json       # PHP dependencies
└── readme.txt          # WordPress.org plugin readme
```

## Code Quality & Linting

### Overview

The project uses automated linting and code quality tools to maintain consistent code style and catch bugs early:

-   **JavaScript/CSS**: ESLint + Stylelint (via `@wordpress/scripts`)
-   **PHP**: PHPCS (WordPress Coding Standards) + PHPStan (static analysis)
-   **Git Hooks**: Husky + lint-staged for automatic fixing
-   **Commit Messages**: Commitlint (conventional commits)

### Git Hooks

**Pre-commit Hook**:

-   Auto-fixes staged files with `lint-staged`
-   PHP: `phpcbf` (auto-fix code style) + `phpstan` (static analysis)
-   JavaScript: `eslint --fix`
-   CSS: `stylelint --fix`
-   **Prevents committing broken code** by running PHPStan

**Commit-msg Hook**:

-   Validates commit message format (conventional commits)

**Pre-push Hook**:

-   **Branch Protection**: Blocks direct pushes to `master`/`main` branches

### Linting Commands

```bash
# Auto-fix all issues at once (recommended)
pnpm lint:fix

# Individual fixers
pnpm lint:js-fix    # Fix JavaScript issues
pnpm lint:css-fix   # Fix CSS issues
pnpm lint:php:fix   # Fix PHP code style issues (phpcbf)

# Linters only (no auto-fix)
pnpm lint           # Check all (JS + CSS + PHP)
pnpm lint:js        # Check JavaScript
pnpm lint:css       # Check CSS
pnpm lint:php       # Check PHP (PHPCS + PHPStan)
pnpm lint:php:phpcs # Check PHP code style only
pnpm lint:php:phpstan # Check PHP static analysis only
```

### Workflow Best Practices

1. **Before committing**: Run `pnpm lint:fix` to auto-fix all issues
2. **During commit**: Hooks auto-fix staged files, run PHPStan, and validate commit message
3. **If commit fails**: Fix PHPStan errors and commit again
4. **Before push**: Branch protection check ensures you're not pushing to master

### Configuration Files

-   `.lintstagedrc.json` - Auto-fix configuration for staged files
-   `.eslintrc.js` - JavaScript linting rules
-   `.eslintignore` - Exclude `public/` build outputs from JS linting
-   `.stylelintignore` - Exclude `public/` build outputs from CSS linting
-   `phpcs.xml` - PHP code style rules (WordPress standards)
-   `phpstan.neon` - PHP static analysis configuration (Level 4)
-   `commitlint.config.js` - Commit message validation rules

### PHPStan (Static Analysis)

PHPStan analyzes PHP code for type errors and bugs without running it:

```bash
# Run PHPStan manually
composer phpstan

# Or via pnpm
pnpm lint:php:phpstan
```

**Common PHPStan errors:**

-   Missing type hints in docblocks
-   Calling undefined methods
-   Type mismatches in function parameters
-   Unreachable code

**Configuration**: `phpstan.neon` (Level 4)

-   WordPress/WooCommerce function stubs included
-   Bootstrap file for plugin constants

### PHPCS (Code Style)

PHPCS checks PHP code against WordPress Coding Standards:

```bash
# Check code style
composer phpcs
pnpm lint:php:phpcs

# Auto-fix code style issues
composer phpcbf
pnpm lint:php:fix
```

**Configuration**: `phpcs.xml`

-   WordPress-Core ruleset
-   Tabs for indentation
-   PSR-4 autoloading compatible

### Branch Protection

Direct pushes to `master`/`main` branches are blocked by the pre-push hook:

```bash
# ❌ This will fail:
git checkout master
git push origin master

# ✅ Instead, use feature branches:
git checkout -b feat/my-feature
git push origin feat/my-feature
# Then create a Pull Request on GitHub
```

## Scripts

-   `pnpm build` - Build production assets
-   `pnpm start` - Development build with watch mode
-   `pnpm release` - Create new release (automated versioning)
-   `pnpm lint` - Lint all files (JS + CSS + PHP)
-   `pnpm lint:fix` - Auto-fix all linting issues
-   `pnpm lint:js` - Lint JavaScript
-   `pnpm lint:js-fix` - Fix JavaScript linting issues
-   `pnpm lint:css` - Lint CSS
-   `pnpm lint:css-fix` - Fix CSS linting issues
-   `pnpm lint:php` - Lint PHP (PHPCS + PHPStan)
-   `pnpm lint:php:fix` - Fix PHP code style issues
-   `pnpm test:unit` - Run JavaScript unit tests
-   `pnpm test:e2e` - Run end to end payment tests (needs `tests/playwright/.env`)

## Tech Stack

-   **Build Tool**: Webpack (via @wordpress/scripts)
-   **Package Manager**: pnpm 11
-   **Commit Linting**: Commitlint + Husky
-   **Release Automation**: release-it + generate-wp-readme
-   **Changelog**: Auto-generated from conventional commits

## CI/CD

### GitHub Actions Workflows

1. **WordPress.org Deployment** (`.github/workflows/main.yml`)

    - Triggers on GitHub release creation
    - Builds assets, deploys to WordPress.org
    - Attaches ZIP to GitHub release

2. **Manual Package Creation** (`.github/workflows/create-package.yml`)
    - Manually triggered via Actions UI
    - Creates installable plugin ZIP

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/my-feature`
3. Make changes using conventional commits
4. Push and create a Pull Request

## Support

-   Documentation: https://support.monei.com
-   Email: support@monei.com
-   WordPress.org: https://wordpress.org/plugins/monei/

## License

GPLv2 or later
