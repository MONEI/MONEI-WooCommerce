/**
 * Shared construction of the express wallet components.
 *
 * Classic and blocks differ in how they mount the button and what they do with the
 * resulting token, but the wallet contract — amount, shipping callbacks, minor units
 * — is identical, so it lives here once.
 *
 * PaymentRequest (Apple Pay / Google Pay) and PayPal take the same shipping callbacks:
 * `onShippingAddressChange` and `onShippingOptionChange` are declared with the same
 * result types in both components, so one set of endpoints serves both.
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
 * Everything both wallets take, in the shape the SDK expects.
 * @param {Object} config - Component configuration
 * @return {Object} Component props
 */
const buildProps = ( config ) => {
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

	// ⚠️ `accountId` and never `paymentId`: monei.js refuses `requestShipping` on a
	// paymentId flow, and express cannot work without collecting a shipping address.
	const props = {
		accountId,
		sessionId,
		amount,
		currency,
		language,
		style: style || {},
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

	return props;
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

	// eslint-disable-next-line no-undef
	return monei.PaymentRequest( {
		...buildProps( config ),
		requestBilling: true,
	} );
};

/**
 * Builds an express PayPal button. Returns null when the SDK is absent.
 *
 * ⚠️ There is no `requestBilling` prop on PayPal — the component always returns the
 * payer's name and email, and passing one is not how you ask for them.
 * @param {Object} config - Component configuration
 * @return {Object|null} The component instance
 */
export const createExpressPayPal = ( config ) => {
	// eslint-disable-next-line no-undef
	if ( typeof monei === 'undefined' || ! monei.PayPal ) {
		return null;
	}

	// eslint-disable-next-line no-undef
	return monei.PayPal( buildProps( config ) );
};

/**
 * Builds the wallet a mount container asks for.
 * @param {string} method - `payment_request` or `paypal`
 * @param {Object} config - Component configuration
 * @return {Object|null} The component instance
 */
export const createExpressComponent = ( method, config ) =>
	'paypal' === method
		? createExpressPayPal( config )
		: createExpressPaymentRequest( config );
