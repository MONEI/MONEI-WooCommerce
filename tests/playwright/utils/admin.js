const { expect } = require( '@playwright/test' );
const { adminCredentials } = require( './env' );

/**
 * Sign in to wp-admin.
 *
 * ⚠️ Waits up to 60 s for the admin bar: the first wp-admin request of a fresh
 * store runs WooCommerce's first-time admin setup before the page renders.
 * @param {import('@playwright/test').Page} page - Page
 */
const login = async ( page ) => {
	const { user, password } = adminCredentials();

	await page.goto( '/wp-login.php', { waitUntil: 'load' } );
	// ⚠️ wp-login.php moves the focus to the username field shortly after load,
	// which can land a typed password there. Fill until both fields hold their
	// values.
	await expect( async () => {
		await page.locator( '#user_login' ).fill( user );
		await page.locator( '#user_pass' ).fill( password );
		await expect( page.locator( '#user_login' ) ).toHaveValue( user, {
			timeout: 1000,
		} );
		await expect( page.locator( '#user_pass' ) ).toHaveValue( password, {
			timeout: 1000,
		} );
	} ).toPass( { timeout: 15000 } );
	await page.locator( '#wp-submit' ).click();
	await expect(
		page.locator( '#wpadminbar' ),
		'wp-login accepted the admin credentials'
	).toBeVisible( { timeout: 60000 } );
};

module.exports = { login };
