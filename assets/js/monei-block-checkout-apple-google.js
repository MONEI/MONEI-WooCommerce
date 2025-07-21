( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const { useEffect } = wp.element;
	const moneiData = wc.wcSettings.getSetting( 'monei_apple_google_data' );

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
	const appleGoogleLabel = () => {
		const isApple = window.ApplePaySession?.canMakePayments();
		const appleEnabled = moneiData.logo_apple !== false;
		const googleEnabled = moneiData.logo_google !== false;
		let logo = googleEnabled? moneiData.logo_google : false;
		logo = isApple && appleEnabled ? moneiData.logo_apple : logo;
		const title = isApple && appleEnabled
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
	const AppleGooglePaymentMethod = {
		name: 'monei_apple_google',
		paymentMethodId: 'monei_apple_google',
		label: <div> { appleGoogleLabel() } </div>,
		ariaLabel: __( 'Apple/Google Pay Payment Gateway', 'monei' ),
		content: <MoneiAppleGoogleContent />,
		edit: <div>{ __( 'MONEI Payment Form (Edit Mode)', 'monei' ) }</div>,
		canMakePayment: () => true,
		supports: { features: [ 'products' ] },
	};

	registerPaymentMethod( AppleGooglePaymentMethod );
} )();
