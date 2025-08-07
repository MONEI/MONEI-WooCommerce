import { useCardholderName, useMoneiCardInput, useFormErrors } from '../helpers/monei-card-input-hooks';

const { useEffect, useState, useRef, useCallback } = wp.element;

/**
 * MONEI Credit Card Content Component
 * @param {Object} props - Component props
 * @returns {React.Element}
 */
export const MoneiCCContent = ( props ) => {
    const { responseTypes } = props.emitResponse;
    const { onPaymentSetup, onCheckoutValidation, onCheckoutSuccess } = props.eventRegistration;
    const moneiData = props.moneiData || wc.wcSettings.getSetting( 'monei_data' );
    const isHostedWorkflow = moneiData.redirect === 'yes';
    const shouldSavePayment = props.shouldSavePayment;
    // State management
    const [ isProcessing, setIsProcessing ] = useState( false );
    const tokenRef = useRef( null );

    // Form error management
    const formErrors = useFormErrors();

    // Cardholder name management
    const cardholderName = useCardholderName( {
        errorMessage: moneiData.nameErrorString,
        pattern: /^[A-Za-zÀ-ú\s-]{5,50}$/
    } );

    // Card input management
    const cardInput = useMoneiCardInput( {
        accountId: moneiData.accountId,
        sessionId: moneiData.sessionId,
        language: moneiData.language
    } );
    // If hosted workflow, show redirect message
    if ( isHostedWorkflow ) {
        return (
            <div className="wc-block-components-text-input wc-block-components-address-form__email">
                <p>{ moneiData.redirected }</p>
            </div>
        );
    }

    /**
     * Create payment token
     */
    const createPaymentToken = useCallback( async () => {
        if ( tokenRef.current ) {
            return tokenRef.current;
        }

        const newToken = await cardInput.createToken();
        if ( newToken ) {
            tokenRef.current = newToken;
        }
        return newToken;
    }, [ cardInput ] );

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
    }, [ cardholderName, cardInput.isValid, formErrors, moneiData.cardErrorString ] );

    // Setup validation hook
    useEffect( () => {
        const unsubscribe = onCheckoutValidation( async () => {
            // Validate cardholder name
            if ( ! cardholderName.validate() ) {
                return {
                    errorMessage: cardholderName.error
                };
            }

            // Check card input error
            if ( cardInput.error ) {
                return {
                    errorMessage: cardInput.error
                };
            }

            // Check card input validity
            if ( ! cardInput.isValid ) {
                return {
                    errorMessage: moneiData.cardErrorString
                };
            }

            // Try to create token if not exists
            if ( ! tokenRef.current && ! cardInput.token ) {
                const newToken = await createPaymentToken();
                if ( ! newToken ) {
                    return {
                        errorMessage: moneiData.tokenErrorString
                    };
                }
            }

            return true;
        } );

        return unsubscribe;
    }, [
        onCheckoutValidation,
        cardholderName,
        cardInput,
        createPaymentToken,
        moneiData.cardErrorString,
        moneiData.tokenErrorString
    ] );

    // Setup payment hook
    useEffect( () => {
        const unsubscribe = onPaymentSetup( async () => {
            setIsProcessing( true );

            try {
                // Get or create token
                const paymentToken = tokenRef.current || cardInput.token || await createPaymentToken();

                if ( ! paymentToken ) {
                    return {
                        type: responseTypes.ERROR,
                        message: moneiData.tokenErrorString
                    };
                }

                return {
                    type: responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            monei_payment_token: paymentToken,
                            monei_cardholder_name: cardholderName.value,
                            monei_is_block_checkout: 'yes'
                        }
                    }
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
        moneiData.tokenErrorString
    ] );

    // Setup checkout success hook
    useEffect( () => {
        const unsubscribe = onCheckoutSuccess( async ( { processingResponse } ) => {
            const { paymentDetails } = processingResponse;

            if ( ! paymentDetails?.paymentId ) {
                console.error( 'No paymentId found in paymentDetails' );
                return false;
            }

            try {
                const result = await monei.confirmPayment( {
                    paymentId: paymentDetails.paymentId,
                    paymentToken: paymentDetails.token,
                    paymentMethod: {
                        card: {
                            cardholderName: cardholderName.value
                        }
                    }
                } );

                if ( result.status === 'FAILED' ) {
                    window.location.href = `${ paymentDetails.failUrl }&status=FAILED`;
                } else {
                    let redirectUrl = paymentDetails.completeUrl;

                    if ( shouldSavePayment === true ) {
                        const { orderId, paymentId } = paymentDetails;
                        redirectUrl = `${ paymentDetails.completeUrl }&id=${ paymentId }&orderId=${ orderId }`;
                    }

                    window.location.href = redirectUrl;
                }
            } catch ( error ) {
                console.error( 'Error during payment confirmation:', error );
                window.location.href = paymentDetails.failUrl;
            }

            return true;
        } );

        return unsubscribe;
    }, [ onCheckoutSuccess, cardholderName.value, shouldSavePayment ] );

    return (
        <fieldset className="monei-fieldset monei-card-fieldset wc-block-components-form">
            { moneiData?.description && <p>{ moneiData.description }</p> }
            {/* Cardholder Name Input */}
            <div className="monei-input-container wc-block-components-text-input">
                <input
                    type="text"
                    id="cardholder_name"
                    name="cardholder_name"
                    data-testid="cardholder-name-input"
                    placeholder={ moneiData.cardholderName }
                    required
                    className={ `monei-input ${ cardholderName.error ? 'has-error' : '' }` }
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

            {/* Card Input Container */}
            <div
                id="monei-card-input"
                className="monei-card-input"
                ref={ cardInput.containerRef }
            />

            {/* Hidden token input for form compatibility */}
            <input
                type="hidden"
                id="monei_payment_token"
                name="monei_payment_token"
                value={ cardInput.token || '' }
            />

            {/* Card Input Error */}
            { cardInput.error && (
                <div className="wc-block-components-validation-error">
                    { cardInput.error }
                </div>
            ) }

            {/* General Form Error */}
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
 * @returns {React.Element}
 */
export const CreditCardLabel = ( moneiData ) => {
    return (
        <div className="monei-label-container">
            <span className="monei-text">{ moneiData.title }</span>
            { moneiData?.logo && (
                <div className="monei-logo">
                    <img src={ moneiData.logo } alt="" />
                </div>
            ) }
        </div>
    );
};