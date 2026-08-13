const { test, expect } = require( '@playwright/test' );
const {
	CARDS,
	PRODUCT_ID,
	completeThreeDsChallengeIfShown,
	expectOrderReceived,
	fillCard,
} = require( '../utils/checkout' );
const {
	getExpressSettings,
	getOrderStatus,
	setExpressSettings,
} = require( '../utils/wp-cli' );

/**
 * Express checkout takes a real payment, from a product page to a paid order.
 *
 * ⚠️ What this proves: everything the server does with a wallet result. The
 * express endpoints borrow the cart, map the wallet's billing and shipping
 * details onto the customer, recompute the total, verify the amount the client
 * reported, build the order through `WC_Checkout::create_order()`, hand the
 * token to the gateway, and the payment then goes through MONEI for real —
 * including the 3DS redirect — until the order reaches `processing`.
 *
 * ⚠️ What this does NOT prove: the wallet sheet. Apple Pay and Google Pay render
 * their sheet in a cross-origin iframe owned by the browser vendor, no wallet
 * card can be provisioned on a CI machine, and no automation may authorise one.
 * That third-party UI is the one part of express checkout that stays untested.
 *
 * The token is therefore obtained the only way a test can obtain a genuine one:
 * a real `monei.CardInput`, mounted on the store's own page with the store's own
 * `accountId` and the express `sessionId`, filled with a MONEI test card. A
 * wallet `SubmitResult` carries a token of exactly this kind, so everything
 * downstream of the sheet is identical to a real Apple Pay or Google Pay run.
 *
 * ⚠️ Not exercised here: shipping option selection. The fixture store has zero
 * shipping methods, so `WC()->cart->needs_shipping()` is false and express skips
 * the shipping callbacks — which is the same assumption every other spec in this
 * suite makes about the store.
 *
 * This leaves one real paid test-mode order behind per run.
 */

const SETTINGS_OPTION = 'woocommerce_monei_apple_google_settings';
const PRODUCT_PATH =
	process.env.MONEI_E2E_PRODUCT_PATH || '/product/t-shirt-with-logo/';

const BILLING_FIELDS = {
	'billing[name]': 'Ada Lovelace',
	'billing[email]': 'e2e-monei@example.com',
	'billing[address][line1]': 'Calle Mayor 1',
	'billing[address][city]': 'Madrid',
	'billing[address][state]': 'Madrid',
	'billing[address][zip]': '28013',
	'billing[address][country]': 'ES',
};

const SHIPPING_FIELDS = {
	'shipping[name]': 'Ada Lovelace',
	'shipping[address][line1]': 'Calle Mayor 1',
	'shipping[address][city]': 'Madrid',
	'shipping[address][state]': 'Madrid',
	'shipping[address][zip]': '28013',
	'shipping[address][country]': 'ES',
};

/**
 * Calls an express endpoint from inside the page.
 *
 * The request has to come from the browser and not from an API context: the
 * token is bound to the WooCommerce session the express bootstrap opened, and
 * only the page carries that session cookie.
 * @param {import('@playwright/test').Page} page     - Page under test
 * @param {string}                          endpoint - Endpoint name without the prefix
 * @param {Object}                          fields   - Form fields
 * @return {Promise<Object>} `{ status, body }`
 */
const expressPost = ( page, endpoint, fields = {} ) =>
	page.evaluate(
		async ( { name, values } ) => {
			const url = String(
				window.wc_monei_express_params.ajaxUrl
			).replace( '%%endpoint%%', `monei_express_${ name }` );
			const form = new FormData();
			Object.keys( values ).forEach( ( key ) =>
				form.append( key, String( values[ key ] ) )
			);

			const response = await fetch( url, {
				method: 'POST',
				credentials: 'same-origin',
				body: form,
			} );

			return { status: response.status, body: await response.json() };
		},
		{ name: endpoint, values: fields }
	);

/**
 * Mounts a real card component on the page, in the same mount container the
 * checkout uses, so the shared card helpers can drive it.
 * @param {import('@playwright/test').Page} page      - Page under test
 * @param {string}                          sessionId - Express session id
 * @param {number}                          amount    - Cart total in minor units
 */
const mountCardInput = ( page, sessionId, amount ) =>
	page.evaluate(
		( { session, total } ) => {
			const mount = document.createElement( 'div' );
			mount.id = 'monei-card-input';
			document.body.prepend( mount );

			// eslint-disable-next-line no-undef
			const input = monei.CardInput( {
				accountId: window.wc_monei_express_params.accountId,
				sessionId: session,
				amount: total,
				currency: window.wc_monei_express_params.currency,
			} );
			input.render( mount );
			window.__moneiE2eCardInput = input;
		},
		{ session: sessionId, total: amount }
	);

let previousSettings;

test.describe( 'Express checkout payment', () => {
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

	test( 'pays a product page express order with a real token', async ( {
		page,
	} ) => {
		await page.goto( PRODUCT_PATH, { waitUntil: 'domcontentloaded' } );

		// Also the assertion that the express assets reached the product page
		// with a usable account id, which is what the wallet needs to render.
		await expect
			.poll( () =>
				page.evaluate(
					() => window.wc_monei_express_params?.accountId || ''
				)
			)
			.not.toBe( '' );

		const bootstrap = await expressPost( page, 'bootstrap' );
		expect( bootstrap.body.result ).toBe( 'success' );

		const security = bootstrap.body.nonce;
		const sessionId = bootstrap.body.sessionId;

		const cart = await expressPost( page, 'add_to_cart', {
			security,
			product_id: PRODUCT_ID,
			quantity: 1,
		} );
		expect( cart.body.result ).toBe( 'success' );

		const amount = cart.body.amount;
		expect( amount ).toBeGreaterThan( 0 );

		await mountCardInput( page, sessionId, amount );
		await fillCard( page, 'single', CARDS.visaFrictionless );

		const submitted = await page.evaluate( () =>
			window.__moneiE2eCardInput.submit()
		);
		expect(
			submitted.error,
			'the card component returned a token'
		).toBeFalsy();
		expect( submitted.token ).toBeTruthy();

		const created = await expressPost( page, 'create_order', {
			security,
			session_id: sessionId,
			location: 'product',
			payment_method: 'card',
			monei_payment_request_token: submitted.token,
			// The cart carries no shippable line while the store has no shipping
			// method, so the wallet would report no option either.
			shipping_option: '',
			final_amount: String( amount ),
			...BILLING_FIELDS,
			...SHIPPING_FIELDS,
		} );

		expect(
			created.body.data?.code,
			`express order refused: ${ created.body.data?.message || '' }`
		).toBeUndefined();
		expect( created.body.result ).toBe( 'success' );
		expect( created.body.orderId ).toBeGreaterThan( 0 );

		// What the express client does with the response, and the only part of
		// the flow after the sheet that is still a browser journey: the 3DS
		// redirect and the return to the store.
		await page.goto( created.body.redirect );
		await completeThreeDsChallengeIfShown( page );

		const orderId = await expectOrderReceived( page );
		expect( Number( orderId ) ).toBe( created.body.orderId );

		// The assertion that separates a real payment from a created order: only
		// money that actually moved takes an order out of `pending`.
		expect( getOrderStatus( orderId ) ).toBe( 'processing' );
	} );
} );
