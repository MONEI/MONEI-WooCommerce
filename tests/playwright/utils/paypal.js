/**
 * Driving a real PayPal sandbox payment.
 *
 * Everything here was established by watching the live flow; none of it is
 * guessable from the markup:
 *
 * - The button is inside a cross-origin iframe zoid appends to `<body>`, NOT
 *   inside `.monei-express-checkout` and NOT nested in monei.js's own
 *   `inner-paypal` frame. Its generated `name` is the only stable handle.
 * - PayPal picks its own surface for the login. Headless Chromium gets a popup
 *   window; a headed browser gets an in-page overlay iframe. Both shapes have to
 *   be accepted or the suite passes locally and hangs in CI.
 * - The sandbox account is public, published in MONEI's own testing docs.
 */

const { expect } = require( '@playwright/test' );

/**
 * Public sandbox accounts from https://docs.monei.com/testing
 */
const PAYPAL_ACCOUNTS = {
	// Pays successfully.
	personal: {
		email: 'paypal-personal@monei.net',
		password: 'monei12345',
	},
	// Declines, for the failure paths.
	rejected: {
		email: 'CCREJECT-REFUSED@paypal.com',
		password: 'PayPal2016',
	},
};

/**
 * Whether the store's MONEI account offers PayPal at all.
 *
 * ⚠️ Same shape as the Bizum guard: an account without PayPal never mounts the
 * component, so a spec that assumed it would fail for a reason that has nothing
 * to do with the code under test. Asks MONEI the same question the component
 * asks.
 * @param {string} apiKey - Test mode API key of the account under test
 * @return {Promise<boolean>} Whether PayPal is on offer
 */
const isPayPalOffered = async ( apiKey ) => {
	const response = await fetch(
		'https://api.monei.com/v1/allowed-payment-methods',
		{
			headers: {
				Authorization: apiKey,
				'User-Agent': 'MONEI-WooCommerce-E2E',
			},
			signal: AbortSignal.timeout( 30000 ),
		}
	);

	if ( ! response.ok ) {
		return false;
	}

	const body = await response.json();

	return ( body.paymentMethods || [] ).includes( 'paypal' );
};

/**
 * The express PayPal button, inside the frame zoid gave it.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {import('@playwright/test').Locator} The clickable button
 */
const expressPayPalButton = ( page ) =>
	page
		.frameLocator( 'iframe[name^="__zoid__paypal_buttons"]' )
		.locator( '[data-funding-source="paypal"], .paypal-button' )
		.first();

/**
 * Clicks the wallet button and returns whatever PayPal opened.
 *
 * ⚠️ Racing `waitForEvent('popup')` against the click is not enough, and the
 * failure it causes points at the wrong thing. The popup sometimes arrives after
 * the race gives up, and the overlay fallback then matches an iframe zoid has
 * appended as `about:blank` but not yet navigated — so the wait for the login
 * field times out on a surface that was never PayPal. Poll both instead, and
 * only accept a frame that has actually reached paypal.com.
 *
 * @param {import('@playwright/test').Page}    page      - Page under test
 * @param {import('@playwright/test').Locator} button    - The wallet button
 * @param {number}                             [timeout] - How long to keep looking
 * @return {Promise<Object>} The popup page, or the overlay frame
 */
const openPayPal = async ( page, button, timeout = 60000 ) => {
	const isLogin = ( url ) =>
		url.includes( 'paypal.com' ) && ! url.includes( 'smart/buttons' );

	await button.click();

	const deadline = Date.now() + timeout;

	while ( Date.now() < deadline ) {
		const popup = page
			.context()
			.pages()
			.find( ( other ) => other !== page && isLogin( other.url() ) );

		if ( popup ) {
			return popup;
		}

		const overlay = page
			.frames()
			.find( ( frame ) => isLogin( frame.url() ) );

		if ( overlay ) {
			return overlay;
		}

		await page.waitForTimeout( 500 );
	}

	throw new Error(
		'PayPal did not open a login surface within ' + timeout + 'ms'
	);
};

/**
 * Signs in to the sandbox and approves the payment.
 *
 * PayPal has shipped several markups for these steps, so each is matched by any
 * of the selectors it has used rather than betting on one.
 * @param {Object} surface           - Popup page or overlay frame from `openPayPal`
 * @param {Object} account           - One of `PAYPAL_ACCOUNTS`
 * @param {number} [timeout]         - Per-step timeout
 * @return {Promise<void>}
 */
const approveInPayPal = async ( surface, account, timeout = 90000 ) => {
	const anyOf = ( selectors ) =>
		surface.locator( selectors.join( ', ' ) ).first();

	const email = anyOf( [ '#email', '[name="login_email"]' ] );
	const confirm = anyOf( [
		'[data-testid="submit-button-initial"]',
		'#payment-submit-btn',
		'[data-testid="continue-button"]',
	] );

	// ⚠️ The login is not always shown. PayPal remembers a signed-in shopper and
	// drops straight to the review screen, so waiting unconditionally for the
	// email field spends the whole timeout staring at a page that is already
	// past it. Whichever appears first decides the path.
	await Promise.race( [
		email.waitFor( { state: 'visible', timeout } ),
		confirm.waitFor( { state: 'visible', timeout } ),
	] );

	if ( await email.isVisible().catch( () => false ) ) {
		await email.fill( account.email );
		await anyOf( [ '#btnNext', '[name="btnNext"]' ] ).click();

		const password = anyOf( [ '#password', '[name="login_password"]' ] );
		await password.waitFor( { state: 'visible', timeout } );
		await password.fill( account.password );
		await anyOf( [ '#btnLogin', '[name="btnLogin"]' ] ).click();
	}

	await confirm.waitFor( { state: 'visible', timeout } );
	await confirm.click();
};

/**
 * The whole express PayPal journey, up to the shopper's approval.
 *
 * Stops there deliberately: what the store does next is the thing under test,
 * and differs per spec.
 * @param {import('@playwright/test').Page} page      - Page under test
 * @param {Object}                          [account] - Sandbox account to pay with
 * @return {Promise<void>}
 */
const payWithExpressPayPal = async (
	page,
	account = PAYPAL_ACCOUNTS.personal
) => {
	const button = expressPayPalButton( page );
	await expect( button, 'the express PayPal button mounted' ).toBeVisible( {
		timeout: 60000,
	} );

	const surface = await openPayPal( page, button );
	await approveInPayPal( surface, account );
};

/**
 * What the store is telling the shopper right now, across every surface it uses.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {Promise<string[]>} Non-empty messages
 */
const storeMessages = async ( page ) => {
	const texts = await page
		.locator(
			'.monei-express-checkout__error, .wc-block-components-notice-banner, .woocommerce-error'
		)
		.allInnerTexts()
		.catch( () => [] );

	return texts.map( ( t ) => t.trim() ).filter( Boolean );
};

module.exports = {
	PAYPAL_ACCOUNTS,
	approveInPayPal,
	expressPayPalButton,
	isPayPalOffered,
	openPayPal,
	payWithExpressPayPal,
	storeMessages,
};
