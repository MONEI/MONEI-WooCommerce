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

## In CI

The `e2e` job in `.github/workflows/tests.yml` stays skipped until all three of
these repository secrets exist. A fork or a clone has no MONEI account behind
it, so a missing secret skips the job instead of failing the run.

| Secret               | Value                                                      |
| -------------------- | ---------------------------------------------------------- |
| `MONEI_TEST_API_KEY` | Test mode API key of the MONEI account the site pays with. |
| `MONEI_E2E_BASE_URL` | Public URL of the site the runner drives and pays on.      |
| `MONEI_E2E_WP_DIR`   | docker-compose directory of that same site on the runner.  |

`MONEI_E2E_WP_DIR` is the unfinished half. The specs reconfigure the site
through `docker-compose run --rm --entrypoint wp wp-cli`, so the job needs a
runner that both serves the site and holds its compose stack — a self hosted
runner, or a step that brings the stack up first. A GitHub hosted runner cannot
reach a site hosted anywhere else with WP-CLI. Adding `MONEI_TEST_API_KEY`
alone does not start the job, which is deliberate: the job runs only once the
whole environment is there.

Every push runs the Jest and PHPUnit jobs in the same workflow. Neither needs a
MONEI account.
