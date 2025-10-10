import {
	useCardholderName,
	useFormErrors,
	useMoneiCardInput,
} from '../helpers/monei-card-input-hooks';

const { useEffect, useState, useRef, useCallback, useMemo, createPortal } =
	wp.element;

/**
 * MONEI Credit Card Content Component
 * @param {Object} props - Component props
 * @return {React.Element}
 */
export const MoneiCCContent = ( props ) => {
	const { responseTypes } = props.emitResponse;
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

	const cardInputConfig = useMemo(
		() => ( {
			accountId: moneiData.accountId,
			sessionId: moneiData.sessionId,
			language: moneiData.language,
			style: moneiData.cardInputStyle,
		} ),
		[
			moneiData.accountId,
			moneiData.sessionId,
			moneiData.language,
			moneiData.cardInputStyle,
		]
	);

	// Cardholder name management
	const cardholderName = useCardholderName( cardholderNameConfig );

	// Card input management
	const cardInput = useMoneiCardInput( cardInputConfig );
	// If hosted workflow, show redirect message
	if ( isHostedWorkflow ) {
		return (
			<div className="monei-redirect-description">
				{ moneiData.description }
			</div>
		);
	}

	/**
	 * Create payment token
	 */
	const tokenPromiseRef = useRef( null );

	const createPaymentToken = useCallback( async () => {
		if ( tokenPromiseRef.current ) {
			return tokenPromiseRef.current;
		}

		tokenPromiseRef.current = cardInput
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
	}, [ cardInput.createToken ] );

	/**
	 * Validate form
	 */
	const validateForm = useCallback( () => {
		let isValid = true;

		// Validate cardholder name
		if ( ! cardholderName.validate() ) {
			isValid = false;
		}

		// Check card input validity
		if ( ! cardInput.isValid ) {
			formErrors.setError( 'card', moneiData.cardErrorString );
			isValid = false;
		} else {
			formErrors.clearError( 'card' );
		}

		return isValid;
	}, [
		cardholderName.validate,
		cardInput.isValid,
		formErrors.setError,
		formErrors.clearError,
		moneiData.cardErrorString,
	] );

	// Setup validation hook
	useEffect( () => {
		const unsubscribe = onCheckoutValidation( async () => {
			// Validate cardholder name
			if ( ! cardholderName.validate() ) {
				return {
					errorMessage: cardholderName.error,
				};
			}

			// Check card input error
			if ( cardInput.error ) {
				return {
					errorMessage: cardInput.error,
				};
			}

			// Check card input validity
			if ( ! cardInput.isValid ) {
				return {
					errorMessage: moneiData.cardErrorString,
				};
			}

			// Try to create token if not exists
			if ( ! tokenRef.current && ! cardInput.token ) {
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
		cardholderName.validate,
		cardholderName.error,
		cardInput.error,
		cardInput.isValid,
		cardInput.token,
		createPaymentToken,
		moneiData.cardErrorString,
		moneiData.tokenErrorString,
	] );

	// Setup payment hook
	useEffect( () => {
		const unsubscribe = onPaymentSetup( async () => {
			setIsProcessing( true );

			try {
				// Get or create token
				const paymentToken =
					tokenRef.current ||
					cardInput.token ||
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
		cardholderName.value,
		cardInput.token,
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
						messageContext:
							props.emitResponse.noticeContexts.PAYMENTS,
					};
				}
			}
		);

		return unsubscribe;
	}, [
		onCheckoutSuccess,
		cardholderName.value,
		responseTypes,
		props.emitResponse.noticeContexts,
	] );

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

			{ /* Card Input Container */ }
			<div
				id="monei-card-input"
				className="monei-card-input"
				ref={ cardInput.containerRef }
			/>

			{ /* Hidden token input for form compatibility */ }
			<input
				type="hidden"
				id="monei_payment_token"
				name="monei_payment_token"
				value={ cardInput.token || '' }
			/>

			{ /* Card Input Error */ }
			{ cardInput.error && (
				<div className="wc-block-components-validation-error">
					{ cardInput.error }
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

/**
 * Credit Card Label Component
 * @param {Object} moneiData - Configuration data
 * @return {React.Element}
 */
export const CreditCardLabel = ( moneiData ) => {
	const cardBrands = moneiData?.cardBrands
		? Object.values( moneiData.cardBrands ).filter(
				( brand ) => brand.title !== 'Card'
		  )
		: [];

	return (
		<div className="monei-label-container">
			{ moneiData.title && (
				<span className="monei-text">{ moneiData.title }</span>
			) }
			{ cardBrands.length > 0 ? (
				<span className="monei-card-brands">
					{ cardBrands.map( ( brand, index ) => (
						<img
							key={ index }
							src={ brand.url }
							alt={ brand.title }
							className="card-brand-icon"
						/>
					) ) }
				</span>
			) : (
				moneiData?.logo && (
					<div className="monei-logo">
						<img src={ moneiData.logo } alt="" />
					</div>
				)
			) }
		</div>
	);
};
