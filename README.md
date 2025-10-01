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
- ✅ Bump version in `package.json`, `readme.txt`, `woocommerce-gateway-monei.php`, and `class-woocommerce-gateway-monei.php`
- ✅ Generate changelog from commit history → `readme.txt`
- ✅ Create git tag (e.g., `6.3.9`)
- ✅ Generate GitHub release notes
- ✅ Push changes and tags to GitHub

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

## Scripts

- `yarn build` - Build production assets
- `yarn start` - Development build with watch mode
- `yarn release` - Create new release (automated versioning)
- `yarn lint:js` - Lint JavaScript
- `yarn lint:js-fix` - Fix JavaScript linting issues
- `yarn lint:css` - Lint CSS

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
