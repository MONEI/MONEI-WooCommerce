const { defineConfig, devices } = require( '@playwright/test' );

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
	expect: { timeout: 30000 },
	reporter: [
		[ 'list' ],
		[ 'html', { outputFolder: './playwright-report', open: 'never' } ],
	],
	use: {
		baseURL:
			process.env.MONEI_E2E_BASE_URL ||
			'https://pseudoangularly-unquitted-trudie.ngrok-free.dev',
		actionTimeout: 30000,
		navigationTimeout: 60000,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	projects: [ { name: 'chromium', use: { ...devices[ 'Desktop Chrome' ] } } ],
} );
