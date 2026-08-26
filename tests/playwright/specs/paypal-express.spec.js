/**
 * Express PayPal, paid with a real sandbox account.
 *
 * ⚠️ These take real test-mode payments and drive PayPal's own sandbox, so they
 * are slower and less predictable than the rest of the suite. They skip entirely
 * on an account that does not offer PayPal rather than failing for a reason that
 * has nothing to do with the plugin — see `isPayPalOffered()`.
 */

const { test, expect } = require( '@playwright/test' );
const { expectOrderReceived } = require( '../utils/checkout' );
const { readFixtures } = require( '../utils/fixtures' );
const {
	getOrderStatus,
	setExpressSettings,
	getExpressSettings,
} = require( '../utils/wp-cli' );
const { requireEnv } = require( '../utils/env' );
const {
	isPayPalOffered,
	payWithExpressPayPal,
	storeMessages,
} = require( '../utils/paypal' );

const PAYPAL_OPTION = 'woocommerce_monei_paypal_settings';

const fixtures = readFixtures();

// PayPal's sandbox is the slow part; the store's own work is a fraction of this.
const PAYPAL_TIMEOUT = 240000;

let previousSettings = null;
let paypalOffered = false;

test.describe( 'Express checkout, PayPal', () => {
	test.describe.configure( { mode: 'serial' } );

	test.beforeAll( async () => {
		paypalOffered = await isPayPalOffered(
			requireEnv(
				'MONEI_TEST_API_KEY',
				'It must be the test mode API key of the MONEI account the suite pays with.'
			)
		);

		if ( ! paypalOffered ) {
			return;
		}

		previousSettings = getExpressSettings( PAYPAL_OPTION );
		setExpressSettings( PAYPAL_OPTION, {
			enabled: 'yes',
			express_enabled: 'yes',
			express_locations: [ 'product', 'cart', 'checkout' ],
		} );
	} );

	test.afterAll( () => {
		if ( previousSettings ) {
			setExpressSettings( PAYPAL_OPTION, previousSettings );
		}
	} );

	test.beforeEach( () => {
		test.skip(
			! paypalOffered,
			'The MONEI account behind MONEI_TEST_API_KEY does not offer PayPal. ' +
				'Connect PayPal to it in the MONEI dashboard to run these.'
		);
	} );

	test( 'pays for a product with express PayPal', async ( { page } ) => {
		test.setTimeout( PAYPAL_TIMEOUT );

		// ⚠️ The product page, not the cart, and the difference is not cosmetic.
		// PayPal returns a partial address — name, email and country, no street or
		// city. The classic product flow builds the order itself and accepts that,
		// so it pays. The Cart and Checkout blocks go through the Store API, which
		// rejects a partial shipping address outright. Same wallet, same payload,
		// two outcomes; this test covers the one that can complete.
		await page.goto( fixtures.productPath, {
			waitUntil: 'domcontentloaded',
		} );

		await payWithExpressPayPal( page );

		const orderId = await expectOrderReceived( page );

		// The thank you page only says the browser landed somewhere. The order
		// status is what says money moved.
		expect(
			getOrderStatus( orderId ),
			'the order the wallet paid for reached processing'
		).toBe( 'processing' );
	} );

	test( 'tells the shopper when the order cannot be placed', async ( {
		page,
	} ) => {
		test.setTimeout( PAYPAL_TIMEOUT );

		// 🚨 Regression guard. A rejected order used to leave the Cart block
		// silent: WooCommerce never marks an express method "active" there, so
		// the failure was discarded and the shopper sat on a cart that had
		// already taken their PayPal approval, with nothing on screen. Whatever
		// the outcome, the store must either place the order or say why not.
		await page.goto( `/?add-to-cart=${ fixtures.productId }`, {
			waitUntil: 'domcontentloaded',
		} );
		await page.goto( '/cart/', { waitUntil: 'domcontentloaded' } );

		await payWithExpressPayPal( page );

		const landed = await page
			.waitForURL( /order-received/, { timeout: 90000 } )
			.then( () => true )
			.catch( () => false );

		if ( landed ) {
			// The happy path is covered above; nothing to assert about silence.
			return;
		}

		const messages = await storeMessages( page );

		expect(
			messages.join( ' ' ),
			'the store explained why the express order did not go through'
		).not.toBe( '' );
	} );
} );
