import {
	useCardholderName,
	useFormErrors,
} from '../helpers/monei-card-input-hooks';
import { useMoneiCardGroup } from '../helpers/monei-card-group-hooks';

const { useEffect, useState, useRef, useCallback, useMemo, createPortal } =
	wp.element;

/**
 * MONEI Credit Card Content Component, split card fields layout
 * @param {Object} props - Component props
 * @return {React.Element}
 */
export const MoneiCCGroupContent = ( props ) => {
	const { responseTypes, noticeContexts } = props.emitResponse;
	const { onPaymentSetup, onCheckoutValidation, onCheckoutSuccess } =
		props.eventRegistration;

	// Memoize moneiData to prevent infinite re-renders from wc.wcSettings.getSetting() returning new object
	const moneiData = useMemo(
		() => props.moneiData || wc.wcSettings.getSetting( 'monei_data' ),
		[ props.moneiData ]
	);

	const isHostedWorkflow = moneiData.redirect === 'yes';
	const shouldSavePayment = props.shouldSavePayment;
	// State management
	const tokenRef = useRef( null );
	const [ isProcessing, setIsProcessing ] = useState( false );
	const [ isConfirming, setIsConfirming ] = useState( false );

	// Form error management
	const formErrors = useFormErrors();

	// Memoize config objects to prevent infinite re-renders
	const cardholderNameConfig = useMemo(
		() => ( {
			errorMessage: moneiData.nameErrorString,
			pattern: /^[A-Za-zÀ-ú\s-]{5,50}$/,
		} ),
		[ moneiData.nameErrorString ]
	);

	// Amount is deliberately kept out of this memo: a moving total would change
	// the config identity and re-arm the delayed card group mount.
	const cardGroupConfig = useMemo(
		() => ( {
			accountId: moneiData.accountId,
			sessionId: moneiData.sessionId,
			language: moneiData.language,
			currency: moneiData.currency,
			style: moneiData.cardInputStyle,
		} ),
		[
			moneiData.accountId,
			moneiData.sessionId,
			moneiData.language,
			moneiData.currency,
			moneiData.cardInputStyle,
		]
	);

	// Cardholder name management
	const cardholderName = useCardholderName( cardholderNameConfig );

	// Card group management — the hook owns the amount sync
	const cardGroup = useMoneiCardGroup( cardGroupConfig, props.amount );

	/**
	 * Create payment token
	 */
	const tokenPromiseRef = useRef( null );

	const createPaymentToken = useCallback( async () => {
		if ( tokenPromiseRef.current ) {
			return tokenPromiseRef.current;
		}

		tokenPromiseRef.current = cardGroup
			.createToken()
			.then( ( newToken ) => {
				if ( newToken ) {
					tokenRef.current = newToken;
				}
				return newToken;
			} )
			.finally( () => {
				tokenPromiseRef.current = null;
			} );

		return tokenPromiseRef.current;
	}, [ cardGroup ] );

	// Setup validation hook
	useEffect( () => {
		const unsubscribe = onCheckoutValidation( async () => {
			// In redirect mode, no client-side validation needed
			if ( isHostedWorkflow ) {
				return true;
			}

			// Validate cardholder name
			if ( ! cardholderName.validate() ) {
				return {
					errorMessage: cardholderName.error,
				};
			}

			// Check card group error
			if ( cardGroup.error ) {
				return {
					errorMessage: cardGroup.error,
				};
			}

			// Check card group validity
			if ( ! cardGroup.isValid ) {
				return {
					errorMessage: moneiData.cardErrorString,
				};
			}

			// Try to create token if not exists
			if ( ! tokenRef.current && ! cardGroup.token ) {
				const newToken = await createPaymentToken();
				if ( ! newToken ) {
					return {
						errorMessage: moneiData.tokenErrorString,
					};
				}
			}

			return true;
		} );

		return unsubscribe;
	}, [
		onCheckoutValidation,
		isHostedWorkflow,
		cardholderName,
		cardGroup,
		createPaymentToken,
		moneiData.cardErrorString,
		moneiData.tokenErrorString,
	] );

	// Setup payment hook
	useEffect( () => {
		const unsubscribe = onPaymentSetup( async () => {
			// In redirect mode, skip token creation — backend handles everything
			if ( isHostedWorkflow ) {
				return {
					type: responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							monei_is_block_checkout: 'yes',
						},
					},
				};
			}

			setIsProcessing( true );

			try {
				// Get or create token
				const paymentToken =
					tokenRef.current ||
					cardGroup.token ||
					( await createPaymentToken() );

				if ( ! paymentToken ) {
					return {
						type: responseTypes.ERROR,
						message: moneiData.tokenErrorString,
					};
				}

				const paymentData = {
					monei_payment_token: paymentToken,
					monei_cardholder_name: cardholderName.value,
					monei_is_block_checkout: 'yes',
				};

				// Only include save payment method flag if checkbox is checked
				if ( shouldSavePayment ) {
					paymentData[ 'wc-monei-new-payment-method' ] = true;
				}

				return {
					type: responseTypes.SUCCESS,
					meta: {
						paymentMethodData: paymentData,
					},
				};
			} finally {
				setIsProcessing( false );
			}
		} );

		return unsubscribe;
	}, [
		onPaymentSetup,
		isHostedWorkflow,
		cardholderName,
		cardGroup,
		createPaymentToken,
		responseTypes,
		moneiData.tokenErrorString,
		shouldSavePayment,
	] );

	// Setup checkout success hook
	useEffect( () => {
		const unsubscribe = onCheckoutSuccess(
			async ( { processingResponse } ) => {
				const { paymentDetails } = processingResponse;

				if ( ! paymentDetails?.paymentId ) {
					console.error( 'No paymentId found in paymentDetails' );
					return {
						type: responseTypes.SUCCESS,
					};
				}

				setIsConfirming( true );

				try {
					const result = await monei.confirmPayment( {
						paymentId: paymentDetails.paymentId,
						paymentToken: paymentDetails.token,
						paymentMethod: {
							card: {
								cardholderName: cardholderName.value,
							},
						},
					} );

					if ( result.status === 'FAILED' ) {
						const failUrl = new URL( paymentDetails.failUrl );
						failUrl.searchParams.set( 'status', 'FAILED' );
						return {
							type: responseTypes.SUCCESS,
							redirectUrl: failUrl.toString(),
						};
					}

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

		return unsubscribe;
	}, [ onCheckoutSuccess, cardholderName, responseTypes, noticeContexts ] );

	// If hosted workflow, show redirect message only
	if ( isHostedWorkflow ) {
		return (
			<div className="monei-redirect-description">
				{ moneiData.description }
			</div>
		);
	}

	return (
		<fieldset className="monei-fieldset monei-card-fieldset wc-block-components-form">
			{ isConfirming &&
				createPortal(
					<div className="monei-payment-overlay" />,
					document.body
				) }
			{ moneiData?.description && <p>{ moneiData.description }</p> }
			{ /* Cardholder Name Input */ }
			<div className="monei-input-container wc-block-components-text-input">
				<input
					type="text"
					id="cardholder_name"
					name="cardholder_name"
					data-testid="cardholder-name-input"
					placeholder={ moneiData.cardholderName }
					required
					className={ `monei-input ${
						cardholderName.error ? 'has-error' : ''
					}` }
					value={ cardholderName.value }
					onChange={ cardholderName.handleChange }
					onBlur={ cardholderName.handleBlur }
					disabled={ isProcessing }
				/>
				{ cardholderName.error && (
					<div className="wc-block-components-validation-error">
						{ cardholderName.error }
					</div>
				) }
			</div>

			{ /* Split Card Field Containers, in tab order */ }
			<div className="monei-card-group">
				<div
					id="monei-card-number"
					className="monei-card-group-field monei-card-number"
					ref={ cardGroup.cardNumberRef }
				/>
				<div
					id="monei-card-expiry"
					className="monei-card-group-field monei-card-expiry"
					ref={ cardGroup.cardExpiryRef }
				/>
				<div
					id="monei-card-cvc"
					className="monei-card-group-field monei-card-cvc"
					ref={ cardGroup.cardCvcRef }
				/>
			</div>

			{ /* Hidden token input for form compatibility */ }
			<input
				type="hidden"
				id="monei_payment_token"
				name="monei_payment_token"
				value={ cardGroup.token || '' }
			/>

			{ /* Card Group Error */ }
			{ cardGroup.error && (
				<div className="wc-block-components-validation-error">
					{ cardGroup.error }
				</div>
			) }

			{ /* General Form Error */ }
			{ formErrors.getError( 'card' ) && (
				<div className="wc-block-components-validation-error">
					{ formErrors.getError( 'card' ) }
				</div>
			) }
		</fieldset>
	);
};
