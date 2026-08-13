const { test, expect } = require( '@playwright/test' );
const {
	CARDS,
	addProductToCart,
	completeThreeDsChallenge,
	completeThreeDsChallengeIfShown,
	expectOrderReceived,
	fillBlocksBilling,
	fillCard,
	fillCardholderName,
	gotoCheckout,
} = require( '../utils/checkout' );
const { getCardFieldLayout, setCardFieldLayout } = require( '../utils/wp-cli' );
const { THREE_DS_SKIP_REASON, supportsThreeDs } = require( '../utils/env' );

const LAYOUT = 'single';

let previousLayout;

test.describe( 'Block checkout, single card field', () => {
	// Every case here places a card order, and this account challenges the test
	// cards, so the whole file depends on a 3DS round trip.
	test.skip( ! supportsThreeDs(), THREE_DS_SKIP_REASON );

	test.beforeAll( () => {
		previousLayout = getCardFieldLayout();
		setCardFieldLayout( LAYOUT );
	} );

	test.afterAll( () => {
		// A failed beforeAll leaves this unset, and writing an empty layout
		// back would take the setting off the site.
		if ( previousLayout ) {
			setCardFieldLayout( previousLayout );
		}
	} );

	test( 'pays with a card', async ( { page } ) => {
		await addProductToCart( page );
		await gotoCheckout( page, '/checkout/', LAYOUT );

		await fillBlocksBilling( page );
		await fillCardholderName( page );
		await fillCard( page, LAYOUT, CARDS.visaFrictionless );

		await page.getByRole( 'button', { name: /place order/i } ).click();
		await completeThreeDsChallengeIfShown( page );

		const orderId = await expectOrderReceived( page );
		expect( Number( orderId ) ).toBeGreaterThan( 0 );
	} );

	test( 'shows the 3DS challenge and completes the payment', async ( {
		page,
	} ) => {
		await addProductToCart( page );
		await gotoCheckout( page, '/checkout/', LAYOUT );

		await fillBlocksBilling( page );
		await fillCardholderName( page );
		await fillCard( page, LAYOUT, CARDS.visaChallenge );

		await page.getByRole( 'button', { name: /place order/i } ).click();

		// The challenge is mandatory for this card, so this must not be
		// tolerant of its absence.
		await completeThreeDsChallenge( page );

		const orderId = await expectOrderReceived( page );
		expect( Number( orderId ) ).toBeGreaterThan( 0 );
	} );
} );
