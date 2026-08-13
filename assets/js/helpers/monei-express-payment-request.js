/**
 * Shared construction of the express `PaymentRequest` component.
 *
 * Classic and blocks differ in how they mount the button and what they do with the
 * resulting token, but the wallet contract — amount, shipping callbacks, minor units
 * — is identical, so it lives here once.
 */

import { expressRequest } from './monei-express-api';

/**
 * Shipping callbacks wired to the express endpoints.
 *
 * `onShippingAddressChange` receives a partial address: monei.js emits
 * `{ city, state, zip, country }` and never a street mid-flow. The server maps those
 * names itself, so the object is forwarded untouched.
 * @param {Object}   options                  - Options
 * @param {Function} options.beforeServerCall - Awaited before every call, so the
 *                                            product page can prepare the cart
 * @param {Function} options.onCartChange     - Called with the fresh cart payload
 * @return {Object} `{ onShippingAddressChange, onShippingOptionChange }`
 */
export const createShippingCallbacks = ( {
	beforeServerCall = null,
	onCartChange = null,
} = {} ) => {
	const publish = ( payload ) => {
		if ( onCartChange ) {
			onCartChange( payload );
		}
		return payload;
	};

	return {
		async onShippingAddressChange( address ) {
			if ( beforeServerCall ) {
				await beforeServerCall();
			}

			const payload = await expressRequest( 'get_shipping_options', {
				address,
			} );

			publish( payload );

			// An empty option list is the wallet's own signal for "cannot ship here",
			// so a rejected address needs no special casing beyond returning it.
			return {
				shippingOptions: payload.shippingOptions || [],
				amount: payload.amount,
			};
		},

		async onShippingOptionChange( option ) {
			const payload = await expressRequest( 'update_shipping_method', {
				shipping_method: option.id,
			} );

			publish( payload );

			return { amount: payload.amount };
		},
	};
};

/**
 * Builds an express `PaymentRequest`. Returns null when the SDK is absent.
 * @param {Object} config - Component configuration
 * @return {Object|null} The component instance
 */
export const createExpressPaymentRequest = ( config ) => {
	// eslint-disable-next-line no-undef
	if ( typeof monei === 'undefined' || ! monei.PaymentRequest ) {
		return null;
	}

	const {
		accountId,
		sessionId,
		amount,
		currency,
		language,
		style,
		requestShipping,
		shippingOptions,
		beforeServerCall,
		onCartChange,
		onBeforeOpen,
		onSubmit,
		onError,
		onLoad,
	} = config;

	const callbacks = createShippingCallbacks( {
		beforeServerCall,
		onCartChange,
	} );

	const props = {
		accountId,
		sessionId,
		amount,
		currency,
		language,
		style: style || {},
		requestBilling: true,
		onSubmit,
		onError,
		onLoad,
	};

	if ( onBeforeOpen ) {
		props.onBeforeOpen = onBeforeOpen;
	}

	if ( requestShipping ) {
		props.requestShipping = true;
		props.shippingOptions = shippingOptions || [];
		props.onShippingAddressChange = callbacks.onShippingAddressChange;
		props.onShippingOptionChange = callbacks.onShippingOptionChange;
	}

	// eslint-disable-next-line no-undef
	return monei.PaymentRequest( props );
};
