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
