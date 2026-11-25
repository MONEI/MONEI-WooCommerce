const crypto = require( 'crypto' );
const { test, expect } = require( '@playwright/test' );
const { login } = require( '../utils/admin' );
const { adminCredentials, baseUrl } = require( '../utils/env' );
const { getOption, setOption, wpCli } = require( '../utils/wp-cli' );

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

/**
 * The fake Live credentials a connect would leave, as connectedShop() stores them.
 */
const FAKE_LIVE = {
	monei_live_accountid: 'e2e-live-account',
	monei_live_apikey: 'mk_placeholder',
	monei_live_signing_secret: 'mksec_live_e2e',
};

/**
 * Read an option, null when absent. Unlike getOption, a failed WP-CLI call
 * throws instead of reading as empty, so a restore never writes a wrong value.
 * @param {string} name - Option name
 * @return {string|null} Value
 */
const readOption = ( name ) =>
	JSON.parse(
		wpCli( [
			'eval',
			`echo json_encode( get_option( '${ name }', null ) );`,
		] )
	);

/**
 * Give the shop a connected Live mode, as a connect leaves it. The selected mode
 * stays Test, so no request goes out with the fake key.
 * @return {Function} Puts the stored values back
 */
const connectedShop = () => {
	const names = [
		...CREDENTIALS,
		'monei_live_signing_secret',
		'monei_test_signing_secret',
		'monei_apikey_mode',
	];
	const before = {};
	for ( const name of names ) {
		const value = readOption( name );
		// A fake left by an interrupted run is not the shop's own value.
		before[ name ] = value === FAKE_LIVE[ name ] ? null : value;
	}
	for ( const [ name, value ] of Object.entries( FAKE_LIVE ) ) {
		setOption( name, value );
	}
	return () => {
		for ( const [ name, value ] of Object.entries( before ) ) {
			if ( value === null ) {
				try {
					wpCli( [ 'option', 'delete', name ] );
				} catch ( error ) {
					// It was not there to delete.
				}
			} else {
				setOption( name, value );
			}
		}
	};
};

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
		// Manual keys are the only way here, so their fields are open.
		await expect( page.locator( '#monei_test_apikey' ) ).toBeVisible();
		await expect( page.locator( '#monei-manual-keys-toggle' ) ).toHaveCount(
			0
		);
	} );

	test( 'an https shop starts at the broker with S256 and its callback', async ( {
		page,
	} ) => {
		test.skip( ! HTTPS, CONNECT_SKIP_REASON );

		await login( page );
		await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );
		// Connect is the main flow: the manual key fields wait for a click.
		await expect( page.locator( '#monei_test_apikey' ) ).toBeHidden();
		await page.locator( '#monei-manual-keys-toggle' ).click();
		await expect( page.locator( '#monei_test_apikey' ) ).toBeVisible();
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

	test( 'a shop with only manual keys has no Disconnect button', async ( {
		page,
	} ) => {
		expect(
			getOption( 'monei_test_apikey' ).startsWith( 'pk_' ),
			'the seeded store uses a manual key'
		).toBe( true );

		await login( page );
		await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );

		await expect( page.locator( '#monei-disconnect' ) ).toHaveCount( 0 );
	} );

	test( 'Enter in a settings field saves, keeps the hidden connected key and does not disconnect', async ( {
		page,
	} ) => {
		const restore = connectedShop();
		try {
			const before = credentialsDigest();
			await login( page );
			await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );
			await expect( page.locator( '#monei-disconnect' ) ).toBeVisible();
			expect(
				await page.content(),
				'the page holds no connected key'
			).not.toContain( FAKE_LIVE.monei_live_apikey );

			if ( await page.locator( '#monei-manual-keys-toggle' ).count() ) {
				await page.locator( '#monei-manual-keys-toggle' ).click();
			}
			// WooCommerce enables Save only after a change: type, then undo it.
			const field = page.locator( '#monei_test_accountid' );
			await field.press( 'End' );
			await field.press( 'x' );
			await field.press( 'Backspace' );
			await Promise.all( [
				page.waitForNavigation(),
				field.press( 'Enter' ),
			] );

			await expect(
				page.getByText( 'Your settings have been saved.' )
			).toBeVisible();
			expect( credentialsDigest(), 'credentials unchanged' ).toBe(
				before
			);
		} finally {
			restore();
		}
	} );

	test( 'a save from a tab rendered before a connect keeps the new keys', async ( {
		page,
	} ) => {
		await login( page );
		await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );
		// The connect lands in another tab after this page was rendered.
		const restore = connectedShop();
		try {
			if ( await page.locator( '#monei-manual-keys-toggle' ).count() ) {
				await page.locator( '#monei-manual-keys-toggle' ).click();
			}
			// WooCommerce enables Save only after a change: type, then undo it.
			const field = page.locator( '#monei_test_accountid' );
			await field.press( 'End' );
			await field.press( 'x' );
			await field.press( 'Backspace' );
			await Promise.all( [
				page.waitForNavigation(),
				field.press( 'Enter' ),
			] );

			await expect(
				page.getByText( 'The MONEI keys changed in another tab' )
			).toBeVisible();
			expect(
				[
					readOption( 'monei_live_accountid' ),
					readOption( 'monei_live_apikey' ),
					readOption( 'monei_live_signing_secret' ),
				],
				'the connected Live credentials stay'
			).toEqual( Object.values( FAKE_LIVE ) );
		} finally {
			restore();
		}
	} );

	test( 'Disconnect clears the connected mode and keeps a manual key', async ( {
		page,
	} ) => {
		const restore = connectedShop();
		const manualTest = [
			getOption( 'monei_test_accountid' ),
			getOption( 'monei_test_apikey' ),
		];
		try {
			await login( page );
			await page.goto( SETTINGS, { waitUntil: 'domcontentloaded' } );
			await page.locator( '#monei-disconnect' ).click();

			await expect(
				page.locator( '.notice', { hasText: 'MONEI disconnected.' } )
			).toBeVisible();
			await expect( page.locator( '#monei-disconnect' ) ).toHaveCount(
				0
			);
			expect(
				[
					getOption( 'monei_live_accountid' ),
					getOption( 'monei_live_apikey' ),
				],
				'the connected Live mode cleared'
			).toEqual( [ '', '' ] );
			expect(
				[
					getOption( 'monei_test_accountid' ),
					getOption( 'monei_test_apikey' ),
				],
				'the manual Test key kept'
			).toEqual( manualTest );
		} finally {
			restore();
		}
	} );

	test( 'an http shop gets no invitation to connect', async ( { page } ) => {
		test.skip( HTTPS, MANUAL_SKIP_REASON );
		await login( page );
		await page.goto( '/wp-admin/index.php', {
			waitUntil: 'domcontentloaded',
		} );

		await expect(
			page.locator(
				'text=Connect with MONEI to stop storing your account API key.'
			)
		).toHaveCount( 0 );
	} );

	test( 'a manual-key shop can dismiss the invitation to connect', async ( {
		page,
	} ) => {
		test.skip( ! HTTPS, CONNECT_SKIP_REASON );
		const userId = wpCli( [
			'user',
			'get',
			adminCredentials().user,
			'--field=ID',
		] );
		const notice = page.locator(
			'text=Connect with MONEI to stop storing your account API key.'
		);
		const meta = ( ...args ) => {
			try {
				return wpCli( [
					'user',
					'meta',
					...args,
					userId,
					'monei_hide_connect_notice',
				] );
			} catch ( error ) {
				// Absent: nothing to read or delete.
				return '';
			}
		};
		// A dismissal from an earlier run or by hand would hide the notice.
		const dismissedBefore = meta( 'get' );
		meta( 'delete' );
		try {
			await login( page );
			await page.goto( '/wp-admin/index.php', {
				waitUntil: 'domcontentloaded',
			} );
			await expect( notice ).toBeVisible();

			await page
				.locator( 'a[href*="monei-hide-connect-notice"]' )
				.click();
			await page.goto( '/wp-admin/index.php', {
				waitUntil: 'domcontentloaded',
			} );

			await expect( notice ).toHaveCount( 0 );
		} finally {
			meta( 'delete' );
			if ( dismissedBefore ) {
				wpCli( [
					'user',
					'meta',
					'update',
					userId,
					'monei_hide_connect_notice',
					dismissedBefore,
				] );
			}
		}
	} );
} );
