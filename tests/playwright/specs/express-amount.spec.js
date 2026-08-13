const { test, expect } = require( '@playwright/test' );
const { getExpressSettings, setExpressSettings } = require( '../utils/wp-cli' );

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

const PRODUCT_ID = process.env.MONEI_E2E_PRODUCT_ID || '24';
const SETTINGS_OPTION = 'woocommerce_monei_apple_google_settings';

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
				monei_payment_request_token: 'tok_e2e_not_a_real_token',
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
			monei_payment_request_token: 'tok_e2e_not_a_real_token',
			final_amount: '1',
		} );
		expect( refused.body.data.code ).toBe( 'amount_mismatch' );

		const after = await post( request, 'get_cart_details', { security } );
		expect( after.body.amount ).toBe( before.body.amount );
	} );
} );
