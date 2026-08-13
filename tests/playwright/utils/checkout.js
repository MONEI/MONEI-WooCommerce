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
	CARD_CVC,
	CARD_EXPIRY,
	PRODUCT_ID,
	PRODUCT_PATH,
	SINGLE_MOUNT,
	SPLIT_MOUNT,
	addProductToCart,
	cardInput,
	challengeCompleteButtons,
	completeThreeDsChallenge,
	completeThreeDsChallengeIfShown,
	expectOrderReceived,
	fillBlocksBilling,
	fillCard,
	fillCardholderName,
	fillClassicBilling,
	gotoCheckout,
	mountSelector,
	readBlocksTotal,
	typeIntoCardInput,
	waitForCardFields,
};
