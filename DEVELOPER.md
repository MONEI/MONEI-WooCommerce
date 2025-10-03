# MONEI Payments for WooCommerce

Official WordPress plugin for accepting payments through MONEI's payment gateway.

## Description

MONEI is an e-commerce payment gateway for WooCommerce that enables merchants to accept:
- Credit/Debit Cards (230+ currencies)
- Apple Pay & Google Pay
- Bizum (Spain)
- PayPal
- SEPA Direct Debit (EU)
- Multibanco & MBWay (Portugal)

## Development Setup

### Prerequisites

- PHP 7.4 or higher
- Node.js 18 or higher
- Composer
- Yarn 4 (managed via Corepack)

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
yarn install
yarn build
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
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting, etc.)
- `refactor:` - Code refactoring
- `perf:` - Performance improvements
- `test:` - Adding or updating tests
- `build:` - Build system changes
- `ci:` - CI/CD changes
- `chore:` - Other changes (dependencies, etc.)

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
yarn start

# Production build
yarn build
```

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
yarn release
```

This will automatically:
- ✅ Bump version in `package.json`, `readme.txt`, `.readme-template`, `woocommerce-gateway-monei.php`, and `class-woocommerce-gateway-monei.php`
- ✅ Generate changelog from git conventional commits
- ✅ Update `readme.txt` with new changelog entries above manual entries
- ✅ Generate `CHANGELOG.md` with full git history
- ✅ Create git tag (e.g., `6.3.9`)
- ✅ Generate GitHub release notes
- ✅ Push changes and tags to GitHub

**Changelog System:**
- New releases with conventional commits → auto-generated entries at the top
- Historical releases → manual entries preserved below
- `.readme-template` contains `{{__PLUGIN_CHANGELOG__}}` placeholder
- `generate-wp-readme` replaces placeholder with git commit history
- Manual changelog entries remain intact below the auto-generated section

3. **CI/CD takes over**:
   - GitHub Actions builds the plugin
   - Deploys to WordPress.org
   - Attaches plugin ZIP to GitHub release

### Dry Run (Testing)

Test the release process without making changes:
```bash
yarn release --dry-run
```

### Manual Version Bump

To specify a version manually:
```bash
yarn release --increment patch   # 6.3.8 → 6.3.9
yarn release --increment minor   # 6.3.8 → 6.4.0
yarn release --increment major   # 6.3.8 → 7.0.0
yarn release 6.4.0              # Specific version
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

- **JavaScript/CSS**: ESLint + Stylelint (via `@wordpress/scripts`)
- **PHP**: PHPCS (WordPress Coding Standards) + PHPStan (static analysis)
- **Git Hooks**: Husky + lint-staged for automatic fixing
- **Commit Messages**: Commitlint (conventional commits)

### Git Hooks

**Pre-commit Hook** (fast ~0.9s):
- Auto-fixes staged files with `lint-staged`
- PHP: `phpcbf` (WordPress coding standards auto-fix)
- JavaScript: `eslint --fix`
- CSS: `stylelint --fix`

**Commit-msg Hook**:
- Validates commit message format (conventional commits)

**Pre-push Hook** (~1s):
- **Branch Protection**: Blocks direct pushes to `master`/`main` branches
- **PHPStan**: Static analysis (type checking, bug detection)

### Linting Commands

```bash
# Auto-fix all issues at once (recommended)
yarn lint:fix

# Individual fixers
yarn lint:js-fix    # Fix JavaScript issues
yarn lint:css-fix   # Fix CSS issues
yarn lint:php:fix   # Fix PHP code style issues (phpcbf)

# Linters only (no auto-fix)
yarn lint           # Check all (JS + CSS + PHP)
yarn lint:js        # Check JavaScript
yarn lint:css       # Check CSS
yarn lint:php       # Check PHP (PHPCS + PHPStan)
yarn lint:php:phpcs # Check PHP code style only
yarn lint:php:phpstan # Check PHP static analysis only
```

### Workflow Best Practices

1. **Before committing**: Run `yarn lint:fix` to auto-fix all issues
2. **During commit**: Hooks auto-fix staged files and validate commit message
3. **Before push**: PHPStan runs automatically (takes ~1s)
4. **If push fails**: Fix PHPStan errors and push again

### Configuration Files

- `.lintstagedrc.json` - Auto-fix configuration for staged files
- `.eslintrc.js` - JavaScript linting rules
- `.eslintignore` - Exclude `public/` build outputs from JS linting
- `.stylelintignore` - Exclude `public/` build outputs from CSS linting
- `phpcs.xml` - PHP code style rules (WordPress standards)
- `phpstan.neon` - PHP static analysis configuration (Level 4)
- `commitlint.config.js` - Commit message validation rules

### PHPStan (Static Analysis)

PHPStan analyzes PHP code for type errors and bugs without running it:

```bash
# Run PHPStan manually
composer phpstan

# Or via yarn
yarn lint:php:phpstan
```

**Common PHPStan errors:**
- Missing type hints in docblocks
- Calling undefined methods
- Type mismatches in function parameters
- Unreachable code

**Configuration**: `phpstan.neon` (Level 4)
- WordPress/WooCommerce function stubs included
- Bootstrap file for plugin constants

### PHPCS (Code Style)

PHPCS checks PHP code against WordPress Coding Standards:

```bash
# Check code style
composer phpcs
yarn lint:php:phpcs

# Auto-fix code style issues
composer phpcbf
yarn lint:php:fix
```

**Configuration**: `phpcs.xml`
- WordPress-Core ruleset
- Tabs for indentation
- PSR-4 autoloading compatible

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

- `yarn build` - Build production assets
- `yarn start` - Development build with watch mode
- `yarn release` - Create new release (automated versioning)
- `yarn lint` - Lint all files (JS + CSS + PHP)
- `yarn lint:fix` - Auto-fix all linting issues
- `yarn lint:js` - Lint JavaScript
- `yarn lint:js-fix` - Fix JavaScript linting issues
- `yarn lint:css` - Lint CSS
- `yarn lint:css-fix` - Fix CSS linting issues
- `yarn lint:php` - Lint PHP (PHPCS + PHPStan)
- `yarn lint:php:fix` - Fix PHP code style issues

## Tech Stack

- **Build Tool**: Webpack (via @wordpress/scripts)
- **Package Manager**: Yarn 4 (Berry)
- **Commit Linting**: Commitlint + Husky
- **Release Automation**: release-it + generate-wp-readme
- **Changelog**: Auto-generated from conventional commits

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

- Documentation: https://support.monei.com
- Email: support@monei.com
- WordPress.org: https://wordpress.org/plugins/monei/

## License

GPLv2 or later
