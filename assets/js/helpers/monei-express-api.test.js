/**
 * Internal dependencies
 */
import {
	expressRequest,
	resetExpressCredentials,
	setExpressParams,
} from './monei-express-api';

const GENERIC = 'Something went wrong.';

/**
 * @param {Object} body       - Value `response.json()` resolves with
 * @param {Object} [overrides] - Extra Response fields, e.g. `{ ok: false }`
 * @return {Object} Fetch response double
 */
const jsonResponse = ( body, overrides = {} ) => ( {
	ok: true,
	status: 200,
	json: async () => body,
	...overrides,
} );

/**
 * A server that answered with something that is not JSON — an HTML error page, a
 * WAF block, the output of a PHP fatal.
 * @param {number} status - HTTP status
 * @return {Object} Fetch response double
 */
const htmlResponse = ( status ) => ( {
	ok: status >= 200 && status < 300,
	status,
	json: async () => {
		throw new SyntaxError( 'Unexpected token < in JSON at position 0' );
	},
} );

describe( 'expressRequest', () => {
	beforeEach( () => {
		resetExpressCredentials();
		setExpressParams( {
			ajaxUrl: 'https://example.test/?wc-ajax=%%endpoint%%',
			i18n: { genericError: GENERIC },
		} );

		// Every protected call bootstraps a nonce first.
		global.fetch = jest
			.fn()
			.mockResolvedValueOnce(
				jsonResponse( { nonce: 'abc123', sessionId: 's1' } )
			);
	} );

	afterEach( () => {
		resetExpressCredentials();
		delete global.fetch;
	} );

	it( 'returns the decoded body of a successful call', async () => {
		global.fetch.mockResolvedValueOnce(
			jsonResponse( { result: 'success', amount: 1250 } )
		);

		await expect( expressRequest( 'get_cart_details' ) ).resolves.toEqual( {
			result: 'success',
			amount: 1250,
		} );
	} );

	it( 'raises the message the server sent for wp_send_json_error()', async () => {
		global.fetch.mockResolvedValueOnce(
			jsonResponse( {
				success: false,
				data: { message: 'Cart is empty.', code: 'empty_cart' },
			} )
		);

		await expect( expressRequest( 'add_to_cart' ) ).rejects.toThrow(
			'Cart is empty.'
		);
	} );

	// A raw parser message ("Unexpected token <") is not something a shopper can act
	// on, and it used to be what they saw whenever the server returned an error page.
	it( 'raises the generic error when the body is not JSON', async () => {
		global.fetch.mockResolvedValueOnce( htmlResponse( 500 ) );

		await expect( expressRequest( 'get_cart_details' ) ).rejects.toThrow(
			GENERIC
		);
	} );

	// The dangerous case: a non-2xx JSON body with no `success` key used to be
	// returned as if it had worked, so callers read `undefined` off it and handed
	// that to the wallet.
	it( 'raises rather than returning a non-2xx body that is not an error envelope', async () => {
		global.fetch.mockResolvedValueOnce(
			jsonResponse( { message: 'Forbidden' }, { ok: false, status: 403 } )
		);

		await expect( expressRequest( 'get_cart_details' ) ).rejects.toThrow(
			GENERIC
		);
	} );

	it( 'still prefers the server message when an error envelope arrives non-2xx', async () => {
		global.fetch.mockResolvedValueOnce(
			jsonResponse(
				{ success: false, data: { message: 'Nonce expired.' } },
				{ ok: false, status: 403 }
			)
		);

		await expect( expressRequest( 'get_cart_details' ) ).rejects.toThrow(
			'Nonce expired.'
		);
	} );
} );
