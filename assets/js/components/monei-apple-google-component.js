import { useButtonStateManager } from '../helpers/monei-shared-utils';

/**
 * Create Apple/Google Pay label
 * @param {Object} moneiData - Configuration data
 * @return {*} JSX Element
 */
export const createAppleGoogleLabel = ( moneiData ) => {
	const isApple = window.ApplePaySession?.canMakePayments?.();
	const appleEnabled = moneiData.logoApple !== false;
	const googleEnabled = moneiData.logoGoogle !== false;

	let logo = googleEnabled ? moneiData.logoGoogle : false;
	logo = isApple && appleEnabled ? moneiData.logoApple : logo;

	// Use specific title based on device
	const title = isApple
		? moneiData.applePayTitle || ''
		: moneiData.googlePayTitle || '';

	const shouldShowLogo =
		( isApple && moneiData?.logoApple ) ||
		( ! isApple && moneiData?.logoGoogle );

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
	const { useEffect, useRef, useState, createPortal } = wp.element;
	const { onPaymentSetup, onCheckoutSuccess } = props.eventRegistration;
	const { activePaymentMethod } = props;
	const moneiData =
		props.moneiData ||
		// eslint-disable-next-line no-undef
		wc.wcSettings.getSetting( 'monei_apple_google_data' );

	const paymentRequestRef = useRef( null );
	const [ isConfirming, setIsConfirming ] = useState( false );
	const [ error, setError ] = useState( '' );
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
					setError( '' );
					buttonManager.enableCheckout( result.token );
				}
			},
			onError( error ) {
				const errorMessage =
					error.message ||
					`${ error.status || 'Error' } ${
						error.statusCode ? `(${ error.statusCode })` : ''
					}`;
				setError( errorMessage );
				console.error( 'Payment Request error:', error );
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
	}, [] );

	// Setup payment hook
	useEffect( () => {
		const unsubscribe = onPaymentSetup( () => {
			return buttonManager.getPaymentData();
		} );

		return () => unsubscribe();
	}, [ onPaymentSetup ] );

	// Setup checkout success hook
	useEffect( () => {
		const unsubscribe = onCheckoutSuccess(
			async ( { processingResponse } ) => {
				const { paymentDetails } = processingResponse;

				// If no paymentId, backend handles everything (redirect flow)
				if ( ! paymentDetails?.paymentId ) {
					return {
						type: props.emitResponse.responseTypes.SUCCESS,
					};
				}

				setIsConfirming( true );

				try {
					// Component mode: confirm payment with token
					const paymentId = paymentDetails.paymentId;
					const tokenValue = paymentDetails.token;
					// eslint-disable-next-line no-undef
					const result = await monei.confirmPayment( {
						paymentId,
						paymentToken: tokenValue,
					} );

					if ( result.nextAction && result.nextAction.mustRedirect ) {
						return {
							type: props.emitResponse.responseTypes.SUCCESS,
							redirectUrl: result.nextAction.redirectUrl,
						};
					}
					if ( result.status === 'FAILED' ) {
						const failUrl = new URL( paymentDetails.failUrl );
						failUrl.searchParams.set( 'status', 'FAILED' );
						return {
							type: props.emitResponse.responseTypes.SUCCESS,
							redirectUrl: failUrl.toString(),
						};
					} else {
						// Always include payment ID in redirect URL for order verification
						const { orderId, paymentId } = paymentDetails;
						const url = new URL( paymentDetails.completeUrl );
						url.searchParams.set( 'id', paymentId );
						url.searchParams.set( 'orderId', orderId );
						url.searchParams.set( 'status', result.status );

						return {
							type: props.emitResponse.responseTypes.SUCCESS,
							redirectUrl: url.toString(),
						};
					}
				} catch ( error ) {
					console.error(
						'Error during payment confirmation:',
						error
					);
					setIsConfirming( false );
					return {
						type: props.emitResponse.responseTypes.ERROR,
						message: error.message || 'Payment confirmation failed',
						messageContext:
							props.emitResponse.noticeContexts.PAYMENTS,
					};
				}
			}
		);

		return () => unsubscribe();
	}, [ onCheckoutSuccess ] );

	return (
		<fieldset className="monei-fieldset monei-payment-request-fieldset">
			{ isConfirming &&
				createPortal(
					<div className="monei-payment-overlay" />,
					document.body
				) }
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
			{ error && <div className="monei-error">{ error }</div> }
		</fieldset>
	);
};
