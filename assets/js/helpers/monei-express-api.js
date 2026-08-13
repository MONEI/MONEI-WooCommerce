/**
 * Client for the express checkout `wc-ajax` endpoints.
 *
 * No nonce is ever printed into the page: product and cart pages are the most
 * aggressively cached pages in a store, and a cached nonce fails verification with
 * no useful signal. The first call is always `bootstrap`, which is uncached and
 * issues a nonce bound to the caller's own session.
 */

let credentials = null;
let bootstrapping = null;
let params = null;

/**
 * Points the client at its configuration.
 *
 * Classic gets it from `wp_localize_script`; blocks has no localized global and
 * carries the same payload inside the payment method settings, so it hands it over
 * here. Without this the endpoint URL is empty and every call fetches the current
 * page and fails on parsing HTML as JSON.
 * @param {Object} value - Express script data
 */
export const setExpressParams = ( value ) => {
	params = value || {};
};

const getParams = () => params || window.wc_monei_express_params || {};

/**
 * @param {string} endpoint - Endpoint name without the `monei_express_` prefix
 * @return {string} wc-ajax URL
 */
export const expressAjaxUrl = ( endpoint ) =>
	String( getParams().ajaxUrl || '' ).replace(
		'%%endpoint%%',
		`monei_express_${ endpoint }`
	);

/**
 * Flattens a value into `FormData`, so nested objects arrive in PHP as arrays.
 * @param {FormData}                              form  - Target form data
 * @param {string}                                key   - Field name
 * @param {Object|Array|string|number|boolean} value - Field value
 */
const appendField = ( form, key, value ) => {
	if ( value === null || value === undefined ) {
		return;
	}

	if ( Array.isArray( value ) ) {
		value.forEach( ( item, index ) =>
			appendField( form, `${ key }[${ index }]`, item )
		);
		return;
	}

	if ( typeof value === 'object' ) {
		Object.keys( value ).forEach( ( name ) =>
			appendField( form, `${ key }[${ name }]`, value[ name ] )
		);
		return;
	}

	form.append( key, typeof value === 'boolean' ? String( value ) : value );
};

const request = async ( endpoint, data ) => {
	const form = new FormData();
	Object.keys( data ).forEach( ( key ) =>
		appendField( form, key, data[ key ] )
	);

	const response = await fetch( expressAjaxUrl( endpoint ), {
		method: 'POST',
		credentials: 'same-origin',
		body: form,
	} );

	const payload = await response.json();

	// wp_send_json_error() wraps its payload; wp_send_json() does not.
	if ( payload && payload.success === false ) {
		const error = new Error(
			payload.data?.message || getParams().i18n?.genericError || 'Error'
		);
		error.code = payload.data?.code;
		throw error;
	}

	return payload;
};

/**
 * Fetches the nonce and WooCommerce session id, once per page.
 * @return {Promise<Object>} `{ nonce, sessionId }`
 */
export const expressBootstrap = () => {
	if ( credentials ) {
		return Promise.resolve( credentials );
	}

	if ( ! bootstrapping ) {
		bootstrapping = request( 'bootstrap', {} )
			.then( ( result ) => {
				credentials = result;
				bootstrapping = null;
				return result;
			} )
			.catch( ( error ) => {
				bootstrapping = null;
				throw error;
			} );
	}

	return bootstrapping;
};

/**
 * Calls a protected express endpoint, bootstrapping the nonce first.
 * @param {string} endpoint - Endpoint name without the `monei_express_` prefix
 * @param {Object} data     - Request fields
 * @return {Promise<Object>} Decoded response
 */
export const expressRequest = async ( endpoint, data = {} ) => {
	const { nonce } = await expressBootstrap();

	return request( endpoint, { ...data, security: nonce } );
};

/**
 * Fire-and-forget call that survives the page being torn down.
 *
 * A shopper who navigates away mid-flow leaves a borrowed cart behind, and a normal
 * `fetch` is cancelled with the document. Only works once `expressBootstrap()` has
 * resolved, which it always has by the time a flow is in progress.
 * @param {string} endpoint - Endpoint name without the `monei_express_` prefix
 * @param {Object} data     - Request fields
 * @return {boolean} Whether the beacon was queued
 */
export const expressBeacon = ( endpoint, data = {} ) => {
	if ( ! credentials || ! navigator.sendBeacon ) {
		return false;
	}

	const form = new FormData();

	Object.keys( { ...data, security: credentials.nonce } ).forEach( ( key ) =>
		appendField(
			form,
			key,
			key === 'security' ? credentials.nonce : data[ key ]
		)
	);

	return navigator.sendBeacon( expressAjaxUrl( endpoint ), form );
};

/**
 * Test seam: drops the cached nonce so the next call bootstraps again.
 */
export const resetExpressCredentials = () => {
	credentials = null;
	bootstrapping = null;
	params = null;
};
