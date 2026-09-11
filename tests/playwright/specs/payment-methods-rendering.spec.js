/**
 * How the payment methods render at checkout.
 *
 * Every state here is one that shipped broken at least once: the card field a
 * different height from the cardholder input beside it, the wallet container
 * spilling past its column, the save-card checkbox with no gap above it, a
 * focus ring the theme lost. None of those fail a functional test — the order
 * still goes through — so this is the only thing that catches them.
 *
 * Each case asserts geometry first, then compares pixels. The geometry assert
 * names the box that collapsed; a pixel diff only says "image differs".
 *
 * ⚠️ Baselines are macOS renders of the wp-env store (see playwright.config.js).
 * Generate and refresh them against `pnpm test:e2e:start`, never against a
 * docker-compose store: .wp-env.json pins WordPress, WooCommerce and the theme,
 * and a store on other versions renders differently for reasons that are not
 * regressions. Refresh with `pnpm test:e2e -- --project=visual -u`, then look
 * at every changed PNG before committing it — the diff is the review.
 *
 * ⚠️ The MONEI card fields are cross-origin iframes rendered by monei.js. A
 * monei.js UI change fails these comparisons with no plugin change. That is
 * accepted: the merchant sees the same thing, and someone has to look at it.
 * PayPal's and Google's own buttons are different — their art changes on the
 * vendor's schedule — so those iframes are masked and only their box is
 * asserted. The plugin owns the container, not the button.
 *
 * Same shape as the PrestaShop and Magento suites, so a regression found on
 * one platform can be checked on the other two by name.
 */

const { test, expect } = require( '@playwright/test' );
const {
	addProductToCart,
	cardInput,
	expectMounted,
	fillBlocksBilling,
	gotoCheckout,
	loginAsShopper,
	mountSelector,
	typeIntoCardInput,
} = require( '../utils/checkout' );
const { readFixtures } = require( '../utils/fixtures' );
const { isBizumOfferedHere } = require( '../utils/bizum' );
const { isPayPalOffered } = require( '../utils/paypal' );
const {
	getAccountId,
	getCardFieldLayout,
	getCheckoutPageId,
	getExpressSettings,
	getGatewayEnabled,
	mergeSettings,
	setCardFieldLayout,
	setCheckoutPageId,
	setExpressSettings,
	setGatewayEnabled,
} = require( '../utils/wp-cli' );

const CARD_OPTION = 'woocommerce_monei_settings';
const BIZUM_OPTION = 'woocommerce_monei_bizum_settings';
const PAYPAL_OPTION = 'woocommerce_monei_paypal_settings';
const WALLET_OPTION = 'woocommerce_monei_apple_google_settings';

const fixtures = readFixtures();

/**
 * The two checkouts, and the element that holds their payment methods.
 */
const CHECKOUTS = {
	blocks: { path: '/checkout/', panel: '.wc-block-checkout__payment-method' },
	classic: { path: fixtures.classicCheckoutPath, panel: '#payment' },
};

/**
 * Card number that fails the Luhn check, so monei.js marks the field invalid
 * without any network round trip.
 */
const LUHN_INVALID = '4242424242424241';

/**
 * PayPal's button iframe. zoid appends it to the body, not to the container
 * it renders into, so a container-scoped mask would miss it while the element
 * screenshot still captures it overlapping the box.
 */
const ZOID_PAYPAL = 'iframe[name^="__zoid__paypal_buttons"]';

/**
 * How long a component gets to mount after its method is selected. The SDK
 * is already on the page by then; this is the component's own round trip.
 */
const MOUNT_TIMEOUT = 60000;

/**
 * Settle the page before a comparison.
 *
 * Web fonts arrive after first paint; a comparison taken before them shows
 * fallback glyphs and fails on the wrong thing. `toHaveScreenshot` already
 * disables CSS animations and hides the caret.
 * @param {import('@playwright/test').Page} page - Page under test
 */
const settle = async ( page ) => {
	await page.evaluate( () => document.fonts.ready );
};

/**
 * Open a checkout with the card fields mounted, ready to be photographed.
 * @param {import('@playwright/test').Page} page     - Page under test
 * @param {'blocks'|'classic'}              checkout - Which checkout
 * @param {'single'|'split'}                layout   - Card field layout
 * @return {import('@playwright/test').Locator} The payment methods panel
 */
const openPaymentPanel = async ( page, checkout, layout ) => {
	// The classic gateway enqueues its card scripts only on the page WooCommerce
	// considers the checkout page, so the shortcode page has to become that
	// page for as long as it is photographed. afterEach puts it back.
	if ( checkout === 'classic' ) {
		setCheckoutPageId( fixtures.classicCheckoutPageId );
	}
	await addProductToCart( page );
	await gotoCheckout( page, CHECKOUTS[ checkout ].path, layout );
	await settle( page );

	return page.locator( CHECKOUTS[ checkout ].panel );
};

/**
 * Every card mount in a layout has to occupy space, not just exist.
 * @param {import('@playwright/test').Page} page   - Page under test
 * @param {'single'|'split'}                layout - Card field layout
 */
const expectCardMounted = async ( page, layout ) => {
	const parts =
		layout === 'split' ? [ 'number', 'expiry', 'cvc' ] : [ 'number' ];
	for ( const part of parts ) {
		await expectMounted(
			page.locator( mountSelector( layout, part ) ),
			`${ layout } card ${ part }`,
			// Expiry and CVC share a row, so each gets half the width.
			part === 'number' ? 200 : 100
		);
	}
};

/**
 * Select a non-card method on the Blocks checkout and wait for its component.
 * @param {import('@playwright/test').Page} page      - Page under test
 * @param {string}                          gateway   - Gateway id
 * @param {string}                          container - Selector of its mount
 * @return {import('@playwright/test').Locator} The mount container
 */
const selectMethod = async ( page, gateway, container ) => {
	const radio = page.locator(
		`#radio-control-wc-payment-method-options-${ gateway }`
	);
	await expect( radio, `${ gateway } is offered` ).toBeVisible( {
		timeout: MOUNT_TIMEOUT,
	} );
	await radio.check();

	const mount = page.locator( container );
	await expect(
		mount.locator( 'iframe:visible' ).first(),
		`${ gateway } mounted`
	).toBeVisible( { timeout: MOUNT_TIMEOUT } );
	await settle( page );

	return mount;
};

/**
 * Every store setting a shot depends on, so each group can set exactly what it
 * needs and the file can put it all back once at the end.
 *
 * ⚠️ Explicit, not "restore whatever was there". A restore-to-previous chain
 * leaks: an interrupted run leaves PayPal on, the next run records "on" as the
 * previous value and faithfully restores it, and every card panel from then on
 * carries a PayPal row its baseline never had. The panel's contents are part
 * of what a baseline asserts, so they are set here, not inherited.
 */
const snapshot = () => ( {
	layout: getCardFieldLayout(),
	checkoutPageId: getCheckoutPageId(),
	bizum: getGatewayEnabled( BIZUM_OPTION ),
	paypal: getGatewayEnabled( PAYPAL_OPTION ),
	walletExpress: getExpressSettings( WALLET_OPTION ),
	paypalExpress: getExpressSettings( PAYPAL_OPTION ),
} );

const restore = ( state ) => {
	setCardFieldLayout( state.layout );
	setCheckoutPageId( state.checkoutPageId );
	setGatewayEnabled( BIZUM_OPTION, state.bizum );
	setGatewayEnabled( PAYPAL_OPTION, state.paypal );
	setExpressSettings( WALLET_OPTION, state.walletExpress );
	setExpressSettings( PAYPAL_OPTION, state.paypalExpress );
	mergeSettings( CARD_OPTION, { tokenization: 'no' } );
};

/**
 * The card shots show the panel with the card method and the wallet method
 * only. Every other MONEI method off, and no express row above.
 * @param {'no'|'yes'} tokenization - Whether the save-card checkbox renders
 */
const cardOnlyStore = ( tokenization ) => {
	setGatewayEnabled( BIZUM_OPTION, 'no' );
	setGatewayEnabled( PAYPAL_OPTION, 'no' );
	for ( const option of [ WALLET_OPTION, PAYPAL_OPTION ] ) {
		setExpressSettings( option, {
			express_enabled: 'no',
			express_locations: [],
		} );
	}
	mergeSettings( CARD_OPTION, { tokenization } );
};

/**
 * Every MONEI method on, and express at the top of the checkout.
 */
const everyMethodStore = () => {
	setGatewayEnabled( BIZUM_OPTION, 'yes' );
	setGatewayEnabled( PAYPAL_OPTION, 'yes' );
	for ( const option of [ WALLET_OPTION, PAYPAL_OPTION ] ) {
		setExpressSettings( option, {
			express_enabled: 'yes',
			express_locations: [ 'checkout' ],
		} );
	}
	mergeSettings( CARD_OPTION, { tokenization: 'no' } );
	setCardFieldLayout( 'split' );
};

let before;

test.describe( 'Checkout payment methods, rendering', () => {
	test.beforeAll( () => {
		before = snapshot();
	} );

	test.afterAll( () => {
		if ( before ) {
			restore( before );
		}
	} );

	test.afterEach( () => {
		// Classic shots point WooCommerce at the shortcode page; every test
		// starts from the real one.
		if ( before ) {
			setCheckoutPageId( before.checkoutPageId );
		}
	} );

	test.beforeEach( ( { page } ) => {
		// A collapsed viewport reports 0x0 and every bounding box after it is
		// garbage. Fail here, with a reason, rather than on a mystery box.
		expect( page.viewportSize().width, 'viewport width' ).toBeGreaterThan(
			300
		);
	} );

	for ( const tokenization of [ 'no', 'yes' ] ) {
		const saved =
			tokenization === 'yes' ? 'with save-card' : 'no save-card';

		test.describe( `card, ${ saved }`, () => {
			test.beforeAll( () => {
				cardOnlyStore( tokenization );
			} );

			for ( const checkout of Object.keys( CHECKOUTS ) ) {
				for ( const layout of [ 'single', 'split' ] ) {
					test( `${ checkout } ${ layout }`, async ( { page } ) => {
						setCardFieldLayout( layout );

						// The Blocks checkout offers "save payment information" only
						// to a signed-in customer; classic offers it to a guest too.
						// Sign in for both, so the two shots differ only in what the
						// plugin renders and not in who is looking.
						if ( tokenization === 'yes' ) {
							await loginAsShopper( page );
						}

						const panel = await openPaymentPanel(
							page,
							checkout,
							layout
						);
						await expectCardMounted( page, layout );

						if ( tokenization === 'yes' ) {
							await expect(
								panel.getByText( /save payment information/i ),
								'the save-card checkbox rendered'
							).toBeVisible();
						}

						await expect( panel ).toHaveScreenshot(
							`checkout-card-${ layout }-${ checkout }-${
								tokenization === 'yes' ? 'save-card' : 'guest'
							}.png`
						);
					} );
				}
			}
		} );
	}

	test.describe( 'card, split field states', () => {
		test.beforeAll( () => {
			cardOnlyStore( 'no' );
		} );

		test( 'focused', async ( { page } ) => {
			setCardFieldLayout( 'split' );
			const panel = await openPaymentPanel( page, 'blocks', 'split' );
			await expectCardMounted( page, 'split' );

			await cardInput( page, 'split', 'number' ).click();
			await expect(
				page.locator( mountSelector( 'split', 'number' ) ),
				'the number field reports focus to its mount'
			).toHaveClass( /monei-component--focus/ );

			await expect( panel ).toHaveScreenshot(
				'checkout-card-split-blocks-focused.png'
			);
		} );

		test( 'invalid', async ( { page } ) => {
			setCardFieldLayout( 'split' );
			const panel = await openPaymentPanel( page, 'blocks', 'split' );
			await expectCardMounted( page, 'split' );

			await typeIntoCardInput(
				cardInput( page, 'split', 'number' ),
				LUHN_INVALID
			);
			// Leave the field: monei.js validates on blur, not on every key.
			await cardInput( page, 'split', 'expiry' ).click();
			await expect(
				page.locator( mountSelector( 'split', 'number' ) ),
				'the number field reports the invalid number to its mount'
			).toHaveClass( /monei-component--invalid/ );

			await expect( panel ).toHaveScreenshot(
				'checkout-card-split-blocks-invalid.png'
			);
		} );
	} );

	test.describe( 'other methods, Blocks', () => {
		let bizumOffered = false;
		let paypalOffered = false;

		test.beforeAll( async () => {
			bizumOffered = await isBizumOfferedHere( getAccountId() );
			paypalOffered = await isPayPalOffered(
				( process.env.MONEI_TEST_API_KEY || '' ).trim()
			).catch( () => false );
			everyMethodStore();
		} );

		test( 'bizum', async ( { page } ) => {
			// Bizum is filtered by the caller's IP, not the store's country, so
			// from outside Spain the component correctly declines to mount and
			// there is nothing to photograph. Same gate as blocks-bizum.spec.js.
			test.skip(
				! bizumOffered,
				'MONEI does not offer Bizum from here.'
			);

			await openPaymentPanel( page, 'blocks', 'split' );
			// Bizum offers itself only to a Spanish billing address.
			await fillBlocksBilling( page );

			const mount = await selectMethod(
				page,
				'monei_bizum',
				'#bizum-container'
			);
			await expectMounted( mount, 'bizum' );

			await expect( mount ).toHaveScreenshot(
				'checkout-bizum-blocks.png'
			);
		} );

		test( 'paypal', async ( { page } ) => {
			test.skip(
				! paypalOffered,
				'Needs MONEI_TEST_API_KEY set to an account that offers PayPal.'
			);

			await openPaymentPanel( page, 'blocks', 'split' );
			const mount = await selectMethod(
				page,
				'monei_paypal',
				'#paypal-container'
			);
			await expectMounted( mount, 'paypal' );

			await expect( mount ).toHaveScreenshot(
				'checkout-paypal-blocks.png',
				{
					mask: [
						mount.locator( 'iframe' ),
						page.locator( ZOID_PAYPAL ),
					],
				}
			);
		} );

		test( 'wallet', async ( { page } ) => {
			await openPaymentPanel( page, 'blocks', 'split' );
			const mount = await selectMethod(
				page,
				'monei_apple_google',
				'#payment-request-container'
			);
			await expectMounted( mount, 'wallet' );

			await expect( mount ).toHaveScreenshot(
				'checkout-wallet-blocks.png',
				{
					mask: [ mount.locator( 'iframe' ) ],
				}
			);
		} );

		test( 'express', async ( { page } ) => {
			await openPaymentPanel( page, 'blocks', 'split' );

			// Each express method registers its own block, and WooCommerce lays
			// them out together in its express payment area. That area is what the
			// shopper sees as "express checkout", so that is the surface.
			const express = page.locator(
				'.wc-block-components-express-payment__content'
			);
			await expect( express, 'express area rendered' ).toBeVisible( {
				timeout: MOUNT_TIMEOUT,
			} );
			const buttons = express.locator(
				'.monei-express-checkout__button'
			);
			await expect(
				buttons,
				'wallet and PayPal both offered'
			).toHaveCount( 2, { timeout: MOUNT_TIMEOUT } );
			// The overflow bug was exactly one of these being wider than the block.
			for ( const container of await buttons.all() ) {
				await expectMounted( container, 'express button' );
			}
			await settle( page );

			await expect( express ).toHaveScreenshot(
				'checkout-express-blocks.png',
				{
					mask: [
						express.locator( 'iframe' ),
						page.locator( ZOID_PAYPAL ),
					],
				}
			);
		} );
	} );
} );
