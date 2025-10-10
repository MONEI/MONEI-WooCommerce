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
	const { useEffect, useRef, useState, createPortal, useMemo, useCallback } =
		wp.element;
	const { useSelect } = wp.data;
	const { onPaymentSetup, onCheckoutSuccess } = props.eventRegistration;
	const { activePaymentMethod, emitResponse } = props;
	const { responseTypes, noticeContexts } = emitResponse;
	const moneiData =
		props.moneiData ||
		// eslint-disable-next-line no-undef
		wc.wcSettings.getSetting( 'monei_apple_google_data' );

	const paymentRequestRef = useRef( null );
	const lastAmountRef = useRef( null );
	const isInitializedRef = useRef( false );
	const [ isConfirming, setIsConfirming ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ isLoading, setIsLoading ] = useState( false );

	// Subscribe to cart totals
	const cartTotals = useSelect( ( select ) => {
		return select( 'wc/store/cart' ).getCartTotals();
	}, [] );
	const isActive =
		activePaymentMethod ===
		( props.paymentMethodId || 'monei_apple_google' );

	// Memoize buttonManager config to ensure stability
	const buttonManagerConfig = useMemo(
		() => ( {
			isActive,
			emitResponse,
			tokenFieldName: 'monei_payment_request_token',
			errorMessage: moneiData.tokenErrorString,
		} ),
		[ isActive, emitResponse, moneiData.tokenErrorString ]
	);

	const buttonManager = useButtonStateManager( buttonManagerConfig );

	/**
	 * Create or re-render MONEI Payment Request with specified amount
	 * @param {number} amount - Payment amount in cents
	 */
	const createOrRenderPaymentRequest = useCallback(
		( amount ) => {
			// Clean up existing instance
			if ( paymentRequestRef.current?.close ) {
				try {
					paymentRequestRef.current.close();
				} catch ( e ) {
					// Silent fail
				}
			}

			const container = document.getElementById(
				'payment-request-container'
			);
			if ( ! container ) {
				console.error( 'Payment request container not found' );
				return;
			}

			// Show skeleton loading state
			setIsLoading( true );

			// Clear container
			container.innerHTML = '';

			// eslint-disable-next-line no-undef
			const paymentRequest = monei.PaymentRequest( {
				accountId: moneiData.accountId,
				sessionId: moneiData.sessionId,
				language: moneiData.language,
				amount,
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

			paymentRequest.render( container );
			paymentRequestRef.current = paymentRequest;

			// Remove skeleton loading state after rendering
			setTimeout( () => {
				setIsLoading( false );
			}, 1000 );
		},
		[ moneiData, setError, buttonManager ]
	);

	/**
	 * Initialize MONEI Payment Request
	 */
	const initPaymentRequest = useCallback( () => {
		// eslint-disable-next-line no-undef
		if ( typeof monei === 'undefined' || ! monei.PaymentRequest ) {
			console.error( 'MONEI SDK is not available' );
			return;
		}

		const currentTotal = cartTotals?.total_price
			? parseInt( cartTotals.total_price )
			: Math.round( moneiData.total * 100 );

		lastAmountRef.current = currentTotal;

		createOrRenderPaymentRequest( currentTotal );
	}, [ cartTotals, moneiData.total, createOrRenderPaymentRequest ] );

	/**
	 * Update the amount in the existing Payment Request instance
	 */
	const updatePaymentRequestAmount = useCallback( () => {
		const currentTotal = cartTotals?.total_price
			? parseInt( cartTotals.total_price )
			: Math.round( moneiData.total * 100 );

		// Only update if amount actually changed
		if ( currentTotal === lastAmountRef.current ) {
			return;
		}

		lastAmountRef.current = currentTotal;

		createOrRenderPaymentRequest( currentTotal );
	}, [ cartTotals, moneiData.total, createOrRenderPaymentRequest ] );

	// Initialize on mount
	useEffect( () => {
		// eslint-disable-next-line no-undef
		if (
			typeof monei !== 'undefined' &&
			monei.PaymentRequest &&
			! isInitializedRef.current
		) {
			initPaymentRequest();
			isInitializedRef.current = true;
		} else if ( ! monei || ! monei.PaymentRequest ) {
			console.error( 'MONEI SDK is not available' );
		}
	}, [ initPaymentRequest ] );

	// Update amount when cart totals change
	useEffect( () => {
		if (
			isInitializedRef.current &&
			paymentRequestRef.current &&
			cartTotals
		) {
			updatePaymentRequestAmount();
		}
	}, [ cartTotals, updatePaymentRequestAmount ] );

	// Cleanup on unmount
	useEffect( () => {
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
	}, [ onPaymentSetup, buttonManager ] );

	// Setup checkout success hook
	useEffect( () => {
		const unsubscribe = onCheckoutSuccess(
			async ( { processingResponse } ) => {
				const { paymentDetails } = processingResponse;

				// If no paymentId, backend handles everything (redirect flow)
				if ( ! paymentDetails?.paymentId ) {
					return {
						type: responseTypes.SUCCESS,
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
							type: responseTypes.SUCCESS,
							redirectUrl: result.nextAction.redirectUrl,
						};
					}
					if ( result.status === 'FAILED' ) {
						const failUrl = new URL( paymentDetails.failUrl );
						failUrl.searchParams.set( 'status', 'FAILED' );
						return {
							type: responseTypes.SUCCESS,
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
							type: responseTypes.SUCCESS,
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
						type: responseTypes.ERROR,
						message: error.message || 'Payment confirmation failed',
						messageContext: noticeContexts.PAYMENTS,
					};
				}
			}
		);

		return () => unsubscribe();
	}, [ onCheckoutSuccess, responseTypes, noticeContexts ] );

	return (
		<fieldset className="monei-fieldset monei-payment-request-fieldset">
			{ isConfirming &&
				createPortal(
					<div className="monei-payment-overlay" />,
					document.body
				) }
			<div
				id="payment-request-container"
				className={ `monei-payment-request-container${
					isLoading ? ' wc-block-components-skeleton__element' : ''
				}` }
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
