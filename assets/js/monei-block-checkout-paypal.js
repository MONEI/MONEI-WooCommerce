( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const { useEffect, useState, createPortal, useRef, useCallback } =
		wp.element;
	const { useSelect } = wp.data;
	const paypalData = wc.wcSettings.getSetting( 'monei_paypal_data' );

	const MoneiPayPalContent = ( props ) => {
		const { responseTypes } = props.emitResponse;
		const { onPaymentSetup, onCheckoutSuccess } = props.eventRegistration;
		const { activePaymentMethod } = props;

		// Use refs instead of plain variables
		const requestTokenRef = useRef( null );
		const paypalInstanceRef = useRef( null );
		const lastAmountRef = useRef( null );
		const isInitializedRef = useRef( false );

		// Check if redirect flow is enabled
		const isRedirectFlow = paypalData.redirectFlow === true;

		// State for confirmation overlay and error handling
		const [ isConfirming, setIsConfirming ] = useState( false );
		const [ error, setError ] = useState( '' );
		const [ isLoading, setIsLoading ] = useState( false );

		// Subscribe to cart totals
		const cartTotals = useSelect( ( select ) => {
			return select( 'wc/store/cart' ).getCartTotals();
		}, [] );

		/**
		 * Create or re-render PayPal instance with specified amount
		 * @param {number} amount - Payment amount in cents
		 */
		const createOrRenderPayPal = useCallback( ( amount ) => {
			// Clean up existing instance
			if ( paypalInstanceRef.current?.close ) {
				try {
					paypalInstanceRef.current.close();
				} catch ( e ) {
					// Silent fail
				}
			}

			const paypalContainer =
				document.getElementById( 'paypal-container' );
			if ( ! paypalContainer ) {
				console.error( 'PayPal container not found' );
				return;
			}

			// Show skeleton loading state
			setIsLoading( true );

			// Clear container
			paypalContainer.innerHTML = '';

			// eslint-disable-next-line no-undef
			const paypalInstance = monei.PayPal( {
				accountId: paypalData.accountId,
				sessionId: paypalData.sessionId,
				language: paypalData.language,
				amount,
				currency: paypalData.currency,
				style: paypalData.paypalStyle || {},
				onSubmit( result ) {
					if ( result.token ) {
						setError( '' );
						requestTokenRef.current = result.token;
						const placeOrderButton = document.querySelector(
							'.wc-block-components-checkout-place-order-button'
						);
						if ( placeOrderButton ) {
							placeOrderButton.style.color = '';
							placeOrderButton.style.backgroundColor = '';
							placeOrderButton.disabled = false;
							placeOrderButton.click();
						} else {
							console.error( 'Place Order button not found.' );
						}
					}
				},
				onError( error ) {
					const errorMessage =
						error.message ||
						`${ error.status || 'Error' } - ${
							error.statusMessage || 'Payment failed'
						}`;
					setError( errorMessage );
					console.error( 'PayPal error:', error );
				},
			} );

			paypalInstance.render( paypalContainer );
			paypalInstanceRef.current = paypalInstance;

			// Remove skeleton loading state after rendering
			setTimeout( () => {
				setIsLoading( false );
			}, 1000 );
		}, [] );

		useEffect( () => {
			// Don't modify the Place Order button if using redirect flow
			if ( isRedirectFlow ) {
				return;
			}

			const placeOrderButton = document.querySelector(
				'.wc-block-components-checkout-place-order-button'
			);
			if ( activePaymentMethod === 'monei_paypal' ) {
				if ( placeOrderButton ) {
					//on hover over the button the text should not change color to white
					placeOrderButton.style.color = 'black';
					placeOrderButton.style.backgroundColor = '#ccc';
					placeOrderButton.disabled = true;
				}
			}
			return () => {
				if ( paypalInstanceRef.current ) {
					paypalInstanceRef.current.close();
					paypalInstanceRef.current = null;
				}
				if ( placeOrderButton ) {
					placeOrderButton.style.color = '';
					placeOrderButton.style.backgroundColor = '';
					placeOrderButton.disabled = false;
				}
			};
		}, [ activePaymentMethod, isRedirectFlow ] );

		/**
		 * Initialize MONEI PayPal component and handle token creation.
		 */
		const initMoneiPayPal = useCallback( () => {
			// eslint-disable-next-line no-undef
			if ( typeof monei === 'undefined' || ! monei.PayPal ) {
				console.error( 'MONEI SDK is not available' );
				return;
			}

			const currentTotal = cartTotals?.total_price
				? parseInt( cartTotals.total_price )
				: Math.round( paypalData.total * 100 );

			lastAmountRef.current = currentTotal;
			createOrRenderPayPal( currentTotal );
		}, [ cartTotals, createOrRenderPayPal ] );

		/**
		 * Update the amount in the existing PayPal instance
		 */
		const updatePaypalAmount = useCallback( () => {
			const currentTotal = cartTotals?.total_price
				? parseInt( cartTotals.total_price )
				: Math.round( paypalData.total * 100 );

			// Only update if amount actually changed
			if ( currentTotal === lastAmountRef.current ) {
				return;
			}

			lastAmountRef.current = currentTotal;

			if ( paypalInstanceRef.current ) {
				createOrRenderPayPal( currentTotal );
			}
		}, [ cartTotals, createOrRenderPayPal ] );

		// Initialize on mount
		useEffect( () => {
			// Don't initialize PayPal component if using redirect flow
			if ( isRedirectFlow ) {
				return;
			}

			// eslint-disable-next-line no-undef
			if (
				typeof monei !== 'undefined' &&
				monei.PayPal &&
				! isInitializedRef.current
			) {
				initMoneiPayPal();
				isInitializedRef.current = true;
			} else if ( ! monei || ! monei.PayPal ) {
				console.error( 'MONEI SDK is not available' );
			}
		}, [ initMoneiPayPal, isRedirectFlow ] );

		// Update amount when cart totals change
		useEffect( () => {
			if (
				isInitializedRef.current &&
				paypalInstanceRef.current &&
				cartTotals
			) {
				updatePaypalAmount();
			}
		}, [ cartTotals, updatePaypalAmount ] );

		// Cleanup on unmount
		useEffect( () => {
			return () => {
				if ( paypalInstanceRef.current?.close ) {
					try {
						paypalInstanceRef.current.close();
					} catch ( e ) {
						// Silent cleanup
					}
				}
			};
		}, [] );

		// Hook into the payment setup
		useEffect( () => {
			const unsubscribePaymentSetup = onPaymentSetup( () => {
				// In redirect mode, no token is needed - form submits normally
				if ( isRedirectFlow ) {
					return {
						type: responseTypes.SUCCESS,
						meta: {
							paymentMethodData: {
								monei_is_block_checkout: 'yes',
							},
						},
					};
				}

				// If no token was created, fail
				if ( ! requestTokenRef.current ) {
					return {
						type: responseTypes.ERROR,
						message: __(
							'MONEI token could not be generated.',
							'monei'
						),
					};
				}
				return {
					type: responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							monei_payment_request_token:
								requestTokenRef.current,
							monei_is_block_checkout: 'yes',
						},
					},
				};
			} );

			return () => {
				unsubscribePaymentSetup();
			};
		}, [
			onPaymentSetup,
			isRedirectFlow,
			responseTypes.SUCCESS,
			responseTypes.ERROR,
		] );

		useEffect( () => {
			const unsubscribe = onCheckoutSuccess(
				async ( { processingResponse } ) => {
					const { paymentDetails } = processingResponse;

					// In redirect mode, backend returns redirect URL and no paymentId
					// WooCommerce Blocks handles redirect automatically
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
						const result = await monei.confirmPayment( {
							paymentId,
							paymentToken: tokenValue,
						} );

						if (
							result.nextAction &&
							result.nextAction.mustRedirect
						) {
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
							message:
								error.message || 'Payment confirmation failed',
							messageContext:
								props.emitResponse.noticeContexts.PAYMENTS,
						};
					}
				}
			);

			return () => {
				unsubscribe();
			};
		}, [
			onCheckoutSuccess,
			props.emitResponse.noticeContexts.PAYMENTS,
			responseTypes.SUCCESS,
			responseTypes.ERROR,
		] );

		// In redirect mode, show description instead of PayPal button
		if ( isRedirectFlow ) {
			return (
				<div className="monei-redirect-description">
					{ paypalData.description }
				</div>
			);
		}

		return (
			<fieldset className="monei-fieldset monei-payment-request-fieldset">
				{ isConfirming &&
					createPortal(
						<div className="monei-payment-overlay" />,
						document.body
					) }
				<div
					id="paypal-container"
					className={ `monei-payment-request-container${
						isLoading
							? ' wc-block-components-skeleton__element'
							: ''
					}` }
				>
					{ /* PayPal button will be inserted here */ }
				</div>
				{ error && <div className="monei-error">{ error }</div> }
			</fieldset>
		);
	};

	const paypalLabel = () => {
		return (
			<div className="monei-label-container">
				{ paypalData.title && (
					<span className="monei-text">
						{ __( paypalData.title, 'monei' ) }
					</span>
				) }
				{ paypalData?.logo && (
					<div className="monei-logo">
						<img src={ paypalData.logo } alt="" />
					</div>
				) }
			</div>
		);
	};

	const MoneiPayPalPaymentMethod = {
		name: 'monei_paypal',
		label: paypalLabel(),
		ariaLabel: __( paypalData.title, 'monei' ),
		content: <MoneiPayPalContent />,
		edit: <div> { __( paypalData.title, 'monei' ) }</div>,
		canMakePayment: () => true,
		supports: paypalData.supports,
	};

	registerPaymentMethod( MoneiPayPalPaymentMethod );
} )();
