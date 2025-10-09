( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const { useEffect, useState, createPortal } = wp.element;
	const paypalData = wc.wcSettings.getSetting( 'monei_paypal_data' );

	const MoneiPayPalContent = ( props ) => {
		let counter = 0;
		const { responseTypes } = props.emitResponse;
		const { onPaymentSetup, onCheckoutSuccess } = props.eventRegistration;
		const { activePaymentMethod } = props;
		let requestToken = null;
		let paypalInstance = null;
		let paypalContainer = null;

		// Check if redirect flow is enabled
		const isRedirectFlow = paypalData.redirectFlow === true;

		// State for confirmation overlay and error handling
		const [ isConfirming, setIsConfirming ] = useState( false );
		const [ error, setError ] = useState( '' );

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
				if ( paypalInstance ) {
					paypalInstance.close();
					paypalInstance = null;
					paypalContainer.innerHtml = '';
				}
				if ( placeOrderButton ) {
					placeOrderButton.style.color = '';
					placeOrderButton.style.backgroundColor = '';
					placeOrderButton.disabled = false;
				}
			};
		}, [ activePaymentMethod ] );
		useEffect( () => {
			// Don't initialize PayPal component if using redirect flow
			if ( isRedirectFlow ) {
				return;
			}

			// We assume the MONEI SDK is already loaded via wp_enqueue_script on the backend.
			if ( typeof monei !== 'undefined' && monei.PayPal ) {
				if ( counter === 0 ) {
					initMoneiCard();
				}
			} else {
				console.error( 'MONEI SDK is not available' );
			}
		}, [] ); // Empty dependency array ensures this runs only once when the component mounts.
		/**
		 * Initialize MONEI card input and handle token creation.
		 */
		const initMoneiCard = () => {
			paypalContainer = document.getElementById( 'paypal-container' );

			// Render the PayPal button
			paypalInstance = monei.PayPal( {
				accountId: paypalData.accountId,
				sessionId: paypalData.sessionId,
				language: paypalData.language,
				amount: parseInt( paypalData.total * 100 ),
				currency: paypalData.currency,
				style: paypalData.paypalStyle || {},
				onSubmit( result ) {
					if ( result.token ) {
						setError( '' ); // Clear any previous errors
						requestToken = result.token;
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

			counter += 1;
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
				if ( ! requestToken ) {
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
							monei_payment_request_token: requestToken,
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
					className="monei-payment-request-container"
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
