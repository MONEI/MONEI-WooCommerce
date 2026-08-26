/**
 * Express checkout wallet buttons for the Cart and Checkout blocks.
 *
 * ⚠️ These are `registerExpressPaymentMethod` entries of their own — not variations of
 * the `registerPaymentMethod` entries the regular Apple/Google Pay and PayPal methods
 * already occupy. They coexist because they register under different names; each
 * express entry points `paymentMethodId` back at its real gateway so the Store API
 * still processes the order through it.
 *
 * A single script serves both blocks and both wallets: WooCommerce adds every active
 * payment method's script to `wc-cart-block-frontend` and `wc-checkout-block-frontend`
 * alike.
 */

import {
	expressBootstrap,
	expressRequest,
	setExpressParams,
} from './helpers/monei-express-api';
import { createExpressComponent } from './helpers/monei-express-payment-request';
import { isWalletDismissal } from './helpers/monei-shared-utils';

const { __ } = wp.i18n;

const PAYMENT_REQUEST = 'payment_request';
const PAYPAL = 'paypal';

let cachedExpress = null;

/**
 * Express settings, resolved once.
 *
 * Both MONEI blocks payment methods carry the same express payload, so whichever of
 * them is active supplies it. `getSetting` builds a fresh object on every call and this
 * data reaches hook dependency lists, so caching keeps those identities stable.
 * @return {Object} Express settings
 */
const getExpress = () => {
	if ( ! cachedExpress ) {
		const candidates = [ 'monei_apple_google_data', 'monei_paypal_data' ];

		for ( const key of candidates ) {
			const data = wc.wcSettings.getSetting( key, {} );

			if ( data.express ) {
				cachedExpress = data.express;
				break;
			}
		}

		cachedExpress = cachedExpress || {};
		setExpressParams( cachedExpress );
	}

	return cachedExpress;
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
 * @param {Object} props        - Injected props
 * @param {string} props.method - Wallet this instance renders
 * @param {string} props.name   - Registry name of this express method
 * @return {*} JSX element
 */
const MoneiExpressContent = ( props ) => {
	const { useEffect, useRef, useState, useCallback } = wp.element;
	const { useDispatch } = wp.data;

	const {
		method,
		name,
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
	const { onPaymentSetup, onCheckoutFail } = props.eventRegistration;
	const { responseTypes } = props.emitResponse;

	const express = getExpress();

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
	 *
	 * PayPal has no `requestBilling` prop and returns the payer's name and email
	 * regardless, which land in the same `billingDetails` object.
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
				// ⚠️ Fall back on an unusable address, not just a missing one. The
				// `||` above only catches a null `shippingDetails`, and PayPal
				// returns an object that exists while carrying no address at all —
				// so the Store API received `country: ""` and refused the order with
				// `woocommerce_rest_invalid_address_country`, quoting an empty
				// country. The server applies exactly this rule for the classic
				// flow; the Store API path has to apply it itself.
				const shipping = normalized.shipping?.country
					? normalized.shipping
					: normalized.billing;

				shippingData.setShippingAddress( toStoreAddress( shipping ) );
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
			if ( activeRef.current === name ) {
				return true;
			}

			await new Promise( ( resolve ) => setTimeout( resolve, 50 ) );
		}

		return false;
	}, [ name ] );

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
				// The method stays mounted for the page's life, so a token left
				// here would be handed over as valid data on the next attempt.
				tokenRef.current = null;
				fail( error.message );
			}
		},
		[ applyAddresses, fail, markStarted, onSubmit, waitUntilActive ]
	);

	/**
	 * Lets go of the checkout when WooCommerce could not place the order.
	 *
	 * `onSubmit()` hands the checkout over and never reports back, so without this
	 * a rejected order left the express flow owning the page: WooCommerce keeps the
	 * button in its processing state until `onClose()`, so its own error notice sat
	 * behind a spinner that never stopped — the shopper saw a wallet that had taken
	 * their approval and then simply hung.
	 *
	 * Returns undefined so the failure keeps whatever message WooCommerce produced.
	 */
	useEffect( () => {
		const unsubscribe = onCheckoutFail( () => {
			if ( activeRef.current !== name ) {
				return undefined;
			}

			// The method stays mounted for the page's life, so a token left here
			// would be handed over as valid data on the next attempt.
			tokenRef.current = null;
			startedRef.current = false;
			onClose();

			return undefined;
		} );

		return () => unsubscribe();
	}, [ onCheckoutFail, name, onClose ] );

	// Hand the token to the checkout as this gateway's payment data.
	useEffect( () => {
		const unsubscribe = onPaymentSetup( () => {
			// ⚠️ Unlike a regular payment method, whose content is only mounted while
			// it is selected, an express method is mounted for the whole life of the
			// page — so this observer runs on *every* checkout, including one paid by
			// card. Returning anything but undefined here would abort those. Verified
			// the hard way: it failed all three card payment E2E tests.
			if ( activeRef.current !== name ) {
				return undefined;
			}

			if ( ! tokenRef.current ) {
				return {
					type: responseTypes.ERROR,
					message: express.i18n?.genericError || '',
				};
			}

			const paymentMethodData = {
				monei_payment_request_token: tokenRef.current,
			};

			// ⚠️ Apple/Google Pay only. The flag makes the gateway hand the payment
			// back for client-side confirmation, which is what the card component
			// does; PayPal's gateway answers it with a paymentId the express flow has
			// nothing to confirm with, so PayPal takes the ordinary redirect path.
			if ( PAYMENT_REQUEST === method ) {
				paymentMethodData.monei_is_block_checkout = 'yes';
			}

			return {
				type: responseTypes.SUCCESS,
				meta: { paymentMethodData },
			};
		} );

		return () => unsubscribe();
	}, [ onPaymentSetup, responseTypes, express.i18n, method, name ] );

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

			const instance = createExpressComponent( method, {
				accountId: express.accountId,
				sessionId,
				amount: cart.amount,
				currency: cart.currency,
				language: express.language,
				style: express.methods?.[ method ]?.style,
				requestShipping: cart.shippingRequired,
				onBeforeOpen: () => {
					markStarted();
					return true;
				},
				onSubmit: handleSubmit,
				onError: ( error ) => {
					// A dismissed sheet still has to close and reset the button,
					// but it is not something to report: the shopper chose it.
					if ( isWalletDismissal( error ) ) {
						startedRef.current = false;
						onClose();
						return;
					}

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

/**
 * Builds the registry entry for one wallet.
 * @param {Object}   options              - Options
 * @param {string}   options.method       - `payment_request` or `paypal`
 * @param {string}   options.name         - Registry name, distinct from the gateway's own
 * @param {string}   options.gatewayId    - Gateway that processes the order
 * @param {string}   options.title        - Admin-facing title
 * @param {Function} options.isAvailable  - Extra platform check
 * @return {Object} Express payment method definition
 */
const buildExpressMethod = ( {
	method,
	name,
	gatewayId,
	title,
	isAvailable,
} ) => ( {
	name,
	paymentMethodId: gatewayId,
	gatewayId,
	title,
	content: <MoneiExpressContent method={ method } name={ name } />,
	edit: <div className="monei-express-checkout__button" />,
	canMakePayment: () => {
		const express = getExpress();
		const location = getLocation();

		if (
			! location ||
			! express.methods?.[ method ]?.locations?.[ location ]
		) {
			return false;
		}

		if ( ! express.accountId ) {
			return false;
		}

		// ⚠️ Registering reserves a grid column whether or not the component ever
		// mounts, so a wallet the account cannot serve has to be refused here — by
		// the time `onLoad` reports it, the empty column is already laid out and
		// halves the width of the button beside it. Compared against `false` rather
		// than truthiness so an absent flag still registers: hiding a wallet the
		// merchant enabled is worse than an empty column.
		if ( false === express.methods?.[ method ]?.available ) {
			return false;
		}

		return isAvailable();
	},
	supports: {
		features: [ 'products' ],
	},
} );

wc.wcBlocksRegistry.registerExpressPaymentMethod(
	buildExpressMethod( {
		method: PAYMENT_REQUEST,
		name: 'monei_apple_google_express',
		gatewayId: 'monei_apple_google',
		title: __( 'MONEI Express Checkout', 'monei' ),
		// A cheap platform check only. A definitive answer needs the wallet component
		// itself, which reports through onLoad — the content component removes itself
		// when that comes back unsupported. Probing here would mean mounting a second
		// PaymentRequest just to throw it away.
		isAvailable: () =>
			!! window.PaymentRequest ||
			!! window.ApplePaySession?.canMakePayments?.(),
	} )
);

wc.wcBlocksRegistry.registerExpressPaymentMethod(
	buildExpressMethod( {
		method: PAYPAL,
		name: 'monei_paypal_express',
		gatewayId: 'monei_paypal',
		title: __( 'MONEI PayPal Express Checkout', 'monei' ),
		// PayPal needs no wallet on the device, so there is nothing to probe. The
		// component still reports through onLoad if the account cannot serve it.
		isAvailable: () => true,
	} )
);
