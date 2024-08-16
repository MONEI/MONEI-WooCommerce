	/**
	 * Get settings from monei_data.
	 * @param id
	 * @param token
	 */
	const
		getSetting=()=>{
			const settingValue=( 0, window.wc.wcSettings.getSetting )( 'monei_data',null );
			if ( ! settingValue ) throw new Error( 'MONEI initialization data is not available.' );
			return settingValue;
		};

	/**
	 * Our block form.
	 */
	let wc_monei_block_form = {
		$cardInput: null,
		$container: null,
		$errorContainer: null,
		$paymentForm: null,
		form: jQuery( '.wc-block-checkout' ),
		init_counter: 0,

		init_checkout_monei: function() {

			// If checkout is updated (and monei was initiated already), ex, selecting new shipping methods, checkout is re-render by the ajax call.
			// and we need to reset the counter in order to initiate again the monei component.

			if ( wc_monei_block_form.$container && 0 === wc_monei_block_form.$container.childElementCount ) {
				wc_monei_block_form.init_counter = 0;
			}

			// Init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_counter ) {
				return;
			}

			wc_monei_block_form.$container      = document.getElementById( 'card-input' );
			wc_monei_block_form.$errorContainer = document.getElementById( 'monei-card-error' );

			let style = {
				input: {
					fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
					fontSmoothing: "antialiased",
					fontSize: "15px",
				},
				invalid: {
					color: "#fa755a"
				},
				icon: {
					marginRight: "0.4em"
				}
			};

			// Build our CardInput ( using `monei` from https://js.monei.com/v1/monei.js )

			wc_monei_block_form.$cardInput = monei.CardInput(
				{
					accountId: getSetting().accountId,
					sessionId: getSetting().sessionId,
					style: style,
					onChange: function (event) {
						// Handle real-time validation errors.
						if (event.isTouched && event.error) {
							wc_monei_block_form.print_errors( event.error );
						} else {
							wc_monei_block_form.clear_errors();
						}
					},
					onEnter: function () {
						jQuery( '.wc-block-components-checkout-place-order-button' ).trigger( 'click' );
					},
					onFocus: function () {
						wc_monei_block_form.$container.classList.add( 'is-focused' );
					},
					onBlur: function () {
						wc_monei_block_form.$container.classList.remove( 'is-focused' );
					},
				}
			);

			wc_monei_block_form.$cardInput.render( wc_monei_block_form.$container );

			// We already init CardInput.
			this.init_counter++;
		},

		create_token: function() {

			// This will be triggered when CC component is used and "Place order" has been clicked.
			if ( jQuery( '#monei_payment_token_created' ).length ) {
				// We already have the token, no need to create again.
				return jQuery('#monei_payment_token_created').val();
			}

			// It is an async call using `monei` from https://js.monei.com/v1/monei.js )
			monei.createToken( wc_monei_block_form.$cardInput )
				.then(
					function ( result ) {
		
					//console.log('async token result ', result );

						if ( result.error ) {

							// Inform the user if there was an error.
							wc_monei_block_form.print_errors( result.error );

						} else {

							// Create MONEI token, append it to DOM and submit.
							wc_monei_block_form.monei_token_handler( result.token );
							//console.log( 'token is created we are ready to submit but should use darn checkout blocks');
							jQuery( '.wc-block-components-checkout-place-order-button' ).trigger( 'click' );
							return result.token;
						}
					}
				)
				.catch(
					function (error) {
						//console.log( error );
						wc_monei_block_form.print_errors( error );
					}
				);
			return false;
		},

		/**
		 * Handle creation of hidden input with token value.
		 * @param id
		 * @param token
		 */
		monei_token_handler: function( token ) {
			wc_monei_block_form.create_hidden_input( 'monei_payment_token_created', token );
		},

		/**
		 * Create hidden input with token.
		 * @param id
		 * @param token
		 */
		create_hidden_input: function( id, token ) {

			let hiddenInput = document.createElement( 'input' );

			hiddenInput.setAttribute( 'type', 'hidden' );
			hiddenInput.setAttribute( 'name', id );
			hiddenInput.setAttribute( 'id', id );
			hiddenInput.setAttribute( 'value', token );

			wc_monei_block_form.$paymentForm = document.getElementById( 'payment-form' );
			wc_monei_block_form.$paymentForm.appendChild( hiddenInput );
		},

		/**
		 * Printing errors into checkout form.
		 * @param error_string
		 */
		print_errors: function (error_string ) {

			jQuery( wc_monei_block_form.$errorContainer ).html( '<br /><ul class="woocommerce_error woocommerce-error monei-error"><li /></ul>' );
			jQuery( wc_monei_block_form.$errorContainer ).find( 'li' ).text( error_string );

			/**
			 * Scroll to Monei Errors.
			 */
			if ( jQuery( '.monei-error' ).length ) {
				jQuery( 'html, body' ).animate(
					{
						scrollTop: ( jQuery( '.monei-error' ).offset().top - 200 )
					},
					200
				);
			}
		},

		/**
		 * Clearing form errors.
		 */
		clear_errors: function() {
			jQuery( '.monei-error' ).remove();
		},
	};

