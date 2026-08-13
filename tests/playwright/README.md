# E2E suite

Playwright specs that pay with real MONEI test cards on a real WordPress site.

## Setup

```bash
cp tests/playwright/.env.example tests/playwright/.env
```

Fill in both values:

| Variable             | Meaning                                                         |
| -------------------- | --------------------------------------------------------------- |
| `MONEI_E2E_BASE_URL` | Public URL of the site the browser drives and pays on.          |
| `MONEI_E2E_WP_DIR`   | docker-compose directory of that **same** site, used by WP-CLI. |

Both are required and have no defaults. The suite pays through the browser and
reconfigures the site through WP-CLI, so a default on either one could point the
two halves at different sites — real test payments on one, settings and coupon
changes on the other. A missing value stops the run before anything happens.

Environment variables set in the shell win over the `.env` file.

## Running

```bash
pnpm test:e2e
```

The suite mutates global site state (card field layout, WooCommerce checkout
page), so it runs single worker and non parallel.
