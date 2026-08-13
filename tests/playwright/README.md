# E2E suite

Playwright specs that pay with real MONEI test cards on a real WordPress site.

Every run leaves real test mode orders behind on the MONEI account.

## Running

The suite brings its own WordPress through [`wp-env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/), so the only thing it needs from you is a MONEI test key.

```bash
echo 'MONEI_TEST_API_KEY=pk_test_...' > tests/playwright/.env
pnpm test:e2e:start   # boots wp-env, then seeds the store
pnpm test:e2e
pnpm test:e2e:stop
```

`pnpm test:e2e:seed` re-seeds without restarting. Environment variables set in the shell win over the `.env` file.

### Against an existing site instead

Point the suite at a store you already run — a tunnelled local site, or staging — by naming **both** halves:

| Variable             | Meaning                                                     |
| -------------------- | ----------------------------------------------------------- |
| `MONEI_E2E_WP_DIR`   | docker-compose directory of the site, used by WP-CLI.       |
| `MONEI_E2E_BASE_URL` | Public URL of that **same** site, which the browser drives. |

```bash
MONEI_E2E_WP_DIR=/path/to/woocommerce \
MONEI_E2E_BASE_URL=https://your-tunnel.ngrok-free.dev \
pnpm test:e2e
```

Setting `MONEI_E2E_WP_DIR` is what switches away from wp-env. Both are then required: the suite pays through the browser and reconfigures the site through WP-CLI, so a default on either one could take real payments on one site while changing settings on another.

Under wp-env the two cannot diverge, because WP-CLI runs inside the instance the URL points at. The URL is derived rather than configured (`http://localhost:8888`, or `WP_ENV_PORT`), and a `MONEI_E2E_BASE_URL` naming any other site is refused rather than honoured.

## Settings

| Variable                | Required                | Meaning                                                                             |
| ----------------------- | ----------------------- | ----------------------------------------------------------------------------------- |
| `MONEI_TEST_API_KEY`    | yes                     | Test mode key of the account the suite pays with. Seeding fails on a live mode key. |
| `MONEI_TEST_ACCOUNT_ID` | no                      | Cross-checked against the account the key belongs to; a mismatch stops the run.     |
| `MONEI_E2E_WP_DIR`      | no                      | Switches from wp-env to an existing docker-compose site.                            |
| `MONEI_E2E_BASE_URL`    | with `MONEI_E2E_WP_DIR` | Public URL of that site.                                                            |

The account id is derived from the key, so `MONEI_TEST_ACCOUNT_ID` only exists to catch a key and an id that disagree.

## What the seed does

`tests/playwright/seed.js` builds the store every spec assumes, rather than leaving it to whoever set the site up. It is idempotent.

-   **Store settings** — EUR, a Spanish store address, taxes off, coupons and guest checkout on. Currency and country are load bearing: the specs pay in EUR from a Madrid address, and the express specs assert on fixed amounts.
-   **No shipping methods** — with none, `WC()->cart->needs_shipping()` is false, so checkout asks for no shipping address and express skips its shipping callbacks. Adding one changes every total.
-   **A product and a shortcode checkout page** — WooCommerce's own checkout page carries the Checkout block on a modern install, so the classic form needs a page of its own.
-   **Moves the order id sequence past `Date.now()`** — MONEI keeps `orderId` unique per account and the plugin sends the WooCommerce order id unchanged. A fresh site starts at 1, and those ids have already been paid for by earlier runs on the same account, so it fails with `The order "14" has already been paid`. A long-lived store never collides, which is why this only ever bites a fresh instance. See `reserveOrderIdRange()`.

Ids and paths land in `tests/playwright/.fixtures.json`, which is generated and gitignored. It describes the site it was seeded against, so re-seed after switching sites.

## Specs that skip

Two conditions are environmental. Both skip with a printed reason rather than failing, and both still fail normally when the condition is met.

**Card journeys need a public HTTPS store.** 3D Secure sends the browser to the issuer and back, with the challenge framed over HTTPS. `http://localhost` satisfies neither half — the page is plain HTTP, and MONEI cannot reach the host — so the challenge never renders and the run waits for a thank you page that cannot arrive. Under wp-env the card specs therefore skip. Run them against a tunnelled or hosted HTTPS store. See `supportsThreeDs()` and `THREE_DS_SKIP_REASON` in `utils/env.js`.

The gate sits on each card spec's `describe`, so it takes the whole file with it. That includes the split field focus test, which pays for nothing and would run fine over HTTP — it skips only because it shares a file with the specs that cannot.

**The Bizum component test needs an IP MONEI offers Bizum to.** Bizum is Spain only, and MONEI filters an account's client payment methods by caller IP, so outside Spain the account is told it has no Bizum and the component correctly declines to mount. Nothing is wrong with the store: the WooCommerce payment method is unaffected, because availability there is decided in PHP. The spec asks MONEI the same question the component asks and skips only where Bizum is genuinely not on offer. See `isBizumOfferedHere()` in `specs/blocks-bizum.spec.js`.

The Bizum **registration** test runs everywhere — it asks WordPress what the blocks integration registered, which no IP can change.

## In CI

The `e2e` job in `.github/workflows/tests.yml` runs on a GitHub hosted runner: it builds the plugin, boots wp-env, seeds the store, and runs Playwright.

| Secret                  | Required | Value                                                 |
| ----------------------- | -------- | ----------------------------------------------------- |
| `MONEI_TEST_API_KEY`    | yes      | Test mode API key of the account the suite pays with. |
| `MONEI_TEST_ACCOUNT_ID` | no       | Cross-checked against that key.                       |

`MONEI_TEST_API_KEY` alone starts the job. Without it the job skips with a notice instead of failing, so a fork or a clone with no MONEI account behind it still gets a green run.

Because CI serves `http://localhost:8888`, the card specs and the Bizum component test skip there. What CI does gate is express checkout end to end with a real token, express amount verification both ways, and the Bizum script registration.

To gate the card journeys as well, give the runner a publicly reachable HTTPS store — a tunnel step, or `MONEI_E2E_WP_DIR` and `MONEI_E2E_BASE_URL` pointing at staging.

Every push runs the Jest and PHPUnit jobs in the same workflow. Neither needs a MONEI account.

## Notes

The suite mutates global site state — card field layout, express settings, gateway enabled flags, the WooCommerce checkout page — so it runs single worker and non parallel, and each spec restores what it changed.
