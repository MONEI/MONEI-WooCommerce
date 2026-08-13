/**
 * Get the display message of a MONEI error.
 *
 * Component events and `submit()` results both report `error` either as an
 * object with a `message` or as a plain string.
 * @param {Object|string} error    - Error reported by a MONEI component
 * @param {string}        fallback - Message to use when the error carries none
 * @return {string}
 */
export const getMoneiErrorMessage = ( error, fallback ) => {
	if ( typeof error === 'string' ) {
		return error;
	}
	return ( error && error.message ) || fallback;
};

/**
 * Resolve the payment amount in minor units.
 *
 * The Store API reports `total_price` already in minor units, while the
 * localized total is a major unit amount. Truncating that product would
 * undercharge by a cent for every total whose float representation falls just
 * below the integer, such as 0.29, 1.13 or 2.05.
 * @param {Object} cartTotals    - Cart totals from the Store API
 * @param {number} fallbackTotal - Localized total in major units
 * @return {number}
 */
export const getAmountInMinorUnits = ( cartTotals, fallbackTotal ) =>
	cartTotals?.total_price
		? parseInt( cartTotals.total_price, 10 )
		: Math.round( fallbackTotal * 100 );

/**
 * How long tokenization waits for an in flight component update.
 *
 * Long enough for a postMessage round trip to a slow iframe, short enough that
 * an update which never settles cannot strand the shopper on a spinner.
 */
export const COMPONENT_UPDATE_DEADLINE_MS = 2000;

/**
 * Forward props to a live MONEI component and keep the update trackable.
 *
 * The returned promise settles once the component confirms the update, and
 * never rejects: a failed update must not wedge the payment.
 * @param {Object|null} instance - Live component, or null before it mounts
 * @param {Object}      props    - Props to forward
 * @return {Promise<void>}
 */
export const updateComponentProps = ( instance, props ) => {
	if ( ! instance || ! instance.updateProps ) {
		return Promise.resolve();
	}

	return Promise.resolve( instance.updateProps( props ) ).then(
		() => undefined,
		( error ) => {
			console.error( 'MONEI: card component update failed', error );
		}
	);
};

/**
 * Wait for an in flight component update before submitting the component.
 *
 * The token request carries the amount the component holds, and `updateProps()`
 * only resolves once the new props reach it. A shopper who pays inside that
 * window would otherwise tokenize against the previous total.
 *
 * The wait is capped. An update that never settles gives up the guarantee
 * rather than the payment, so tokenization continues once the deadline passes.
 * @param {Promise|null} pending    - In flight update, if any
 * @param {number}       deadlineMs - Maximum time to wait
 * @return {Promise<void>}
 */
export const awaitComponentUpdate = (
	pending,
	deadlineMs = COMPONENT_UPDATE_DEADLINE_MS
) => {
	if ( ! pending ) {
		return Promise.resolve();
	}

	let timer;
	const settled = Promise.resolve( pending ).then(
		() => false,
		() => false
	);
	const expired = new Promise( ( resolve ) => {
		timer = setTimeout( () => resolve( true ), deadlineMs );
	} );

	return Promise.race( [ settled, expired ] ).then( ( timedOut ) => {
		clearTimeout( timer );
		if ( timedOut ) {
			console.warn(
				'MONEI: the card component did not confirm the amount update in time, submitting with the amount it holds.'
			);
		}
	} );
};

/**
 * Get WooCommerce place order button
 * @return {HTMLElement|null}
 */
export const getPlaceOrderButton = () => {
	return document.querySelector(
		'.wc-block-components-button.wp-element-button.wc-block-components-checkout-place-order-button'
	);
};

/**
 * Button state manager for React components
 * @param {Object}  props
 * @param {boolean} props.isActive       - Whether this payment method is active
 * @param {Object}  props.emitResponse   - Response types from WooCommerce
 * @param {string}  props.tokenFieldName - Hidden input field name for token
 * @return {Object}
 */
export const useButtonStateManager = ( props ) => {
	const { useEffect, useState, useRef, useCallback, useMemo } = wp.element;
	const [ buttonReady, setButtonReady ] = useState( false );
	const tokenRef = useRef( null );

	useEffect( () => {
		if ( ! props.isActive ) {
			return;
		}

		const button = getPlaceOrderButton();
		if ( ! button ) {
			return;
		}

		const originalDisabled = button.disabled;
		const originalColor = button.style.color;
		const originalBackgroundColor = button.style.backgroundColor;

		if ( ! buttonReady ) {
			button.classList.add( 'monei-disabled' );
		}

		return () => {
			button.classList.remove( 'monei-disabled' );
			button.disabled = originalDisabled;
			button.style.color = originalColor;
			button.style.backgroundColor = originalBackgroundColor;
		};
	}, [ props.isActive, buttonReady ] );

	const enableCheckout = useCallback( ( token ) => {
		tokenRef.current = token;
		setButtonReady( true );

		const button = getPlaceOrderButton();
		if ( button ) {
			button.classList.remove( 'monei-disabled' );
			button.click();
		}
	}, [] );

	const getPaymentData = useCallback( () => {
		if ( ! tokenRef.current ) {
			return {
				type: props.emitResponse.responseTypes.ERROR,
				message: props.errorMessage || 'Payment token required',
			};
		}

		return {
			type: props.emitResponse.responseTypes.SUCCESS,
			meta: {
				paymentMethodData: {
					[ props.tokenFieldName ]: tokenRef.current,
				},
			},
		};
	}, [
		props.emitResponse.responseTypes,
		props.errorMessage,
		props.tokenFieldName,
	] );

	return useMemo(
		() => ( {
			enableCheckout,
			getPaymentData,
			tokenRef,
		} ),
		[ enableCheckout, getPaymentData ]
	);
};
