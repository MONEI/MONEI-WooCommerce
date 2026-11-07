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
const {
	getCardFieldLayout,
	getOption,
	setCardFieldLayout,
	setOption,
	wpCli,
} = require( '../utils/wp-cli' );
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

	test.describe( 'guest creates an account at checkout', () => {
		const SIGNUP_OPTION =
			'woocommerce_enable_signup_and_login_from_checkout';
		const email = `e2e-signup-${ Date.now() }@example.com`;
		let previousSignup;

		test.beforeAll( () => {
			previousSignup = getOption( SIGNUP_OPTION ) || 'no';
			setOption( SIGNUP_OPTION, 'yes' );
		} );

		test.afterAll( () => {
			setOption( SIGNUP_OPTION, previousSignup );
			wpCli( [ 'user', 'delete', email, '--yes' ] );
		} );

		test( 'pays with a card', async ( { page } ) => {
			await addProductToCart( page );
			await gotoCheckout( page, '/checkout/', LAYOUT );

			await fillBlocksBilling( page );
			await page.locator( '#email' ).fill( email );
			await page.getByLabel( /create an account/i ).check();
			await fillCardholderName( page );
			await fillCard( page, LAYOUT, CARDS.visaFrictionless );

			// WooCommerce logs the new customer in before the payment, in the
			// same request. The card token is bound to the guest session, so
			// MONEI refuses it ("Invalid payment method") unless the plugin
			// sends the guest session ID.
			await page.getByRole( 'button', { name: /place order/i } ).click();
			await completeThreeDsChallengeIfShown( page );

			const orderId = await expectOrderReceived( page );
			expect(
				wpCli( [ 'user', 'get', email, '--field=ID' ] ),
				'checkout created the account'
			).toBe(
				wpCli( [
					'eval',
					`echo wc_get_order( ${ orderId } )->get_customer_id();`,
				] )
			);
		} );
	} );
} );
