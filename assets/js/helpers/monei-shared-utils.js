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

		if ( ! buttonReady ) {
			button.style.color = 'black';
			button.style.backgroundColor = '#ccc';
			button.disabled = true;
		}

		return () => {
			button.style.color = '';
			button.style.backgroundColor = '';
			button.disabled = false;
		};
	}, [ props.isActive, buttonReady ] );

	const enableCheckout = useCallback( ( token ) => {
		tokenRef.current = token;
		setButtonReady( true );

		const button = getPlaceOrderButton();
		if ( button ) {
			button.style.color = '';
			button.style.backgroundColor = '';
			button.disabled = false;
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
