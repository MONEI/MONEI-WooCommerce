const { test, expect } = require( '@playwright/test' );
const {
	CARDS,
	CARD_CVC,
	CARD_EXPIRY,
	addProductToCart,
	completeThreeDsChallengeIfShown,
	cardInput,
	expectOrderReceived,
	fillBlocksBilling,
	fillCard,
	fillCardholderName,
	gotoCheckout,
	readBlocksTotal,
	typeIntoCardInput,
} = require( '../utils/checkout' );
const {
	ensureCoupon,
	getCardFieldLayout,
	setCardFieldLayout,
} = require( '../utils/wp-cli' );

const LAYOUT = 'split';
const COUPON = 'e2emonei10';

let previousLayout;

test.describe( 'Block checkout, split card fields', () => {
	test.beforeAll( () => {
		previousLayout = getCardFieldLayout();
		setCardFieldLayout( LAYOUT );
		ensureCoupon( COUPON, 10 );
	} );

	test.afterAll( () => {
		setCardFieldLayout( previousLayout );
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

	test( 'advances focus from number to expiry to CVC', async ( { page } ) => {
		await addProductToCart( page );
		await gotoCheckout( page, '/checkout/', LAYOUT );

		await typeIntoCardInput(
			cardInput( page, LAYOUT, 'number' ),
			CARDS.visaFrictionless
		);

		await expect(
			page.locator( '#monei-card-expiry' ),
			'expiry field takes focus once the number is complete'
		).toHaveClass( /monei-component--focus/ );

		// Typing without clicking proves focus really moved into the expiry
		// frame, not just that a class was toggled.
		await page.keyboard.type( CARD_EXPIRY, { delay: 40 } );
		await expect( cardInput( page, LAYOUT, 'expiry' ) ).toHaveValue(
			'12 / 34'
		);

		await expect(
			page.locator( '#monei-card-cvc' ),
			'CVC field takes focus once the expiry is complete'
		).toHaveClass( /monei-component--focus/ );

		await page.keyboard.type( CARD_CVC, { delay: 40 } );
		await expect( cardInput( page, LAYOUT, 'cvc' ) ).toHaveValue(
			CARD_CVC
		);
	} );

	test( 'keeps typed card digits when the cart total changes', async ( {
		page,
	} ) => {
		await addProductToCart( page );
		await gotoCheckout( page, '/checkout/', LAYOUT );

		const numberInput = cardInput( page, LAYOUT, 'number' );
		await typeIntoCardInput( numberInput, '444444444444' );
		const typedDigits = await numberInput.inputValue();
		expect( typedDigits ).toBe( '4444 4444 4444' );

		const totalBefore = await readBlocksTotal( page );

		await page.getByRole( 'button', { name: /add coupons?/i } ).click();
		const couponInput = page.locator(
			'#wc-block-components-totals-coupon__input-coupon'
		);
		await couponInput.waitFor( { state: 'visible' } );
		await couponInput.fill( COUPON );
		await page.getByRole( 'button', { name: /^apply$/i } ).click();

		await expect(
			page.locator( '.wc-block-components-totals-footer-item' ),
			'cart total really changed, otherwise this test proves nothing'
		).not.toHaveText( totalBefore );

		// The regression this guards: re-creating the card component on an
		// amount change mounts a second iframe and wipes the number the
		// shopper already typed.
		await expect(
			page.locator( '#monei-card-number iframe' ),
			'card component was not re-created'
		).toHaveCount( 1 );
		await expect(
			numberInput,
			'typed card digits survive the amount change'
		).toHaveValue( typedDigits );

		// The component must also still be usable, and the payment must go
		// through on the new amount.
		await fillBlocksBilling( page );
		await fillCardholderName( page );
		await typeIntoCardInput( numberInput, '4414' );
		await expect( numberInput ).toHaveValue( '4444 4444 4444 4414' );
		await typeIntoCardInput(
			cardInput( page, LAYOUT, 'expiry' ),
			CARD_EXPIRY
		);
		await typeIntoCardInput( cardInput( page, LAYOUT, 'cvc' ), CARD_CVC );

		await page.getByRole( 'button', { name: /place order/i } ).click();
		await completeThreeDsChallengeIfShown( page );

		const orderId = await expectOrderReceived( page );
		expect( Number( orderId ) ).toBeGreaterThan( 0 );
	} );
} );
