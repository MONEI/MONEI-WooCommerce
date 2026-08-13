const { test, expect } = require( '@playwright/test' );
const {
	CARDS,
	addProductToCart,
	completeThreeDsChallengeIfShown,
	expectOrderReceived,
	fillCard,
	fillCardholderName,
	fillClassicBilling,
	gotoCheckout,
} = require( '../utils/checkout' );
const {
	getCardFieldLayout,
	getCheckoutPageId,
	setCardFieldLayout,
	setCheckoutPageId,
} = require( '../utils/wp-cli' );
const { THREE_DS_SKIP_REASON, supportsThreeDs } = require( '../utils/env' );

const { fixture } = require( '../utils/fixtures' );

const LAYOUT = 'single';
const CLASSIC_CHECKOUT_PAGE_ID = fixture(
	'classicCheckoutPageId',
	'MONEI_E2E_CLASSIC_CHECKOUT_PAGE_ID',
	'31'
);
const CLASSIC_CHECKOUT_PATH = fixture(
	'classicCheckoutPath',
	'MONEI_E2E_CLASSIC_CHECKOUT_PATH',
	'/classic-checkout/'
);

let previousLayout;
let previousCheckoutPageId;

test.describe( 'Classic checkout, single card field', () => {
	// Every case here places a card order, and this account challenges the test
	// cards, so the whole file depends on a 3DS round trip.
	test.skip( ! supportsThreeDs(), THREE_DS_SKIP_REASON );

	test.beforeAll( () => {
		previousLayout = getCardFieldLayout();
		previousCheckoutPageId = getCheckoutPageId();
		setCardFieldLayout( LAYOUT );
		// The gateway only enqueues its card scripts on the configured
		// checkout page, so the shortcode page has to become that page.
		setCheckoutPageId( CLASSIC_CHECKOUT_PAGE_ID );
	} );

	test.afterAll( () => {
		// Restores run even when the test above failed, so the site never
		// stays pointed at the shortcode page. A failed beforeAll leaves the
		// previous values unset, and writing those back would take the
		// settings off the site.
		if ( previousCheckoutPageId ) {
			setCheckoutPageId( previousCheckoutPageId );
		}
		if ( previousLayout ) {
			setCardFieldLayout( previousLayout );
		}
	} );

	test( 'pays with a card', async ( { page } ) => {
		await addProductToCart( page );
		await gotoCheckout( page, CLASSIC_CHECKOUT_PATH, LAYOUT );

		await fillClassicBilling( page );
		await fillCardholderName( page );
		await fillCard( page, LAYOUT, CARDS.visaFrictionless );

		await page.locator( '#place_order' ).click();
		await completeThreeDsChallengeIfShown( page );

		const orderId = await expectOrderReceived( page );
		expect( Number( orderId ) ).toBeGreaterThan( 0 );
	} );
} );
