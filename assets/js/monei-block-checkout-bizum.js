( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const { useEffect } = wp.element;
	const bizumData = wc.wcSettings.getSetting( 'monei_bizum_data' );

	const MoneiBizumContent = ( props ) => {
		const { responseTypes } = props.emitResponse;
		const { onPaymentSetup, onCheckoutSuccess } = props.eventRegistration;
		const { activePaymentMethod } = props;
		let requestToken = null;
		useEffect( () => {
			const placeOrderButton = document.querySelector(
				'.wc-block-components-button.wp-element-button.wc-block-components-checkout-place-order-button.wc-block-components-checkout-place-order-button--full-width.contained'
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
			// We assume the MONEI SDK is already loaded via wp_enqueue_script on the backend.
			if ( typeof monei !== 'undefined' && monei.Bizum ) {
				initMoneiCard();
			} else {
				console.error( 'MONEI SDK is not available' );
			}
		}, [] ); // Empty dependency array ensures this runs only once when the component mounts.
		/**
		 * Initialize MONEI card input and handle token creation.
		 */
		const initMoneiCard = () => {
			const bizum = monei.Bizum( {
				accountId: bizumData.accountId,
				sessionId: bizumData.sessionId,
				language: bizumData.language,
				amount: parseInt( bizumData.total * 100 ),
				currency: bizumData.currency,
				onSubmit( result ) {
					if ( result.token ) {
						requestToken = result.token;
						const placeOrderButton = document.querySelector(
							'.wc-block-components-button.wp-element-button.wc-block-components-checkout-place-order-button.wc-block-components-checkout-place-order-button--full-width.contained'
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

			const container = document.getElementById( 'bizum-container' );
			bizum.render( container );
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
						console.log(typeof paymentId)
						console.log({
							paymentId,
							paymentToken: tokenValue})
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
				<span className="monei-text">
					{ __( bizumData.title, 'monei' ) }
				</span>
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
		label: <div> { bizumLabel() } </div>,
		ariaLabel: __( bizumData.title, 'monei' ),
		content: <MoneiBizumContent />,
		edit: <div> { __( bizumData.title, 'monei' ) }</div>,
		canMakePayment: ( { billingData } ) => {
			return billingData.country === 'ES';
		},
		supports: bizumData.supports,
	};
	console.log('hola bizum 6')
	registerPaymentMethod( MoneiBizumPaymentMethod );
} )();
