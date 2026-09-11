const { defineConfig, devices } = require( '@playwright/test' );
const { baseUrl } = require( './utils/env' );

/**
 * Playwright config for the MONEI card payment E2E suite.
 *
 * The suite drives a real WordPress site with a real MONEI test account, so it
 * mutates global site state (card field layout, WooCommerce checkout page).
 * That is why it runs single worker and non parallel.
 */
module.exports = defineConfig( {
	testDir: './specs',
	outputDir: './test-results',
	fullyParallel: false,
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,
	// MONEI mounts its iframes after a 500ms init delay, then a real payment
	// round trip follows, so give each test room.
	timeout: 180000,
	// Same convention as monei-js: one set of baselines, rendered on macOS, and
	// Linux CI compares against them. The tolerance absorbs the cross-platform
	// anti-aliasing drift; CI installs Source Sans Pro so that drift stays
	// small. Baselines have to come from wp-env, not a docker-compose store —
	// .wp-env.json pins WordPress, WooCommerce and the theme, and a store on
	// other versions renders differently for reasons that are not regressions.
	snapshotPathTemplate:
		'{testDir}/{testFilePath}-snapshots/{arg}-{projectName}-darwin{ext}',
	// Locally a missing baseline is written and the run passes, which is how
	// baselines are made. On CI the same default would turn "nobody committed
	// the PNG" into a green build. There, a missing baseline is a failure.
	updateSnapshots: process.env.CI ? 'none' : 'missing',
	expect: {
		timeout: 30000,
		toHaveScreenshot: {
			threshold: 0.3,
			// Tight enough that a regression on one small element still fails.
			// A per-test bump must carry a comment saying what drift it absorbs.
			maxDiffPixelRatio: 0.1,
			animations: 'disabled',
		},
	},
	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: './playwright-report', open: 'never' } ],
	],
	use: {
		baseURL: baseUrl(),
		actionTimeout: 30000,
		navigationTimeout: 60000,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			testIgnore: /payment-methods-rendering\.spec\.js/,
			use: { ...devices[ 'Desktop Chrome' ] },
		},
		{
			// Screenshot comparisons. No retries: a comparison that passes on the
			// second attempt is nondeterministic, and a retry would hide that.
			name: 'visual',
			testMatch: /payment-methods-rendering\.spec\.js/,
			retries: 0,
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
