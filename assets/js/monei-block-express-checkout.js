/**
 * Express checkout wallet button for the Cart and Checkout blocks.
 *
 * ⚠️ This is `registerExpressPaymentMethod`, a registry entry of its own — not a
 * variation of the `registerPaymentMethod` entry the regular Apple/Google Pay method
 * already occupies. The two coexist because they register under different names; the
 * express entry points `paymentMethodId` back at the real gateway so the Store API
 * still processes the order through `monei_apple_google`.
 *
 * A single registration serves both blocks: WooCommerce adds every active payment
 * method's script to `wc-cart-block-frontend` and `wc-checkout-block-frontend` alike.
 */

import {
	expressBootstrap,
	expressRequest,
	setExpressParams,
} from './helpers/monei-express-api';
import { createExpressPaymentRequest } from './helpers/monei-express-payment-request';

const { __ } = wp.i18n;

const NAME = 'monei_apple_google_express';
const GATEWAY_ID = 'monei_apple_google';

let cachedData = null;

/**
 * Payment method settings, resolved once.
 *
 * `getSetting` builds a fresh object on every call, and this data reaches hook
 * dependency lists. Caching it keeps those identities stable across renders.
 * @return {Object} Settings for this payment method
 */
const getData = () => {
	if ( ! cachedData ) {
		cachedData = wc.wcSettings.getSetting( 'monei_apple_google_data', {} );
		setExpressParams( cachedData.express );
	}

	return cachedData;
};

/**
 * Express surface of the page the blocks are rendered on.
 *
 * The registry has no notion of which block asked, and the same registration serves
 * both, so the surface is read off the DOM.
 * @return {string|null} `cart`, `checkout` or null
 */
const getLocation = () => {
	if ( document.querySelector( '.wp-block-woocommerce-checkout' ) ) {
		return 'checkout';
	}

	if ( document.querySelector( '.wp-block-woocommerce-cart' ) ) {
		return 'cart';
	}

	return null;
};

/**
 * Express content component.
 *
 * WooCommerce injects `onClick`, `onClose`, `onError` and `setExpressPaymentError`
 * on top of the usual payment method props.
 * @param {Object} props - Injected props
 * @return {*} JSX element
 */
const MoneiExpressContent = ( props ) => {
	const { useEffect, useRef, useState, useCallback } = wp.element;
	const { useDispatch } = wp.data;

	const {
		onClick,
		onClose,
		onError,
		onSubmit,
		billing,
		shippingData,
		activePaymentMethod,
	} = props;

	// ⚠️ Destructured, never used as whole objects. WooCommerce rebuilds
	// `eventRegistration` and `emitResponse` as fresh literals on every parent render,
	// so an effect that depends on either re-subscribes every render. Re-subscribing to
	// `onPaymentSetup` updates state in the checkout events provider, which renders the
	// parent again — an endless loop that pins a CPU core. The individual callbacks
	// underneath are stable.
	const { onPaymentSetup } = props.eventRegistration;
	const { responseTypes } = props.emitResponse;

	const moneiData = getData();
	const express = moneiData.express || {};

	const containerRef = useRef( null );
	const instanceRef = useRef( null );
	const tokenRef = useRef( null );
	const startedRef = useRef( false );
	const amountRef = useRef( null );
	const activeRef = useRef( activePaymentMethod );
	const { setBillingAddress } = useDispatch( 'wc/store/cart' );
	const [ isSupported, setIsSupported ] = useState( true );

	useEffect( () => {
		activeRef.current = activePaymentMethod;
	}, [ activePaymentMethod ] );

	/**
	 * Tells WooCommerce an express flow owns the checkout. Safe to call more than
	 * once, which matters because the wallet button lives in a cross-origin iframe and
	 * the click itself is not observable from here.
	 */
	const markStarted = useCallback( () => {
		if ( startedRef.current ) {
			return;
		}

		startedRef.current = true;
		onClick();
	}, [ onClick ] );

	const fail = useCallback(
		( message ) => {
			startedRef.current = false;
			onError( message || express.i18n?.genericError || '' );
		},
		[ onError, express.i18n ]
	);

	/**
	 * Pushes the wallet address into the cart store, so the order WooCommerce creates
	 * carries the address the shopper approved rather than the one already on file.
	 * @param {Object} result - SubmitResult from monei.js
	 */
	const applyAddresses = useCallback(
		async ( result ) => {
			const normalized = await expressRequest( 'normalize_address', {
				billing: result.billingDetails || {},
				shipping: result.shippingDetails || result.billingDetails || {},
			} );

			const toStoreAddress = ( address ) => ( {
				first_name: address.first_name,
				last_name: address.last_name,
				company: address.company,
				address_1: address.address_1,
				address_2: address.address_2,
				city: address.city,
				state: address.state,
				postcode: address.postcode,
				country: address.country,
			} );

			setBillingAddress( {
				...toStoreAddress( normalized.billing ),
				email: normalized.billing.email,
				phone: normalized.billing.phone,
			} );

			if ( shippingData.needsShipping ) {
				shippingData.setShippingAddress(
					toStoreAddress( normalized.shipping )
				);
			}
		},
		[ setBillingAddress, shippingData ]
	);

	/**
	 * Waits until WooCommerce reports this method active, so `onSubmit()` does not
	 * run the checkout under whichever method was selected before.
	 */
	const waitUntilActive = useCallback( async () => {
		for ( let attempt = 0; attempt < 40; attempt++ ) {
			if ( activeRef.current === NAME ) {
				return true;
			}

			await new Promise( ( resolve ) => setTimeout( resolve, 50 ) );
		}

		return false;
	}, [] );

	const handleSubmit = useCallback(
		async ( result ) => {
			if ( ! result || ! result.token ) {
				fail( result?.error );
				return;
			}

			markStarted();
			tokenRef.current = result.token;

			try {
				await applyAddresses( result );

				// Submitting while WooCommerce still reports another method active
				// would place the order under that method, with no wallet token
				// attached — the observer below hands nothing over in that state.
				if ( ! ( await waitUntilActive() ) ) {
					tokenRef.current = null;
					fail();
					return;
				}

				onSubmit();
			} catch ( error ) {
				fail( error.message );
			}
		},
		[ applyAddresses, fail, markStarted, onSubmit, waitUntilActive ]
	);

	// Hand the token to the checkout as this gateway's payment data.
	useEffect( () => {
		const unsubscribe = onPaymentSetup( () => {
			// ⚠️ Unlike a regular payment method, whose content is only mounted while
			// it is selected, an express method is mounted for the whole life of the
			// page — so this observer runs on *every* checkout, including one paid by
			// card. Returning anything but undefined here would abort those. Verified
			// the hard way: it failed all three card payment E2E tests.
			if ( activeRef.current !== NAME ) {
				return undefined;
			}

			if ( ! tokenRef.current ) {
				return {
					type: responseTypes.ERROR,
					message: express.i18n?.genericError || '',
				};
			}

			return {
				type: responseTypes.SUCCESS,
				meta: {
					paymentMethodData: {
						monei_payment_request_token: tokenRef.current,
						monei_is_block_checkout: 'yes',
					},
				},
			};
		} );

		return () => unsubscribe();
	}, [ onPaymentSetup, responseTypes, express.i18n ] );

	// Mount once. The amount is kept live through updateProps, never by rebuilding —
	// a rebuild would drop the wallet sheet the shopper is looking at.
	useEffect( () => {
		let cancelled = false;

		const mount = async () => {
			const { sessionId } = await expressBootstrap();
			const cart = await expressRequest( 'get_cart_details' );

			if ( cancelled || ! containerRef.current ) {
				return;
			}

			amountRef.current = cart.amount;

			const instance = createExpressPaymentRequest( {
				accountId: moneiData.accountId,
				sessionId,
				amount: cart.amount,
				currency: cart.currency,
				language: moneiData.language,
				style: express.buttonStyle,
				requestShipping: cart.shippingRequired,
				onBeforeOpen: () => {
					markStarted();
					return true;
				},
				onSubmit: handleSubmit,
				onError: ( error ) => {
					fail( error?.message );
					onClose();
				},
				onLoad: ( supported ) => {
					setIsSupported( !! supported );
				},
			} );

			if ( ! instance ) {
				setIsSupported( false );
				return;
			}

			instanceRef.current = instance;
			await instance.render( containerRef.current );
		};

		mount().catch( () => setIsSupported( false ) );

		return () => {
			cancelled = true;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Keep the wallet total in step with the cart.
	useEffect( () => {
		const total = parseInt( billing?.cartTotal?.value, 10 );

		if (
			! instanceRef.current ||
			Number.isNaN( total ) ||
			total === amountRef.current
		) {
			return;
		}

		amountRef.current = total;
		instanceRef.current.updateProps( { amount: total } ).catch( () => {} );
	}, [ billing?.cartTotal?.value ] );

	if ( ! isSupported ) {
		return null;
	}

	return (
		<div className="monei-express-checkout monei-express-checkout--blocks">
			<div
				className="monei-express-checkout__button"
				ref={ containerRef }
			/>
		</div>
	);
};

const MoneiExpressPaymentMethod = {
	name: NAME,
	paymentMethodId: GATEWAY_ID,
	gatewayId: GATEWAY_ID,
	title: __( 'MONEI Express Checkout', 'monei' ),
	content: <MoneiExpressContent />,
	edit: <div className="monei-express-checkout__button" />,
	canMakePayment: () => {
		const data = getData();
		const express = data.express || {};
		const location = getLocation();

		if ( ! location || ! express.locations?.[ location ] ) {
			return false;
		}

		if ( ! data.accountId ) {
			return false;
		}

		// A cheap platform check only. A definitive answer needs the wallet component
		// itself, which reports through onLoad — the content component removes itself
		// when that comes back unsupported. Probing here would mean mounting a second
		// PaymentRequest just to throw it away.
		return (
			!! window.PaymentRequest ||
			!! window.ApplePaySession?.canMakePayments?.()
		);
	},
	supports: {
		features: [ 'products' ],
	},
};

wc.wcBlocksRegistry.registerExpressPaymentMethod( MoneiExpressPaymentMethod );
