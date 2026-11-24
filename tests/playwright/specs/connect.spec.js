const crypto = require( 'crypto' );
const { test, expect } = require( '@playwright/test' );
const { login } = require( '../utils/admin' );
const { baseUrl } = require( '../utils/env' );
const { getOption, wpCli } = require( '../utils/wp-cli' );

/**
 * Connect with MONEI: the admin checks of the start, disconnect and callback
 * routes, and the Connect button.
 *
 * The suite branches on the URL the shop builds, not on whether
 * MONEI_E2E_BASE_URL is set: the broker refuses a callback URL that is not
 * https, so an http shop shows "Use manual keys" and an https shop the button.
 */
const HTTPS = baseUrl().startsWith( 'https://' );

const CONNECT_SKIP_REASON =
	`Connect with MONEI needs an https callback URL; this run targets ${ baseUrl() }. ` +
	'Set MONEI_E2E_WP_DIR and MONEI_E2E_BASE_URL to a tunnelled site to run it.';

const MANUAL_SKIP_REASON = `This run targets the https shop ${ baseUrl() }, which shows the Connect button instead of "Use manual keys".`;

const ADMIN_POST = '/wp-admin/admin-post.php';
const SETTINGS = '/wp-admin/admin.php?page=wc-settings&tab=monei_settings';
const CREDENTIALS = [
	'monei_test_accountid',
	'monei_live_accountid',
	'monei_test_apikey',
	'monei_live_apikey',
];

/**
 * Names of the stored connect states.
 * @return {string[]} Option names
 */
const stateOptions = () =>
	wpCli( [
		'option',
		'list',
		'--search=monei_connect_state_*',
		'--field=option_name',
	] )
		.split( '\n' )
		.filter( Boolean )
		.sort();

/**
 * A digest of the stored credentials, so a failed comparison never prints a key.
 * @return {string} SHA-256 of the four values
 */
const credentialsDigest = () =>
	crypto
		.createHash( 'sha256' )
		.update( JSON.stringify( CREDENTIALS.map( getOption ) ) )
		.digest( 'hex' );

test.describe( 'Connect with MONEI', () => {
	test( 'a start without the admin check stores no state', async ( {
		page,
		playwright,
	} ) => {
		const before = stateOptions();
		const anonymous = await playwright.request.newContext( {
			baseURL: baseUrl(),
		} );
		const noSession = await anonymous.get(
			`${ ADMIN_POST }?action=monei_connect_start&_wpnonce=0123456789`,
			{ maxRedirects: 0 }
		);
		await anonymous.dispose();

		await login( page );
		const noNonce = await page.request.get(
			`${ ADMIN_POST }?action=monei_connect_start`,
			{ maxRedirects: 0 }
		);
		const wrongNonce = await page.request.get(
			`${ ADMIN_POST }?action=monei_connect_start&_wpnonce=0123456789`,
			{ maxRedirects: 0 }
		);

		// admin-post.php answers 400 when no admin_post_nopriv_ hook exists, and
		// a failed nonce is wp_nonce_ays's 403.
		expect( [
			noSession.status(),
			noNonce.status(),
			wrongNonce.status(),
		] ).toEqual( [ 400, 403, 403 ] );
		expect( stateOptions() ).toEqual( before );
	} );

	test( 'a disconnect without POST and the admin check keeps the credentials', async ( {
		page,
		playwright,
	} ) => {
		const before = credentialsDigest();
		expect(
			getOption( 'monei_test_apikey' ),
			'the seeded store has a key to lose'
		).not.toBe( '' );

		const anonymous = await playwright.request.newContext( {
			baseURL: baseUrl(),
		} );
		const noSession = await anonymous.post(
			`${ ADMIN_POST }?action=monei_disconnect`,
			{ maxRedirects: 0 }
		);
		await anonymous.dispose();

		await login( page );
		await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );
		const nonce = await page
			.locator(
				'#monei-disconnect-form input[name="_monei_disconnect_nonce"]'
			)
			.inputValue();
		const get = await page.request.get(
			`${ ADMIN_POST }?action=monei_disconnect&_monei_disconnect_nonce=${ nonce }`,
			{ maxRedirects: 0 }
		);
		const noNonce = await page.request.post(
			`${ ADMIN_POST }?action=monei_disconnect`,
			{ maxRedirects: 0 }
		);
		const wrongNonce = await page.request.post(
			`${ ADMIN_POST }?action=monei_disconnect`,
			{
				form: { _monei_disconnect_nonce: '0123456789' },
				maxRedirects: 0,
			}
		);

		expect( [
			noSession.status(),
			get.status(),
			noNonce.status(),
			wrongNonce.status(),
		] ).toEqual( [ 400, 405, 403, 403 ] );
		expect( credentialsDigest(), 'credentials unchanged' ).toBe( before );
	} );

	test( 'a forged state on the callback is refused and a stored state stays', async ( {
		page,
	} ) => {
		const seed = `monei_connect_state_${ 's'.repeat( 43 ) }`;
		const entry = JSON.stringify( {
			verifier: 'seed-verifier',
			redirectUri: `${ baseUrl() }${ ADMIN_POST }?action=monei_connect`,
			createdAt: Math.floor( Date.now() / 1000 ),
		} );
		wpCli( [
			'option',
			'update',
			seed,
			entry,
			'--format=json',
			'--autoload=no',
		] );
		const before = wpCli( [ 'option', 'get', seed, '--format=json' ] );

		try {
			// Signed in, with a code: only the state check can refuse it.
			await login( page );
			const response = await page.request.get(
				`${ ADMIN_POST }?action=monei_connect&code=forged-code&state=${ 'f'.repeat(
					43
				) }`,
				{ maxRedirects: 0 }
			);

			expect( response.status() ).toBe( 400 );
			expect( response.headers()[ 'referrer-policy' ] ).toBe(
				'no-referrer'
			);
			expect( await response.text() ).toContain( 'not valid' );
			expect( wpCli( [ 'option', 'get', seed, '--format=json' ] ) ).toBe(
				before
			);
		} finally {
			wpCli( [ 'option', 'delete', seed ] );
		}
	} );

	test( 'an http shop shows "Use manual keys" and no button', async ( {
		page,
	} ) => {
		test.skip( HTTPS, MANUAL_SKIP_REASON );

		await login( page );
		await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '#monei-connect-manual' ) ).toContainText(
			'Use manual keys'
		);
		await expect( page.locator( '#monei-connect' ) ).toHaveCount( 0 );
	} );

	test( 'an https shop starts at the broker with S256 and its callback', async ( {
		page,
	} ) => {
		test.skip( ! HTTPS, CONNECT_SKIP_REASON );

		await login( page );
		await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );
		const href = await page
			.locator( '#monei-connect' )
			.getAttribute( 'href' );
		const response = await page.request.get( href, { maxRedirects: 0 } );
		// The broker page itself is not loaded: the dev blocklist may hold the
		// tunnel host.
		const location = new URL( response.headers().location );

		try {
			expect( response.status() ).toBe( 302 );
			expect( location.pathname ).toBe( '/integrations/authorize' );
			expect( location.searchParams.get( 'client_id' ) ).toBe(
				'woocommerce'
			);
			expect( location.searchParams.get( 'code_challenge_method' ) ).toBe(
				'S256'
			);
			expect( location.searchParams.get( 'redirect_uri' ) ).toBe(
				`${
					new URL( baseUrl() ).origin
				}${ ADMIN_POST }?action=monei_connect`
			);
		} finally {
			const state = location.searchParams.get( 'state' );
			if ( state ) {
				wpCli( [
					'option',
					'delete',
					`monei_connect_state_${ state }`,
				] );
			}
		}
	} );
} );
