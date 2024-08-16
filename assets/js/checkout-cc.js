(()=>{

	'use strict';

	const 
		
		wpElement        = window.wp.element,
		wpEntities       = window.wp.htmlEntities,
		wpi18n           = window.wp.i18n,
		wcBlocksRegistry = window.wc.wcBlocksRegistry,
		wcBlocksCheckout = window.wc.blocksCheckout,
		wcSettings       = window.wc.wcSettings,

		// Create raw html element.
		htmlToElem = ( html ) => wpElement.RawHTML( {children:html} ),
		
		// Get monei setting.
		getSetting = () => {
			const settingValue = ( 0, wcSettings.getSetting )( 'monei_data', null );
			if (! settingValue ) throw new Error( 'MONEI initialization data is not available.' );
			return settingValue;
		},

		// Our very own monei script that deals with remote token creation at https://js.monei.com/v1/monei.js
		moneiScript = getSetting().scriptUrl,

		// Description text with extra info according to settings.
		getDescription = () => {

			const 
				moneiTestMode = ' === <strong>' + wpi18n.__( 'Test mode enabled', 'monei' ) + '</strong> === ',
				moneiExternal = '<br><strong>' + wpi18n.__( 'You will be redirected for payment.', 'monei' ) + '</strong>',
				moneiTestCard = '<br>' + wpi18n.__('Use 4444444444444422 as CC number, 12/34 as an expiration date and 123 as CVC code.', 'monei' );

			let moneiAdditionalContent = '';

			if ( 'yes' == getSetting().test_mode ) {
				 moneiAdditionalContent = moneiTestMode;
			}
			if ( 'yes' == getSetting().redirect ) {
				moneiAdditionalContent += moneiExternal;
			}

			if ( 'yes' == getSetting().test_mode ) {
				moneiAdditionalContent += moneiTestCard;
			}
			
			return ( getSetting()?.description || wpi18n.__('Pay with credit card.','monei') ) + moneiAdditionalContent;
		},

		// Maybe show logo based on settings.
		maybeShowLogo = () => {
			if ( 'yes' == getSetting().hide_logo ) {
				return '';
			} else {
				return ( 0, wpElement.createElement( 'img', { src: `${getSetting().logo}`, alt: getSetting().title } ));
			}
		},

		// Maybe show offer to save payment info based on settings.
		maybeShowSaveCard = () =>{
			if ( 'no' != getSetting().tokenization ) {
				return htmlToElem('<label> <input name="wc-monei-new-payment-method" type="checkbox" id="a" value="new" checked="checked" > ' + ( 0, wpi18n.__ )( 'Save payment information to my account for future purchases.', 'monei' ) + '</label>');
			}
		},

		// Same HTML code from protected function render_monei_form().
		showMoneiForm = () => {
			return htmlToElem( '<style>#payment-form{padding-bottom:15px}#card-input{border:1px solid transparent;border-radius:4px;background-color:#fff;box-shadow:0 1px 3px 0 #e6ebf1;height:38px;box-sizing:border-box;-webkit-transition:box-shadow 150ms ease;transition:box-shadow 150ms ease;max-width:350px}#card-input.is-focused{box-shadow:0 1px 3px 0 #cfd7df}</style><fieldset id="wc-monei-cc-form" class="wc-credit-card-form wc-payment-form" style="background:0 0"><div id="payment-form"><div class="card-field"><div id="card-input"></div><div id="monei-card-error"></div></div></div></fieldset>' );
		},

		// Hidden field to store monei_payment_token.
		hiddenField = wpEntities => {

			let {
				id:wcBlocksRegistry,
				value:wcSettings = "",
				onChange:getSetting } = wpEntities;
			return ( 0, wpElement.createElement )( wcBlocksCheckout.ValidatedTextInput, { id:wcBlocksRegistry, type:"hidden", value:wcSettings, onChange:getSetting })
		},	

		// The content of our payment gateway block.
		content = wpi18n => {

			let { eventRegistration:wcBlocksRegistry, emitResponse:wcBlocksCheckout } = wpi18n;
	
			const

				{ onPaymentSetup: wcSettings } = wcBlocksRegistry,
				[ tokenValue, setTokenValue, content ] = ( 0, wpElement.useState )( '' );
				
				if ( 'no' == getSetting().redirect ) {

					// Get remote token based on the CC data informed by customer.
					( wpElement.useEffect( ( ) => 
						{
								const validation = wcBlocksRegistry.onCheckoutValidation( ( async ) => {

									const createToken = async () => {
										const moneiToken = await window.wc_monei_block_form.create_token();
										setTokenValue( moneiToken );
									}

									if ( ! jQuery('#monei_payment_token_created').length ) {
										createToken();
									}

								} );

								const unsubscribe = wcBlocksRegistry.onPaymentSetup( ( async ) => {

									if ( ! jQuery('#monei_payment_token_created').length ) {

										// We need to wait for remote token creation.
										//console.log( 'Missing token, stop this and wait for token creation which triggers click' );
										throw new Error('but not an actual error. MONEI: just stop while waiting for remote token creation....');
									}

									const 
										moneiToken = jQuery('#monei_payment_token_created').val(),
										moneiDataIsValid= !! moneiToken.length;

									if ( moneiDataIsValid ) {
										
										return {
											type: wcBlocksCheckout.responseTypes.SUCCESS,
											meta: {
												paymentMethodData: { monei_payment_token: moneiToken },
											},
										};
									}

									return {
										type: wcBlocksCheckout.responseTypes.ERROR,
										message: 'MONEI: There was an error with token creation.',
									};

								} );

								// Unsubscribes when this component is unmounted.
								return () => {
									unsubscribe();
								};

						}, 
						[ wcBlocksCheckout.responseTypes.ERROR, wcBlocksCheckout.responseTypes.SUCCESS, wcBlocksRegistry.onPaymentSetup, tokenValue ] 
					));

				}

		return( 0, wpElement.useEffect)((() => wcSettings((() => {

			return { type:wcBlocksCheckout.responseTypes.SUCCESS, meta:{ paymentMethodData:[content] } } }
			))),
			[ wcBlocksCheckout.responseTypes.SUCCESS, wcSettings ] ),

				( 'yes' == getSetting().redirect ) ? 

					// Form content for redirection to external payment page.

					( 0, wpElement.createElement )
						( wpElement.Fragment, null,
							( 0, wpElement.createElement( wpElement.RawHTML, null, getDescription() )),
							( 0, wpElement.createElement )( maybeShowSaveCard ),
						)

				:
					// Form content for inline card input.

					( 0, wpElement.createElement )
						( wpElement.Fragment, null,
							( 0, wpElement.createElement( wpElement.RawHTML, null, getDescription() )),
							( 0, wpElement.createElement )( showMoneiForm ),
							( 0, wpElement.createElement )( hiddenField, { id:'monei_payment_token', value:tokenValue, onChange: content }),
							( 0, wpElement.createElement )( maybeShowSaveCard ),
							wpElement.useEffect(
								() => { 

									// Load our credit card script.
									const script = document.createElement("script");
	
									script.src = moneiScript;
									script.async = true;
									script.onload = () => { 
										window.wc_monei_block_form.init_checkout_monei();
									}

									document.body.appendChild(script);

								}, [] )  // <-- empty array means 'run once'
						)
		},

		// Label for our payment method. Can be either image or text, depending on settings.
		labelContent = wpEntities => {
			const 
				{PaymentMethodLabel:wcBlocksRegistry}=wpEntities.components,
				name = getSetting().title?getSetting().title:( 0, wpi18n.__ )( 'MONEI','monei' );

			return ( wpElement.Fragment, null, ( 0, wpElement.createElement )( wcBlocksRegistry, {text:name}), ( 0, wpElement.createElement )( maybeShowLogo ) )
		};


	let supportedFeatures;

	// Register our payment method block.

	( 0, wcBlocksRegistry.registerPaymentMethod )( {
			name:           'monei',
			label:          ( 0, wpElement.createElement )( labelContent ,null),
			ariaLabel:      ( 0, wpi18n.__)( 'MONEI Payment Gateway','monei' ),
			canMakePayment: () => true,
			content:        ( 0, wpElement.createElement )( content, null ),
			edit:           ( 0, wpElement.createElement )( content, null ),
			supports:       { features:null!==(supportedFeatures=getSetting()?.supports)&&void 0!==supportedFeatures?supportedFeatures:[]}
		} )
})();
