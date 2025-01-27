( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const { useEffect } = wp.element;
	const moneiData = wc.wcSettings.getSetting( 'monei_data' );

	const MoneiContent = ( props ) => {
		const { responseTypes } = props.emitResponse;
		const isHostedWorkflow = moneiData.redirect === 'yes';
		const { onPaymentSetup, onCheckoutValidation, onCheckoutSuccess } =
			props.eventRegistration;
		let cardInputError = true;
		let cardInput = null;
		let token = null;
		const cardholderNameRegex = /^[A-Za-zÀ-ú- ]{5,50}$/;
		const shouldSavePayment = props.shouldSavePayment;
		/**
		 * Printing errors into checkout form.
		 * @param error_string
		 * @param error_container_id
		 */
		const print_errors = ( error_string, error_container_id ) => {
			cardInput = document.getElementById( error_container_id );
			cardInput.innerHTML = error_string;
		};
		/**
		 * Clearing form errors.
		 * @param id
		 */
		const clear_errors = ( id ) => {
			document.getElementById( id ).innerHTML = '';
		};
		if ( isHostedWorkflow ) {
			return (
				<div className="wc-block-components-text-input wc-block-components-address-form__email">
					<p>
						{moneiData.redirected}
					</p>
				</div>
			);
		}

		const validateCardholderName = () => {
			const errorContainerId = 'monei-cardholder-name-error';
			const cardholderName =
				document.querySelector( '#cardholder_name' ).value;
			if ( ! cardholderNameRegex.test( cardholderName ) ) {
				print_errors( moneiData.nameErrorString, errorContainerId);
				return false;
			}
			clear_errors( errorContainerId );
			return true;
		};

		useEffect( () => {
			// Attach the blur event for cardholder name validation
			const cardholderNameInput =
				document.querySelector( '#cardholder_name' );

			if ( cardholderNameInput ) {
				cardholderNameInput.addEventListener(
					'blur',
					validateCardholderName
				);
			}

			// Cleanup event listener on unmount
			return () => {
				if ( cardholderNameInput ) {
					cardholderNameInput.removeEventListener(
						'blur',
						validateCardholderName
					);
				}
			};
		}, [] );

		useEffect( () => {
			// We assume the MONEI SDK is already loaded via wp_enqueue_script on the backend.
			if ( typeof monei !== 'undefined' && monei.CardInput ) {
				initMoneiCard();
			} else {
				console.error( 'MONEI SDK is not available' );
			}
		}, [] ); // Empty dependency array ensures this runs only once when the component mounts.
		/**
		 * Initialize MONEI card input and handle token creation.
		 */
		const initMoneiCard = () => {
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
			const container = document.getElementById( 'monei-card-input' );
			cardInput = monei.CardInput( {
				accountId: moneiData.accountId,
				sessionId: moneiData.sessionId,
				language: moneiData.language,
				style,
				onFocus() {
					container.classList.add( 'is-focused' );
				},
				onBlur() {
					container.classList.remove( 'is-focused' );
				},
				onChange( event ) {
					// Handle real-time validation errors.
					if ( event.isTouched && event.error ) {
						container.classList.add( 'is-invalid' );
						print_errors( event.error, 'monei-card-error' );
						cardInputError = true;
					} else {
						container.classList.remove( 'is-invalid' );
						clear_errors( 'monei-card-error' );
						if ( event.isTouched ) {
							cardInputError = false;
						}
					}
				},
				onEnter() {
					// Handle form submission when card details are entered
					createMoneiToken();
				},
			} );
			cardInput.render( container );
		};

		/**
		 * Handle MONEI token creation when form is submitted.
		 */
		const createMoneiToken = () => {
			// Create a token using the MONEI SDK
			return monei
				.createToken( cardInput )
				.then( ( result ) => {
					if ( result.error ) {
						print_errors( result.error, 'monei-card-error' );
						return null;
					}
					document.querySelector( '#monei_payment_token' ).value =
						result.token;
					token = result.token;
					return result.token;
				} )
				.catch( ( error ) => {
					print_errors( error.message, 'monei-card-error' );
					return null;
				} );
		};

		// Hook into the validation process
		useEffect( () => {
			const unsubscribeValidation = onCheckoutValidation( () => {
				if ( ! validateCardholderName() ) {
					return {
						errorMessage: moneiData.nameErrorString,
					};
				}

				if ( cardInputError !== false ) {
					return {
						errorMessage: moneiData.cardErrorString,
					};
				}

				// If no token is available, create a fresh token or fail validation, the card input will show its errors
				if ( ! token ) {
					return createMoneiToken().then( ( freshToken ) => {
						return freshToken;
						 // Validation passed
					} );
				}
				return true; // Validation passed (token already exists)
			} );

			return () => {
				unsubscribeValidation();
			};
		}, [ onCheckoutValidation, cardInputError ] );

		// Hook into the payment setup
		useEffect( () => {
			const unsubscribePaymentSetup = onPaymentSetup( () => {
				// Get the token from the hidden input field
				const cardholderName =
					document.querySelector( '#cardholder_name' ).value;
				// If no token is available, create a fresh token
				if ( ! token ) {
					return createMoneiToken().then( ( freshToken ) => {
						// If the token is generated successfully
						if ( freshToken && freshToken.length ) {
							return {
								type: responseTypes.SUCCESS,
								meta: {
									paymentMethodData: {
										monei_payment_token: freshToken,
										monei_cardholder_name: cardholderName,
										monei_is_block_checkout: 'yes',
									},
								},
							};
						}

						// If the token generation failed
						return {
							type: 'error',
							message: moneiData.tokenErrorString,
						};
					} );
				}
				// Token is already available, proceed with setup
				return {
					type: responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							monei_payment_token: token,
							monei_cardholder_name: cardholderName,
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
					// Ensure we have the paymentId from the server
					if ( paymentDetails && paymentDetails.paymentId ) {
						const paymentId = paymentDetails.paymentId;

						const tokenValue = paymentDetails.token;
						// Call monei.confirmPayment to complete the payment (with 3D Secure)
						monei
							.confirmPayment( {
								paymentId,
								paymentToken: tokenValue,
								paymentMethod: {
									card: {
										cardholderName:
											document.querySelector(
												'#cardholder_name'
											).value,
									},
								},
							} )
							.then( ( result ) => {
								if ( result.status === 'FAILED' ) {
									window.location.href = `${ paymentDetails.failUrl }&status=FAILED`;
								} else {
									let redirectUrl =
										paymentDetails.completeUrl;
									const orderId = paymentDetails.orderId;
									if ( shouldSavePayment === true ) {
										redirectUrl = `${ paymentDetails.completeUrl }&id=${ paymentId }&orderId=${ orderId }`;
									}
									window.location.href = redirectUrl;
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
		}, [ onCheckoutSuccess, shouldSavePayment ] );

		return (
			<fieldset className="monei-fieldset monei-card-fieldset wc-block-components-form">
				{ moneiData?.description && <p>{ moneiData.description }</p> }
				<div className="monei-input-container  wc-block-components-text-input">
					<input
						type="text"
						id="cardholder_name"
						name="cardholder_name"
						placeholder={ moneiData.cardholderName }
						required
						className="monei-input"
					/>
					<div
						id="monei-cardholder-name-error"
						className="wc-block-components-validation-error"
					></div>
				</div>
				<div id="monei-card-input" className="monei-card-input" />
				<input
					type="hidden"
					id="monei_payment_token"
					name="monei_payment_token"
					value=""
				/>
				<div
					id="monei-card-error"
					className="wc-block-components-validation-error"
				/>
			</fieldset>
		);
	};
	const MoneiAppleGoogleContent = ( props ) => {
		const { responseTypes } = props.emitResponse;
		const { onPaymentSetup } =
			props.eventRegistration;
		const { activePaymentMethod } = props;

		let requestToken = null;
		useEffect( () => {
			const placeOrderButton = document.querySelector(
				'.wc-block-components-button.wp-element-button.wc-block-components-checkout-place-order-button.wc-block-components-checkout-place-order-button'
			);
			if ( activePaymentMethod === 'monei_apple_google' ) {
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
			if ( typeof monei !== 'undefined' && monei.PaymentRequest ) {
				initMoneiCard();
			} else {
				console.error( 'MONEI SDK is not available' );
			}
		}, [] ); // Empty dependency array ensures this runs only once when the component mounts.

		/**
		 * Initialize MONEI card input and handle token creation.
		 */
		const initMoneiCard = () => {
			if ( window.paymentRequest ) {
				window.paymentRequest.close();
			}
			const paymentRequest = monei.PaymentRequest( {
				accountId: moneiData.accountId,
				sessionId: moneiData.sessionId,
				language: moneiData.language,
				amount: parseInt( moneiData.total * 100 ),
				currency: moneiData.currency,
				onSubmit( result ) {
					if ( result.token ) {
						requestToken = result.token;
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
					console.error( error );
					console.error( error );
				},
			} );

			const container = document.getElementById(
				'payment-request-container'
			);
			paymentRequest.render( container );
		};

		// Hook into the payment setup
		useEffect( () => {
			const unsubscribePaymentSetup = onPaymentSetup( () => {
				// If no token was created, fail
				if ( ! requestToken ) {
					return {
						type: 'error',
						message: moneiData.tokenErrorString
					};
				}
				return {
					type: responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							monei_payment_request_token: requestToken,
						},
					},
				};
			} );

			return () => {
				unsubscribePaymentSetup();
			};
		}, [ onPaymentSetup ] );

		return (
			<fieldset className="monei-fieldset monei-payment-request-fieldset">
				<div
					id="payment-request-container"
					className="monei-payment-request-container"
				>
					{ /* Google Pay button will be inserted here */ }
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

	const ccLabel = () => {
		return (
			<div className="monei-label-container">
				<span className="monei-text">
					{ moneiData.title }
				</span>
				{ moneiData?.logo && (
					<div className="monei-logo">
						<img src={ moneiData.logo } alt="" />
					</div>
				) }
			</div>
		);
	};

	const appleGoogleLabel = () => {
		const isApple = window.ApplePaySession?.canMakePayments();
		const logo = isApple ? moneiData.logo_apple : moneiData.logo_google;
		const title = isApple
			?  'Apple Pay'
			: 'Google Pay';
		const shouldShowLogo =
			( isApple && moneiData?.logo_apple ) ||
			( ! isApple && moneiData?.logo_google );
		return (
			<div className="monei-label-container">
				<span className="monei-text">{ title }</span>
				{ shouldShowLogo && (
					<div className="monei-logo">
						<img src={ logo } alt="" />
					</div>
				) }
			</div>
		);
	};

	/**
	 * MONEI Payment Method React Component
	 */
	const MoneiPaymentMethod = {
		name: 'monei',
		label: <div> { ccLabel() } </div>,
		ariaLabel: __( 'MONEI Payment Gateway', 'monei' ),

		// React content to render on the checkout page
		content: <MoneiContent />,

		// Optional edit mode for the block editor
		edit: <div>{ __( 'MONEI Payment Form (Edit Mode)', 'monei' ) }</div>,

		canMakePayment: () => true,
		supports: wc.wcSettings.getSetting( 'monei_data' ).supports,
	};
	const AppleGooglePaymentMethod = {
		name: 'monei_apple_google',
		paymentMethodId: 'monei',
		label: <div> { appleGoogleLabel() } </div>,
		ariaLabel: __( 'Apple/Google Pay Payment Gateway', 'monei' ),
		content: <MoneiAppleGoogleContent />,
		edit: <div>{ __( 'MONEI Payment Form (Edit Mode)', 'monei' ) }</div>,
		canMakePayment: () => true,
		supports: { features: [ 'products' ] },
	};
	registerPaymentMethod( MoneiPaymentMethod );
	registerPaymentMethod( AppleGooglePaymentMethod );
} )();
