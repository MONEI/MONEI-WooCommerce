( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const { useEffect, useRef, useState, createPortal } = wp.element;
	const { useSelect } = wp.data;
	const bizumData = wc.wcSettings.getSetting( 'monei_bizum_data' );

	const MoneiBizumContent = ( props ) => {
		const { responseTypes } = props.emitResponse;
		const { onPaymentSetup, onCheckoutSuccess } = props.eventRegistration;
		const { activePaymentMethod } = props;

		// Check if redirect flow is enabled
		const isRedirectFlow = bizumData.redirectFlow === true;

		// State for confirmation overlay
		const [ isConfirming, setIsConfirming ] = useState( false );

		// Use useRef to persist values across re-renders
		const requestTokenRef = useRef( null );
		const currentBizumInstanceRef = useRef( null );
		const lastAmountRef = useRef( null );
		const isInitializedRef = useRef( false );

		// Subscribe to cart totals
		const cartTotals = useSelect( ( select ) => {
			return select( 'wc/store/cart' ).getCartTotals();
		}, [] );

		useEffect( () => {
			// Don't modify the Place Order button if using redirect flow
			if ( isRedirectFlow ) {
				return;
			}

			const placeOrderButton = document.querySelector(
				'.wc-block-components-button.wp-element-button.wc-block-components-checkout-place-order-button.wc-block-components-checkout-place-order-button'
			);
			if ( activePaymentMethod === 'monei_bizum' ) {
				if ( placeOrderButton ) {
					//on hover over the button the text should not change color to white
					placeOrderButton.style.color = 'black';
					placeOrderButton.style.backgroundColor = '#ccc';
					placeOrderButton.disabled = true;
				}
			}
			return () => {
				if ( placeOrderButton ) {
					placeOrderButton.style.color = '';
					placeOrderButton.style.backgroundColor = '';
					placeOrderButton.disabled = false;
				}
			};
		}, [ activePaymentMethod ] );

		useEffect( () => {
			// Don't initialize Bizum component if using redirect flow
			if ( isRedirectFlow ) {
				return;
			}

			// We assume the MONEI SDK is already loaded via wp_enqueue_script on the backend.
			if (
				typeof monei !== 'undefined' &&
				monei.Bizum &&
				! isInitializedRef.current
			) {
				initMoneiCard();
				isInitializedRef.current = true;
			} else if ( ! monei || ! monei.Bizum ) {
				console.error( 'MONEI SDK is not available' );
			}
		}, [] ); // Only initialize once on mount

		useEffect( () => {
			// Don't update amount if using redirect flow
			if ( isRedirectFlow ) {
				return;
			}

			// Only update amount if instance exists and cart totals changed
			if (
				isInitializedRef.current &&
				currentBizumInstanceRef.current &&
				cartTotals
			) {
				updateBizumAmount();
			}
		}, [ cartTotals ] ); // Update amount when cart totals change

		/**
		 * Initialize MONEI Bizum instance once.
		 */
		const initMoneiCard = () => {
			const currentTotal = cartTotals?.total_price
				? parseInt( cartTotals.total_price )
				: parseInt( bizumData.total * 100 );

			lastAmountRef.current = currentTotal;

			const container = document.getElementById( 'bizum-container' );
			if ( ! container ) {
				console.error( 'Bizum container not found' );
				return;
			}

			// Clear container
			container.innerHTML = '';

			currentBizumInstanceRef.current = monei.Bizum( {
				accountId: bizumData.accountId,
				sessionId: bizumData.sessionId,
				language: bizumData.language,
				amount: currentTotal,
				currency: bizumData.currency,
				style: bizumData.bizumStyle || {},
				onSubmit( result ) {
					if ( result.token ) {
						requestTokenRef.current = result.token;
						const placeOrderButton = document.querySelector(
							'.wc-block-components-button.wp-element-button.wc-block-components-checkout-place-order-button.wc-block-components-checkout-place-order-button'
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
					console.error( 'Bizum error:', error );
				},
			} );

			currentBizumInstanceRef.current.render( container );
		};

		/**
		 * Update the amount in the existing Bizum instance.
		 */
		const updateBizumAmount = () => {
			const currentTotal = cartTotals?.total_price
				? parseInt( cartTotals.total_price )
				: parseInt( bizumData.total * 100 );

			// Only update if amount actually changed
			if ( currentTotal === lastAmountRef.current ) {
				return;
			}

			lastAmountRef.current = currentTotal;

			if ( currentBizumInstanceRef.current ) {
				const preservedToken = requestTokenRef.current;

				if (
					typeof currentBizumInstanceRef.current.destroy ===
					'function'
				) {
					currentBizumInstanceRef.current.destroy();
				}

				// Clear container
				const container = document.getElementById( 'bizum-container' );
				if ( container ) {
					container.innerHTML = '';
				}

				// Recreate with new amount
				currentBizumInstanceRef.current = monei.Bizum( {
					accountId: bizumData.accountId,
					sessionId: bizumData.sessionId,
					language: bizumData.language,
					amount: currentTotal,
					currency: bizumData.currency,
					onSubmit( result ) {
						if ( result.token ) {
							requestTokenRef.current = result.token;
							const placeOrderButton = document.querySelector(
								'.wc-block-components-button.wp-element-button.wc-block-components-checkout-place-order-button.wc-block-components-checkout-place-order-button'
							);
							if ( placeOrderButton ) {
								placeOrderButton.style.color = '';
								placeOrderButton.style.backgroundColor = '';
								placeOrderButton.disabled = false;
								placeOrderButton.click();
							} else {
								console.error(
									'Place Order button not found.'
								);
							}
						}
					},
					onError( error ) {
						console.error( 'Bizum error:', error );
					},
				} );

				currentBizumInstanceRef.current.render( container );
			}
		};

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
		}, [ onPaymentSetup ] );

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
		}, [ onCheckoutSuccess ] );

		// Cleanup on unmount
		useEffect( () => {
			return () => {
				if ( currentBizumInstanceRef.current ) {
					if (
						typeof currentBizumInstanceRef.current.destroy ===
						'function'
					) {
						currentBizumInstanceRef.current.destroy();
					}
					currentBizumInstanceRef.current = null;
				}
			};
		}, [] );

		// In redirect mode, show description instead of Bizum button
		if ( isRedirectFlow ) {
			return (
				<div className="monei-redirect-description">
					{ bizumData.description }
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
					id="bizum-container"
					className="monei-payment-request-container"
				>
					{ /* Bizum button will be inserted here */ }
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

	const bizumLabel = () => {
		return (
			<div className="monei-label-container">
				{ bizumData.title && (
					<span className="monei-text">
						{ __( bizumData.title, 'monei' ) }
					</span>
				) }
				{ bizumData?.logo && (
					<div className="monei-logo">
						<img src={ bizumData.logo } alt="" />
					</div>
				) }
			</div>
		);
	};

	const MoneiBizumPaymentMethod = {
		name: 'monei_bizum',
		label: bizumLabel(),
		ariaLabel: __( bizumData.title, 'monei' ),
		content: <MoneiBizumContent />,
		edit: <div> { __( bizumData.title, 'monei' ) }</div>,
		canMakePayment: ( { billingData } ) => {
			return (
				billingData.country === 'ES' && ! bizumData.cartHasSubscription
			);
		},
		supports: bizumData.supports,
	};
	registerPaymentMethod( MoneiBizumPaymentMethod );
} )();
