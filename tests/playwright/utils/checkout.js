const { expect } = require( '@playwright/test' );
const { fixture } = require( './fixtures' );

/**
 * Simple product added to the cart for every checkout run.
 */
const PRODUCT_ID = fixture( 'productId', 'MONEI_E2E_PRODUCT_ID', '24' );

/**
 * Permalink of that product.
 */
const PRODUCT_PATH = fixture(
	'productPath',
	'MONEI_E2E_PRODUCT_PATH',
	'/product/t-shirt-with-logo/'
);

/**
 * MONEI test cards. Expiry 12/34 and CVC 123 apply to all of them.
 */
const CARDS = {
	// Documented as frictionless, but whether a challenge shows also depends on
	// the account risk rules, so tests using it must tolerate a challenge.
	visaFrictionless: '4444444444444414',
	// 3DS v2.1, always challenged.
	visaChallenge: '4444444444444406',
};

const CARD_EXPIRY = '1234';
const CARD_CVC = '123';

/**
 * Test ids of the inputs MONEI renders inside its card iframes.
 */
const CARD_PART_TEST_ID = {
	number: 'card-number-input',
	expiry: 'expiry-date-input',
	cvc: 'cvc-input',
};

/**
 * Mount containers the plugin renders for the split layout.
 */
const SPLIT_MOUNT = {
	number: '#monei-card-number',
	expiry: '#monei-card-expiry',
	cvc: '#monei-card-cvc',
};

/**
 * Mount container the plugin renders for the single layout.
 */
const SINGLE_MOUNT = '#monei-card-input';

/**
 * Customer account the seed creates. Not a secret: a local test store.
 */
const SHOPPER = {
	username: 'e2e-shopper',
	email: 'e2e-shopper@example.com',
	password: 'e2e-shopper-pass',
};

const BILLING = {
	email: 'e2e-monei@example.com',
	firstName: 'Ada',
	lastName: 'Lovelace',
	address: 'Calle Mayor 1',
	postcode: '28013',
	city: 'Madrid',
	state: 'Madrid',
	stateCode: 'M',
	country: 'ES',
	phone: '600000000',
};

/**
 * Mount container for a card part in the active layout.
 * @param {'single'|'split'}          layout - Card field layout
 * @param {'number'|'expiry'|'cvc'}   part   - Card part
 * @return {string} CSS selector
 */
const mountSelector = ( layout, part ) =>
	layout === 'split' ? SPLIT_MOUNT[ part ] : SINGLE_MOUNT;

/**
 * Locator for an input inside a MONEI card iframe.
 * @param {import('@playwright/test').Page} page   - Page under test
 * @param {'single'|'split'}                layout - Card field layout
 * @param {'number'|'expiry'|'cvc'}         part   - Card part
 * @return {import('@playwright/test').Locator} Input locator
 */
const cardInput = ( page, layout, part ) =>
	page
		.frameLocator( `${ mountSelector( layout, part ) } iframe` )
		.getByTestId( CARD_PART_TEST_ID[ part ] );

/**
 * A first checkout paint pulls the cart over the network, then the MONEI SDK,
 * then waits out the plugin init delay, so the first mount needs more room than
 * a normal action.
 */
const CARD_MOUNT_TIMEOUT = 60000;

/**
 * Wait until the MONEI card fields are mounted and accept typing.
 * @param {import('@playwright/test').Page} page    - Page under test
 * @param {'single'|'split'}                layout  - Card field layout
 * @param {number}                          timeout - How long to wait per field
 */
const waitForCardFields = async ( page, layout, timeout ) => {
	for ( const part of [ 'number', 'expiry', 'cvc' ] ) {
		const input = cardInput( page, layout, part );
		await input.waitFor( { state: 'visible', timeout } );
		await expect( input ).toBeEditable( { timeout } );
	}
};

/**
 * Smallest box a mounted MONEI component can honestly occupy. A card field is
 * 50px tall; a wallet button 40. Anything under this is not rendered, whatever
 * the DOM says. Width is per call: a full-width field is 200+, but the expiry
 * and CVC parts of the split layout share a row at half that.
 */
const MOUNTED_MIN_HEIGHT = 30;

/**
 * Assert a MONEI component actually occupies space on the page.
 *
 * ⚠️ A component can mount as a perfectly well-formed iframe with the right
 * src and every expected element inside it — at 0px tall. Every DOM check
 * passes, and the shopper sees nothing. That shipped once in the Magento
 * plugin. A screenshot comparison catches it, but reports "image differs"
 * with no hint; this fails first, and says which box collapsed.
 * @param {import('@playwright/test').Locator} container  - Mount container
 * @param {string}                             label      - What it is, for the failure
 * @param {number}                             [minWidth] - Narrowest honest width
 */
const expectMounted = async ( container, label, minWidth = 200 ) => {
	// ⚠️ `:visible`, not `.first()`. monei.js's PayPal mounts a 0px bridge
	// iframe first and the button the shopper sees is a sibling frame, so the
	// first iframe in DOM order can be the one that is meant to have no size.
	const frame = container.locator( 'iframe:visible' ).first();
	await expect( frame, `${ label }: a visible iframe` ).toBeVisible();

	for ( const [ what, locator ] of [
		[ 'container', container ],
		[ 'iframe', frame ],
	] ) {
		const box = await locator.boundingBox();
		expect( box, `${ label }: ${ what } has a box` ).not.toBeNull();
		expect(
			box.height,
			`${ label }: ${ what } height (${ box.width }x${ box.height })`
		).toBeGreaterThanOrEqual( MOUNTED_MIN_HEIGHT );
		expect(
			box.width,
			`${ label }: ${ what } width (${ box.width }x${ box.height })`
		).toBeGreaterThanOrEqual( minWidth );
	}
};

/**
 * Assert the input inside a card iframe sits in the vertical middle of it.
 *
 * ⚠️ A pixel comparison does not catch this. The plugin used to hand the frame
 * the mount's outer height, so the input overflowed its iframe by the border
 * and its text sat 1px above centre — a shift small enough to pass the
 * screenshot tolerance that absorbs cross-platform drift, and large enough for
 * a merchant to see. The centre is a number; assert the number.
 * @param {import('@playwright/test').Page}    page  - Page under test
 * @param {string}                             mount - Mount container selector
 * @param {import('@playwright/test').Locator} input - The input inside its iframe
 * @param {string}                             label - What it is, for the failure
 */
const expectCentred = async ( page, mount, input, label ) => {
	const iframeHeight = await page
		.locator( `${ mount } iframe:visible` )
		.first()
		.evaluate( ( frame ) => frame.getBoundingClientRect().height );
	const { offset, overflow } = await input.evaluate( ( el, frameHeight ) => {
		const box = el.getBoundingClientRect();
		return {
			offset: box.top + box.height / 2 - frameHeight / 2,
			overflow: Math.max( 0, box.bottom - frameHeight ),
		};
	}, iframeHeight );
	expect(
		Math.abs( offset ),
		`${ label }: input centre is ${ offset.toFixed(
			1
		) }px off the iframe centre`
	).toBeLessThanOrEqual( 0.5 );
	expect(
		overflow,
		`${ label }: input overflows its iframe by ${ overflow.toFixed( 1 ) }px`
	).toBe( 0 );
};

/**
 * Click an input inside a card iframe and type into it.
 * @param {import('@playwright/test').Locator} input - Input locator
 * @param {string}                             text  - Text to type
 */
const typeIntoCardInput = async ( input, text ) => {
	await input.waitFor( { state: 'visible' } );
	await expect( input ).toBeEditable();
	await input.click();
	// A click puts the caret where it landed, so anchor to the end before
	// appending.
	await input.press( 'End' );
	// Keystroke by keystroke: MONEI formats and auto advances on key events,
	// which a direct value set would bypass.
	await input.pressSequentially( text, { delay: 40 } );
};

/**
 * Fill every card field.
 * @param {import('@playwright/test').Page} page   - Page under test
 * @param {'single'|'split'}                layout - Card field layout
 * @param {string}                          number - Card number
 */
const fillCard = async ( page, layout, number ) => {
	await waitForCardFields( page, layout );
	await typeIntoCardInput( cardInput( page, layout, 'number' ), number );
	await typeIntoCardInput( cardInput( page, layout, 'expiry' ), CARD_EXPIRY );
	await typeIntoCardInput( cardInput( page, layout, 'cvc' ), CARD_CVC );
	await expect( cardInput( page, layout, 'expiry' ) ).toHaveValue(
		'12 / 34'
	);
	await expect( cardInput( page, layout, 'cvc' ) ).toHaveValue( CARD_CVC );
};

/**
 * Sign in as the seeded customer.
 *
 * Through WooCommerce's own My Account form, not wp-login. wp-login lands a
 * customer on wp-admin, and the first wp-admin request of a fresh store runs
 * WooCommerce's first-time admin setup — long enough to time out a navigation
 * wait, and the same request that flips "coming soon" on. My Account posts to
 * itself and touches none of that.
 * @param {import('@playwright/test').Page} page - Page under test
 */
const loginAsShopper = async ( page ) => {
	await page.goto( '/my-account/', { waitUntil: 'domcontentloaded' } );
	await page.locator( '#username' ).fill( SHOPPER.username );
	await page.locator( '#password' ).fill( SHOPPER.password );
	await page.locator( 'button[name="login"]' ).click();
	await expect(
		page.locator( 'body' ),
		'My Account accepted the seeded customer'
	).toHaveClass( /logged-in/, { timeout: 60000 } );
};

/**
 * Put the test product in the cart.
 * @param {import('@playwright/test').Page} page - Page under test
 */
const addProductToCart = async ( page ) => {
	await page.goto( `/?add-to-cart=${ PRODUCT_ID }`, {
		waitUntil: 'domcontentloaded',
	} );
};

/**
 * Open a checkout page and wait for the MONEI card fields.
 * @param {import('@playwright/test').Page} page   - Page under test
 * @param {string}                          path   - Checkout path
 * @param {'single'|'split'}                layout - Card field layout
 */
const gotoCheckout = async ( page, path, layout ) => {
	await page.goto( path, { waitUntil: 'domcontentloaded' } );
	// Separates "the checkout never rendered" from "the card fields never
	// mounted" when this fails.
	await expect(
		page.getByTestId( 'cardholder-name-input' ),
		'checkout rendered the MONEI card form'
	).toBeVisible( { timeout: CARD_MOUNT_TIMEOUT } );
	await waitForCardFields( page, layout, CARD_MOUNT_TIMEOUT );
};

/**
 * Fill the block checkout billing form.
 * @param {import('@playwright/test').Page} page - Page under test
 */
const fillBlocksBilling = async ( page ) => {
	await page.locator( '#email' ).fill( BILLING.email );
	await page.locator( '#billing-country' ).selectOption( BILLING.country );
	await page.locator( '#billing-first_name' ).fill( BILLING.firstName );
	await page.locator( '#billing-last_name' ).fill( BILLING.lastName );
	await page.locator( '#billing-address_1' ).fill( BILLING.address );
	await page.locator( '#billing-postcode' ).fill( BILLING.postcode );
	await page.locator( '#billing-city' ).fill( BILLING.city );
	await page
		.locator( '#billing-state' )
		.selectOption( { label: BILLING.state } );
	await page.locator( '#billing-phone' ).fill( BILLING.phone );
};

/**
 * Fill the classic checkout billing form.
 *
 * Country and state are Select2 widgets, so the native select is hidden and can
 * only be driven through jQuery, which is what WooCommerce itself listens to.
 * @param {import('@playwright/test').Page} page - Page under test
 */
const fillClassicBilling = async ( page ) => {
	await page.evaluate(
		( billing ) => {
			window
				.jQuery( '#billing_country' )
				.val( billing.country )
				.trigger( 'change' );
			window
				.jQuery( '#billing_state' )
				.val( billing.stateCode )
				.trigger( 'change' );
		},
		{ country: BILLING.country, stateCode: BILLING.stateCode }
	);
	await page.locator( '#billing_first_name' ).fill( BILLING.firstName );
	await page.locator( '#billing_last_name' ).fill( BILLING.lastName );
	await page.locator( '#billing_address_1' ).fill( BILLING.address );
	await page.locator( '#billing_postcode' ).fill( BILLING.postcode );
	await page.locator( '#billing_city' ).fill( BILLING.city );
	await page.locator( '#billing_phone' ).fill( BILLING.phone );
	await page.locator( '#billing_email' ).fill( BILLING.email );
};

/**
 * Fill the cardholder name field, which the plugin renders outside the iframes.
 * @param {import('@playwright/test').Page} page - Page under test
 */
const fillCardholderName = async ( page ) => {
	await page
		.getByTestId( 'cardholder-name-input' )
		.fill( `${ BILLING.firstName } ${ BILLING.lastName }` );
};

/**
 * MONEI renders the 3DS challenge in a payment modal iframe, which in turn
 * embeds the issuer page. In test mode the issuer page is MONEI's challenge
 * simulator.
 */
const PAYMENT_MODAL_FRAME = 'iframe[title="monei_payment_modal"]';

const CHALLENGE_TIMEOUT = 60000;

/**
 * The two places the challenge simulator can appear: the block checkout keeps
 * the shopper on the page and opens a modal, the classic checkout redirects the
 * whole browser to the issuer page.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {import('@playwright/test').Locator[]} Complete button candidates
 */
const challengeCompleteButtons = ( page ) => [
	page.getByTestId( 'complete-button' ),
	page
		.frameLocator( PAYMENT_MODAL_FRAME )
		.frameLocator( 'iframe' )
		.getByTestId( 'complete-button' ),
];

/**
 * The challenge button that is on screen, if any.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {Promise<import('@playwright/test').Locator|null>} Visible button
 */
const visibleChallengeButton = async ( page ) => {
	for ( const button of challengeCompleteButtons( page ) ) {
		if ( await button.isVisible().catch( () => false ) ) {
			return button;
		}
	}
	return null;
};

/**
 * Wait until a challenge button shows, the order completes, or time runs out.
 * @param {import('@playwright/test').Page} page       - Page under test
 * @param {boolean}                         watchOrder - Also stop on the thank you page
 */
const waitForChallengeOutcome = ( page, watchOrder ) =>
	Promise.race( [
		...( watchOrder
			? [
					page
						.waitForURL( /order-received/, {
							timeout: CHALLENGE_TIMEOUT,
						} )
						.catch( () => {} ),
			  ]
			: [] ),
		...challengeCompleteButtons( page ).map( ( button ) =>
			button
				.waitFor( { state: 'visible', timeout: CHALLENGE_TIMEOUT } )
				.catch( () => {} )
		),
	] );

/**
 * Activate the challenge button.
 *
 * In the block checkout the button sits two cross origin iframes deep, and
 * synthetic mouse events do not reach that far down the frame tree. Keyboard
 * input follows focus, so it does.
 * @param {import('@playwright/test').Locator} button - Complete button locator
 * @param {import('@playwright/test').Page}    page   - Page under test
 */
const activateChallengeButton = async ( button, page ) => {
	await button.focus();
	await page.keyboard.press( 'Enter' );
};

/**
 * Wait for the 3DS challenge and authenticate it. Fails if no challenge shows.
 * @param {import('@playwright/test').Page} page - Page under test
 */
const completeThreeDsChallenge = async ( page ) => {
	await waitForChallengeOutcome( page, false );
	const button = await visibleChallengeButton( page );
	expect( button, '3DS challenge is displayed' ).not.toBeNull();
	await activateChallengeButton( button, page );
};

/**
 * Authenticate the 3DS challenge when one is shown.
 *
 * Whether a card is challenged depends on the MONEI account risk rules, so a
 * happy path test must handle both outcomes without weakening its final
 * assertion that a real order completed.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {Promise<boolean>} Whether a challenge was answered
 */
const completeThreeDsChallengeIfShown = async ( page ) => {
	await waitForChallengeOutcome( page, true );
	const button = await visibleChallengeButton( page );
	if ( ! button ) {
		return false;
	}
	await activateChallengeButton( button, page );
	return true;
};

/**
 * Read the block checkout order total, once it has a currency amount in it.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {Promise<string>} Total text
 */
const readBlocksTotal = async ( page ) => {
	const total = page.locator( '.wc-block-components-totals-footer-item' );
	await expect( total ).toContainText( /\d/ );
	return total.textContent();
};

/**
 * Assert the browser landed on a completed order, and return the order id.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {Promise<string>} WooCommerce order id
 */
const expectOrderReceived = async ( page ) => {
	// A challenge can still be rendering when the caller's tolerant wait gives
	// up: that wait cannot tell "timed out" from "no challenge", so on a slow
	// runner it reports neither and leaves the page sitting on an unanswered
	// challenge until this wait also expires. Keep answering challenges here
	// until the order actually lands.
	const deadline = Date.now() + 120000;

	for (;;) {
		const remaining = deadline - Date.now();

		if ( remaining <= 0 ) {
			// Let waitForURL raise its own timeout error, which names the URL.
			await page.waitForURL( /order-received/, { timeout: 1 } );
		}

		try {
			await page.waitForURL( /order-received/, {
				timeout: Math.min( remaining, CHALLENGE_TIMEOUT ),
			} );
			break;
		} catch ( error ) {
			const button = await visibleChallengeButton( page );

			// No challenge to answer yet. The order may simply still be in
			// flight, so keep waiting until the deadline rather than giving up
			// on this slice — the deadline check above raises when it expires.
			if ( button ) {
				await activateChallengeButton( button, page );
			}
		}
	}

	// The confirmation page carries both the classic body class and the block
	// status element, so this must not assert on a strict single match.
	await expect(
		page
			.locator(
				'.woocommerce-order-received, .wc-block-order-confirmation-status'
			)
			.first()
	).toBeVisible();
	await expect( page.locator( 'body' ) ).not.toContainText(
		/order (has failed|failed)/i
	);
	const orderId = page.url().match( /order-received\/(\d+)/ );
	expect( orderId, 'order id in the thank you URL' ).not.toBeNull();
	return orderId[ 1 ];
};

module.exports = {
	BILLING,
	CARDS,
	SHOPPER,
	CARD_CVC,
	CARD_EXPIRY,
	PRODUCT_ID,
	PRODUCT_PATH,
	SINGLE_MOUNT,
	SPLIT_MOUNT,
	addProductToCart,
	cardInput,
	expectMounted,
	expectCentred,
	challengeCompleteButtons,
	completeThreeDsChallenge,
	completeThreeDsChallengeIfShown,
	expectOrderReceived,
	fillBlocksBilling,
	fillCard,
	fillCardholderName,
	fillClassicBilling,
	gotoCheckout,
	loginAsShopper,
	mountSelector,
	readBlocksTotal,
	typeIntoCardInput,
	waitForCardFields,
};
