( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const { useEffect} = wp.element;
	const paypalData = wc.wcSettings.getSetting( 'monei_paypal_data' );

	const MoneiPayPalContent = ( props ) => {
		let counter = 0;
		const { responseTypes } = props.emitResponse;
		const { onPaymentSetup, onCheckoutSuccess } = props.eventRegistration;
		const { activePaymentMethod } = props;
		let requestToken = null;
		let paypalInstance = null;
		let paypalContainer = null;
		useEffect( () => {
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
					paypalContainer.innerHtml = ''
				}
				if ( placeOrderButton ) {
					placeOrderButton.style.color = '';
					placeOrderButton.style.backgroundColor = '';
					placeOrderButton.disabled = false;
				}

			};
		}, [ activePaymentMethod ] );
		useEffect( () => {
			// We assume the MONEI SDK is already loaded via wp_enqueue_script on the backend.
			if ( typeof monei !== 'undefined' && monei.PayPal ) {
				if(counter === 0) {
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
				onSubmit( result ) {
					if ( result.token ) {
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
					console.error( error );
				},
			} );
			paypalInstance.render( paypalContainer );

			counter += 1
		};

		// Hook into the payment setup
		useEffect( () => {
			const unsubscribePaymentSetup = onPaymentSetup( () => {
				// If no token was created, fail
				if ( ! requestToken ) {
					return {
						type: 'error',
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
			const unsubscribeSuccess = onCheckoutSuccess(
				( { processingResponse } ) => {
					const { paymentDetails } = processingResponse;
					if ( paymentDetails && paymentDetails.paymentId ) {
						const paymentId = paymentDetails.paymentId;
						const tokenValue = paymentDetails.token;
						monei.confirmPayment( {
							paymentId,
							paymentToken: tokenValue} )
							.then( ( result ) => {
								if (
									result.nextAction &&
									result.nextAction.mustRedirect
								) {
									window.location.assign(
										result.nextAction.redirectUrl
									);
								}
								if ( result.status === 'FAILED' ) {
									window.location.href = `${ paymentDetails.failUrl }&status=FAILED`;
								} else {
									window.location.href =
										paymentDetails.completeUrl;
								}
							} )
							.catch( ( error ) => {
								console.error(
									'Error during payment confirmation:',
									error
								);
								window.location.href = paymentDetails.failUrl;
							} );
					} else {
						console.error( 'No paymentId found in paymentDetails' );
					}
					// Return true to indicate that the checkout is successful
					return true;
				}
			);

			return () => {
				unsubscribeSuccess();
			};
		}, [ onCheckoutSuccess ] );
		return (
			<fieldset className="monei-fieldset monei-payment-request-fieldset">
				<div id="paypal-container">
				</div>
			</fieldset>
		);
	};
	const paypalLabel = () => {
		return (
			<div className="monei-label-container">
				<span className="monei-text">
					{ __( paypalData.title, 'monei' ) }
				</span>
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
		label: <div> { paypalLabel() } </div>,
		ariaLabel: __( paypalData.title, 'monei' ),
		content: <MoneiPayPalContent />,
		edit: <div> { __( paypalData.title, 'monei' ) }</div>,
		canMakePayment: ( ) => true,
		supports: paypalData.supports,
	};

	registerPaymentMethod( MoneiPayPalPaymentMethod );
} )();
