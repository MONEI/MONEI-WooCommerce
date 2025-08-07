const { useState, useEffect, useRef, useCallback } = wp.element;

/**
 * Hook for managing cardholder name validation
 * @param {Object} config - Configuration object
 * @param {string} config.errorMessage - Error message to display
 * @param {RegExp} config.pattern - Validation pattern
 * @returns {Object}
 */
export const useCardholderName = ( config = {} ) => {
    const pattern = config.pattern || /^[A-Za-zÀ-ú\s-]{5,50}$/;
    const [ value, setValue ] = useState( '' );
    const [ error, setError ] = useState( '' );
    const [ touched, setTouched ] = useState( false );

    const validate = useCallback( ( name = value ) => {
        if ( ! name || ! pattern.test( name ) ) {
            const errorMsg = config.errorMessage || 'Invalid cardholder name';
            setError( errorMsg );
            return false;
        }
        setError( '' );
        return true;
    }, [ value, pattern, config.errorMessage ] );

    const handleChange = useCallback( ( e ) => {
        const newValue = e.target.value;
        setValue( newValue );
        if ( touched ) {
            validate( newValue );
        }
    }, [ touched, validate ] );

    const handleBlur = useCallback( () => {
        setTouched( true );
        validate();
    }, [ validate ] );

    return {
        value,
        error,
        touched,
        isValid: ! error && touched,
        handleChange,
        handleBlur,
        validate,
        reset: () => {
            setValue( '' );
            setError( '' );
            setTouched( false );
        }
    };
};

/**
 * Hook for managing MONEI Card Input
 * @param {Object} config - MONEI configuration
 * @returns {Object}
 */
export const useMoneiCardInput = ( config ) => {
    const [ isReady, setIsReady ] = useState( false );
    const [ error, setError ] = useState( '' );
    const [ isValid, setIsValid ] = useState( false );
    const [ token, setToken ] = useState( null );
    const [ isCreatingToken, setIsCreatingToken ] = useState( false );
    const cardInputRef = useRef( null );
    const containerRef = useRef( null );
    const hasInitialized = useRef(false);

    /**
     * Initialize MONEI Card Input
     */
    const initializeCardInput = useCallback( () => {
        console.log('initializing MONEI Card Input');
        if ( typeof monei === 'undefined' || ! monei.CardInput ) {
            setError( 'MONEI SDK is not available' );
            return;
        }

        if ( ! containerRef.current ) {
            setError( 'Card input container not found' );
            return;
        }

        const style = {
            input: {
                color: 'hsla(0,0%,7%,.8)',
                fontSize: '16px',
                'box-sizing': 'border-box',
                '::placeholder': {
                    color: 'hsla(0,0%,7%,.8)',
                },
                '-webkit-autofill': {
                    backgroundColor: '#FAFFBD',
                },
            },
            invalid: {
                color: '#fa755a',
            },
        };

        try {
            const cardInput = monei.CardInput( {
                accountId: config.accountId,
                sessionId: config.sessionId,
                language: config.language,
                style,
                onFocus() {
                    if ( containerRef.current ) {
                        containerRef.current.classList.add( 'is-focused' );
                    }
                },
                onBlur() {
                    if ( containerRef.current ) {
                        containerRef.current.classList.remove( 'is-focused' );
                    }
                },
                onChange( event ) {
                    if ( event.isTouched && event.error ) {
                        setError( event.error );
                        setIsValid( false );
                        if ( containerRef.current ) {
                            containerRef.current.classList.add( 'is-invalid' );
                        }
                    } else {
                        setError( '' );
                        if ( event.isTouched ) {
                            setIsValid( true );
                        }
                        if ( containerRef.current ) {
                            containerRef.current.classList.remove( 'is-invalid' );
                        }
                    }
                },
                onEnter() {
                    // Trigger token creation on Enter key
                    if ( cardInputRef.current ) {
                        createToken();
                    }
                },
            } );

            cardInput.render( containerRef.current );
            cardInputRef.current = cardInput;
            setIsReady( true );
            setError( '' );
        } catch ( err ) {
            setError( err.message || 'Failed to initialize card input' );
            setIsReady( false );
        }
    }, [ config ] );

    /**
     * Create payment token
     */
    const createToken = useCallback( async () => {
        if ( ! cardInputRef.current || ! monei?.createToken ) {
            setError( 'Card input not initialized' );
            return null;
        }

        setIsCreatingToken( true );
        setError( '' );

        try {
            const result = await monei.createToken( cardInputRef.current );

            if ( result.error ) {
                setError( result.error );
                return null;
            }

            setToken( result.token );
            return result.token;
        } catch ( err ) {
            setError( err.message || 'Token creation failed' );
            return null;
        } finally {
            setIsCreatingToken( false );
        }
    }, [] );

    /**
     * Reset card input
     */
    const reset = useCallback( () => {
        if ( cardInputRef.current && cardInputRef.current.clear ) {
            cardInputRef.current.clear();
        }
        setToken( null );
        setError( '' );
        setIsValid( false );
    }, [] );

    // Initialize on mount
    useEffect( () => {
        if (!hasInitialized.current) {
            const timer = setTimeout( () => {
                initializeCardInput();
                hasInitialized.current = true;
            }, 500 );
            return () => clearTimeout( timer );
        }
    }, [] );

    // Cleanup on unmount
    useEffect( () => {
        return () => {
            if ( cardInputRef.current && cardInputRef.current.destroy ) {
                try {
                    cardInputRef.current.destroy();
                } catch ( e ) {
                    // Silent cleanup
                }
            }
        };
    }, [] );

    return {
        isReady,
        error,
        isValid,
        token,
        isCreatingToken,
        containerRef,
        createToken,
        reset
    };
};

/**
 * Hook for managing form errors
 * @returns {Object}
 */
export const useFormErrors = () => {
    const [ errors, setErrors ] = useState( {} );

    const setError = useCallback( ( field, message ) => {
        setErrors( prev => ( {
            ...prev,
            [ field ]: message
        } ) );
    }, [] );

    const clearError = useCallback( ( field ) => {
        setErrors( prev => {
            const newErrors = { ...prev };
            delete newErrors[ field ];
            return newErrors;
        } );
    }, [] );

    const clearAllErrors = useCallback( () => {
        setErrors( {} );
    }, [] );

    const hasErrors = useCallback( () => {
        return Object.keys( errors ).length > 0;
    }, [ errors ] );

    const getError = useCallback( ( field ) => {
        return errors[ field ] || '';
    }, [ errors ] );

    return {
        errors,
        setError,
        clearError,
        clearAllErrors,
        hasErrors,
        getError
    };
};