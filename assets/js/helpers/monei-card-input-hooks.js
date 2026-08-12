const { useState, useEffect, useRef, useCallback, useMemo } = wp.element;

/**
 * Hook for managing cardholder name validation
 * @param {Object} config              - Configuration object
 * @param {string} config.errorMessage - Error message to display
 * @param {RegExp} config.pattern      - Validation pattern
 * @return {Object}
 */
export const useCardholderName = ( config = {} ) => {
	const pattern = useMemo(
		() => config.pattern || /^[A-Za-zÀ-ú\s-]{5,50}$/,
		[ config.pattern ]
	);
	const [ value, setValue ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ touched, setTouched ] = useState( false );

	const validate = useCallback(
		( name = value ) => {
			if ( ! name || ! pattern.test( name ) ) {
				const errorMsg =
					config.errorMessage || 'Invalid cardholder name';
				setError( errorMsg );
				return false;
			}
			setError( '' );
			return true;
		},
		[ value, pattern, config.errorMessage ]
	);

	const handleChange = useCallback(
		( e ) => {
			const newValue = e.target.value;
			setValue( newValue );
			if ( touched ) {
				validate( newValue );
			}
		},
		[ touched, validate ]
	);

	const handleBlur = useCallback( () => {
		setTouched( true );
		validate();
	}, [ validate ] );

	const reset = useCallback( () => {
		setValue( '' );
		setError( '' );
		setTouched( false );
	}, [] );

	return useMemo(
		() => ( {
			value,
			error,
			touched,
			isValid: ! error && touched,
			handleChange,
			handleBlur,
			validate,
			reset,
		} ),
		[ value, error, touched, handleChange, handleBlur, validate, reset ]
	);
};

/**
 * Hook for managing MONEI Card Input
 * @param {Object} config - MONEI configuration
 * @param {number} amount - Payment amount in minor units
 * @return {Object}
 */
export const useMoneiCardInput = ( config, amount ) => {
	const [ isReady, setIsReady ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ isValid, setIsValid ] = useState( false );
	const [ token, setToken ] = useState( null );
	const [ isCreatingToken, setIsCreatingToken ] = useState( false );
	const cardInputRef = useRef( null );
	const containerRef = useRef( null );
	const hasInitialized = useRef( false );
	// Held in a ref so a moving amount never changes `config` identity, which
	// would re-arm the delayed init effect below.
	const amountRef = useRef( amount );
	amountRef.current = amount;

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
				const errorMessage =
					result.error.message ||
					( typeof result.error === 'string'
						? result.error
						: 'Token creation failed' );
				setError( errorMessage );
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
	 * Initialize MONEI Card Input
	 */
	const initializeCardInput = useCallback( () => {
		if ( typeof monei === 'undefined' || ! monei.CardInput ) {
			setError( 'MONEI SDK is not available' );
			return;
		}

		if ( ! containerRef.current ) {
			setError( 'Card input container not found' );
			return;
		}

		try {
			const cardInput = monei.CardInput( {
				accountId: config.accountId,
				sessionId: config.sessionId,
				language: config.language,
				amount: amountRef.current,
				currency: config.currency,
				style: config.style || {},
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
						const errorMessage =
							event.error.message ||
							( typeof event.error === 'string'
								? event.error
								: 'Validation error' );
						setError( errorMessage );
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
							containerRef.current.classList.remove(
								'is-invalid'
							);
						}
					}
				},
				onEnter() {
					// Trigger token creation on Enter key
					if ( cardInputRef.current ) {
						createToken().catch( ( err ) => {
							console.error(
								'Token creation failed on Enter:',
								err
							);
						} );
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
	}, [ config, createToken ] );

	/**
	 * Update props on the live card input instance
	 * @param {Object} props - Props to forward to the instance
	 */
	const updateProps = useCallback( ( props ) => {
		if ( ! cardInputRef.current || ! cardInputRef.current.updateProps ) {
			return;
		}
		cardInputRef.current.updateProps( props );
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
		if ( ! hasInitialized.current ) {
			const timer = setTimeout( () => {
				initializeCardInput();
				hasInitialized.current = true;
			}, 500 );
			return () => clearTimeout( timer );
		}
	}, [ initializeCardInput ] );

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

	return useMemo(
		() => ( {
			isReady,
			error,
			isValid,
			token,
			isCreatingToken,
			containerRef,
			createToken,
			updateProps,
			reset,
		} ),
		[
			isReady,
			error,
			isValid,
			token,
			isCreatingToken,
			containerRef,
			createToken,
			updateProps,
			reset,
		]
	);
};

/**
 * Hook for managing form errors
 * @return {Object}
 */
export const useFormErrors = () => {
	const [ errors, setErrors ] = useState( {} );

	const setError = useCallback( ( field, message ) => {
		setErrors( ( prev ) => ( {
			...prev,
			[ field ]: message,
		} ) );
	}, [] );

	const clearError = useCallback( ( field ) => {
		setErrors( ( prev ) => {
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

	const getError = useCallback(
		( field ) => {
			return errors[ field ] || '';
		},
		[ errors ]
	);

	return useMemo(
		() => ( {
			errors,
			setError,
			clearError,
			clearAllErrors,
			hasErrors,
			getError,
		} ),
		[ errors, setError, clearError, clearAllErrors, hasErrors, getError ]
	);
};
