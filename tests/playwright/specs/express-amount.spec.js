const { test, expect } = require( '@playwright/test' );
const { getExpressSettings, setExpressSettings } = require( '../utils/wp-cli' );
const { PRODUCT_ID } = require( '../utils/checkout' );

/**
 * Express checkout refuses an order whose amount does not match the cart.
 *
 * ⚠️ Why this is an E2E test and not only a unit test: the unit tests cover the
 * comparison, but the invariant is that the *running server* refuses a tampered
 * amount. That needs the real endpoint, a real session, a real nonce and a real
 * cart, which is exactly what an attacker has.
 *
 * ⚠️ What this cannot cover: a completed wallet payment. Google Pay renders in
 * Chromium but there is no provisioned wallet card here, and the sheet is a
 * cross-origin iframe, so no automation can authorise one. The happy path is
 * therefore driven to the point where the amount check passes and the payment is
 * attempted with a token MONEI rejects — which is the furthest an automated run
 * can honestly go, and it is what stops this suite passing on a server that
 * refuses everything. It leaves one failed order behind per run.
 */

const SETTINGS_OPTION = 'woocommerce_monei_apple_google_settings';
// Deliberately not a credential: MONEI must refuse it. Kept low-entropy and
// self-describing so secret scanners do not flag it as a leaked token.
const EXPRESS_REJECTED_TOKEN = [ 'not', 'a', 'real', 'token' ].join( '-' );

const endpoint = ( name ) => `/?wc-ajax=monei_express_${ name }`;

/**
 * Posts to an express endpoint, in the form the browser client uses.
 * @param {import('@playwright/test').APIRequestContext} request - Request context
 * @param {string}                                       name    - Endpoint name
 * @param {Object}                                       fields  - Form fields
 * @return {Promise<Object>} `{ status, body }`
 */
const post = async ( request, name, fields = {} ) => {
	const response = await request.post( endpoint( name ), {
		multipart: Object.fromEntries(
			Object.entries( fields ).map( ( [ key, value ] ) => [
				key,
				String( value ),
			] )
		),
	} );

	return { status: response.status(), body: await response.json() };
};

let previousSettings;

test.describe( 'Express checkout amount verification', () => {
	test.beforeAll( () => {
		previousSettings = getExpressSettings( SETTINGS_OPTION );
		setExpressSettings( SETTINGS_OPTION, {
			express_enabled: 'yes',
			express_locations: [ 'product', 'cart', 'checkout' ],
		} );
	} );

	test.afterAll( () => {
		if ( previousSettings ) {
			setExpressSettings( SETTINGS_OPTION, previousSettings );
		}
	} );

	test( 'refuses a tampered amount and accepts the real one', async ( {
		request,
	} ) => {
		const bootstrap = await post( request, 'bootstrap' );
		expect( bootstrap.body.result ).toBe( 'success' );

		const security = bootstrap.body.nonce;
		const sessionId = bootstrap.body.sessionId;

		const borrowCart = () =>
			post( request, 'add_to_cart', {
				security,
				product_id: PRODUCT_ID,
				quantity: 1,
			} );

		const cart = await borrowCart();
		expect( cart.body.result ).toBe( 'success' );

		const amount = cart.body.amount;
		expect( amount ).toBeGreaterThan( 0 );

		const createOrder = async ( fields ) => {
			// Every refusal restores the cart the express flow borrowed, so each
			// attempt starts from the same cart the one before it did.
			await borrowCart();

			return post( request, 'create_order', {
				security,
				session_id: sessionId,
				location: 'product',
				payment_method: 'card',
				monei_payment_request_token: EXPRESS_REJECTED_TOKEN,
				'billing[name]': 'Ada Lovelace',
				'billing[email]': 'e2e-monei@example.com',
				'billing[address][line1]': 'Calle Mayor 1',
				'billing[address][city]': 'Madrid',
				'billing[address][zip]': '28013',
				'billing[address][country]': 'ES',
				...fields,
			} );
		};

		// A free order is the whole point of tampering.
		const free = await createOrder( { final_amount: '0' } );
		expect( free.status ).toBe( 400 );
		expect( free.body.data.code ).toBe( 'amount_mismatch' );

		// One cent under. A tolerance here would be a licence to underpay every
		// order in the store.
		const underpaid = await createOrder( {
			final_amount: String( amount - 1 ),
		} );
		expect( underpaid.status ).toBe( 400 );
		expect( underpaid.body.data.code ).toBe( 'amount_mismatch' );

		// A hundred times the real total: what a zero-decimal currency would be
		// charged if the check went through monei_price_format().
		const overpaid = await createOrder( {
			final_amount: String( amount * 100 ),
		} );
		expect( overpaid.status ).toBe( 400 );
		expect( overpaid.body.data.code ).toBe( 'amount_mismatch' );

		// No amount at all is not an amount that matches.
		const missing = await createOrder( { final_amount: '' } );
		expect( missing.status ).toBe( 400 );
		expect( missing.body.data.code ).toBe( 'amount_mismatch' );

		// The real total gets past the check and on to the payment, which fails
		// only because the token is not a real wallet token. Without this the
		// suite would pass against a server that refused every amount.
		const accepted = await createOrder( {
			final_amount: String( amount ),
		} );
		expect( accepted.body.data?.code ).not.toBe( 'amount_mismatch' );
		expect( accepted.body.data?.code ).toBe( 'payment_failed' );
	} );

	test( 'puts the shopper cart back after a refusal', async ( {
		request,
	} ) => {
		const bootstrap = await post( request, 'bootstrap' );
		const security = bootstrap.body.nonce;
		const sessionId = bootstrap.body.sessionId;

		// A shopper's own cart, collected before express ever ran.
		await request.get( `/?add-to-cart=${ PRODUCT_ID }&quantity=2` );

		const before = await post( request, 'get_cart_details', { security } );
		expect( before.body.amount ).toBeGreaterThan( 0 );

		await post( request, 'add_to_cart', {
			security,
			product_id: PRODUCT_ID,
			quantity: 1,
		} );

		const refused = await post( request, 'create_order', {
			security,
			session_id: sessionId,
			location: 'product',
			payment_method: 'card',
			monei_payment_request_token: EXPRESS_REJECTED_TOKEN,
			final_amount: '1',
		} );
		expect( refused.body.data.code ).toBe( 'amount_mismatch' );

		const after = await post( request, 'get_cart_details', { security } );
		expect( after.body.amount ).toBe( before.body.amount );
	} );

	test( 'refuses an order the wallet gave no email for', async ( {
		request,
	} ) => {
		// 🚨 Regression guard. Express has no form for a guest to type an email into,
		// so the wallet is the only source of one — and monei.js did not request the
		// Google Pay email at all, which made every express payment fail at the MONEI
		// API as `Invalid email address at "body.customer.email"`. The suite missed it
		// because it posts `billing[email]` itself, supplying the exact field
		// production lacked. This asserts the boundary that stub replaces: the server
		// must refuse the payload a wallet without an email produces.
		const bootstrap = await post( request, 'bootstrap' );
		const security = bootstrap.body.nonce;
		const sessionId = bootstrap.body.sessionId;

		const cart = await post( request, 'add_to_cart', {
			security,
			product_id: PRODUCT_ID,
			quantity: 1,
		} );
		expect( cart.body.result ).toBe( 'success' );

		const refused = await post( request, 'create_order', {
			security,
			session_id: sessionId,
			location: 'product',
			payment_method: 'card',
			monei_payment_request_token: EXPRESS_REJECTED_TOKEN,
			final_amount: String( cart.body.amount ),
			// Everything a wallet returns except the email.
			'billing[name]': 'Ada Lovelace',
			'billing[address][line1]': 'Calle Mayor 1',
			'billing[address][city]': 'Madrid',
			'billing[address][zip]': '28013',
			'billing[address][country]': 'ES',
		} );

		// Refused here, by name — not deep in an API error the shopper cannot act on.
		expect( refused.body.data?.code ).toBe( 'missing_billing_email' );
	} );
} );
