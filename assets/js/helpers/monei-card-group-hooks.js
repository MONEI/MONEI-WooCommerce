import { getMoneiErrorMessage } from './monei-shared-utils';

const { useState, useEffect, useRef, useCallback, useMemo } = wp.element;

/**
 * Hook for managing the MONEI CardGroup split card fields.
 *
 * Deliberately duplicates `useMoneiCardInput` rather than generalizing it: the
 * single line layout is the live payment path and must stay untouched.
 * @param {Object} config - MONEI configuration
 * @param {number} amount - Payment amount in minor units
 * @return {Object}
 */
export const useMoneiCardGroup = ( config, amount ) => {
	const [ isReady, setIsReady ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ isValid, setIsValid ] = useState( false );
	const [ token, setToken ] = useState( null );
	const [ isCreatingToken, setIsCreatingToken ] = useState( false );
	const groupRef = useRef( null );
	const partsRef = useRef( [] );
	const cardNumberRef = useRef( null );
	const cardExpiryRef = useRef( null );
	const cardCvcRef = useRef( null );
	const hasInitialized = useRef( false );
	// Held in a ref so a moving amount never changes `config` identity, which
	// would re-arm the delayed init effect below.
	const amountRef = useRef( amount );
	amountRef.current = amount;
	const lastAmountRef = useRef( null );

	/**
	 * Create payment token
	 */
	const createToken = useCallback( async () => {
		if ( ! groupRef.current || ! groupRef.current.submit ) {
			setError( 'Card input not initialized' );
			return null;
		}

		setIsCreatingToken( true );
		setError( '' );

		try {
			const result = await groupRef.current.submit();

			if ( result.error ) {
				setError(
					getMoneiErrorMessage(
						result.error,
						'Token creation failed'
					)
				);
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
	 * Initialize the MONEI card group and its three parts
	 * @return {boolean} Whether the group is now mounted
	 */
	const initializeCardGroup = useCallback( () => {
		if ( typeof monei === 'undefined' || ! monei.CardGroup ) {
			setError( 'MONEI SDK is not available' );
			return false;
		}

		if (
			! cardNumberRef.current ||
			! cardExpiryRef.current ||
			! cardCvcRef.current
		) {
			setError( 'Card input container not found' );
			return false;
		}

		try {
			const group = monei.CardGroup( {
				accountId: config.accountId,
				sessionId: config.sessionId,
				language: config.language,
				amount: amountRef.current,
				currency: config.currency,
				style: config.style || {},
				onChange( event ) {
					if ( event.error ) {
						setError(
							getMoneiErrorMessage(
								event.error,
								'Validation error'
							)
						);
						setIsValid( false );
					} else {
						setError( '' );
						setIsValid( !! event.complete );
					}
				},
				onEnter() {
					createToken().catch( ( err ) => {
						console.error( 'Token creation failed on Enter:', err );
					} );
				},
			} );

			// Parts carry no payment identifiers — passing `amount` to a part
			// throws "amount belongs on the CardGroup, not on a part Component".
			const parts = [
				[ monei.CardNumber, cardNumberRef ],
				[ monei.CardExpiry, cardExpiryRef ],
				[ monei.CardCvc, cardCvcRef ],
			].map( ( [ factory, ref ] ) => {
				const part = factory( { group } );
				part.render( ref.current );
				return part;
			} );

			groupRef.current = group;
			partsRef.current = parts;
			setIsReady( true );
			setError( '' );
			return true;
		} catch ( err ) {
			setError( err.message || 'Failed to initialize card input' );
			setIsReady( false );
			return false;
		}
	}, [ config, createToken ] );

	/**
	 * Update props on the live card group instance
	 * @param {Object} props - Props to forward to the instance
	 */
	const updateProps = useCallback( ( props ) => {
		if ( ! groupRef.current || ! groupRef.current.updateProps ) {
			return;
		}
		groupRef.current.updateProps( props );
	}, [] );

	/**
	 * Reset the card fields
	 */
	const reset = useCallback( () => {
		partsRef.current.forEach( ( part ) => {
			if ( part && part.clear ) {
				part.clear();
			}
		} );
		setToken( null );
		setError( '' );
		setIsValid( false );
	}, [] );

	// Initialize on mount. A failed attempt leaves the ref down so a later run
	// can try again, otherwise the shopper keeps empty fields and cannot pay.
	useEffect( () => {
		if ( ! hasInitialized.current ) {
			const timer = setTimeout( () => {
				hasInitialized.current = initializeCardGroup();
			}, 500 );
			return () => clearTimeout( timer );
		}
	}, [ initializeCardGroup ] );

	// Keep the amount live without re-creating the group, which would wipe the
	// card number the shopper already typed.
	useEffect( () => {
		if ( lastAmountRef.current === amount ) {
			return;
		}
		lastAmountRef.current = amount;
		updateProps( { amount } );
	}, [ amount, updateProps ] );

	// Cleanup on unmount — parts before the group
	useEffect( () => {
		return () => {
			partsRef.current.forEach( ( part ) => {
				if ( part && part.destroy ) {
					try {
						part.destroy();
					} catch ( e ) {
						// Silent cleanup
					}
				}
			} );
			partsRef.current = [];

			if ( groupRef.current && groupRef.current.destroy ) {
				try {
					groupRef.current.destroy();
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
			cardNumberRef,
			cardExpiryRef,
			cardCvcRef,
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
			cardNumberRef,
			cardExpiryRef,
			cardCvcRef,
			createToken,
			updateProps,
			reset,
		]
	);
};
