import { useButtonStateManager } from '../helpers/monei-shared-utils';

/**
 * Create Apple/Google Pay label
 * @param {Object} moneiData - Configuration data
 * @return {*} JSX Element
 */
export const createAppleGoogleLabel = ( moneiData ) => {
	const isApple = window.ApplePaySession?.canMakePayments?.();
	const appleEnabled = moneiData.logo_apple !== false;
	const googleEnabled = moneiData.logo_google !== false;

	let logo = googleEnabled ? moneiData.logo_google : false;
	logo = isApple && appleEnabled ? moneiData.logo_apple : logo;

	const title = moneiData.title || '';
	const shouldShowLogo =
		( isApple && moneiData?.logo_apple ) ||
		( ! isApple && moneiData?.logo_google );

	return (
		<div className="monei-label-container">
			{ title && <span className="monei-text">{ title }</span> }
			{ shouldShowLogo && (
				<div className="monei-logo">
					<img src={ logo } alt="" />
				</div>
			) }
		</div>
	);
};

/**
 * Shared Apple/Google Pay Content Component
 * @param {Object} props - Component props
 * @return {*} JSX Element
 */
export const MoneiAppleGoogleContent = ( props ) => {
	const { useEffect, useRef } = wp.element;
	const { onPaymentSetup } = props.eventRegistration;
	const { activePaymentMethod } = props;
	const moneiData =
		props.moneiData ||
		// eslint-disable-next-line no-undef
		wc.wcSettings.getSetting( 'monei_apple_google_data' );

	const paymentRequestRef = useRef( null );
	const isActive =
		activePaymentMethod ===
		( props.paymentMethodId || 'monei_apple_google' );

	const buttonManager = useButtonStateManager( {
		isActive,
		emitResponse: props.emitResponse,
		tokenFieldName: 'monei_payment_request_token',
		errorMessage: moneiData.tokenErrorString,
	} );

	/**
	 * Initialize MONEI Payment Request
	 */
	const initPaymentRequest = () => {
		// eslint-disable-next-line no-undef
		if ( typeof monei === 'undefined' || ! monei.PaymentRequest ) {
			console.error( 'MONEI SDK is not available' );
			return;
		}

		// Clean up existing instance
		if ( paymentRequestRef.current?.close ) {
			try {
				paymentRequestRef.current.close();
			} catch ( e ) {
				// Silent fail
			}
		}

		// eslint-disable-next-line no-undef
		const paymentRequest = monei.PaymentRequest( {
			accountId: moneiData.accountId,
			sessionId: moneiData.sessionId,
			language: moneiData.language,
			amount: Math.round( moneiData.total * 100 ),
			currency: moneiData.currency,
			style: moneiData.paymentRequestStyle || {},
			onSubmit( result ) {
				if ( result.token ) {
					buttonManager.enableCheckout( result.token );
				}
			},
			onError( error ) {
				console.error( error );
			},
		} );

		const container = document.getElementById(
			'payment-request-container'
		);
		if ( container ) {
			paymentRequest.render( container );
			paymentRequestRef.current = paymentRequest;
		}
	};

	// Initialize on mount
	useEffect( () => {
		initPaymentRequest();

		return () => {
			if ( paymentRequestRef.current?.close ) {
				try {
					paymentRequestRef.current.close();
				} catch ( e ) {
					// Silent cleanup
				}
			}
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Setup payment hook
	useEffect( () => {
		const unsubscribe = onPaymentSetup( () => {
			return buttonManager.getPaymentData();
		} );

		return () => unsubscribe();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ onPaymentSetup ] );

	return (
		<fieldset className="monei-fieldset monei-payment-request-fieldset">
			<div
				id="payment-request-container"
				className="monei-payment-request-container"
			>
				{ /* Payment button will be rendered here */ }
			</div>
			<input
				type="hidden"
				id="monei_payment_token"
				name="monei_payment_token"
				value=""
			/>
			<div id="monei-card-error" className="monei-error" />
		</fieldset>
	);
};
