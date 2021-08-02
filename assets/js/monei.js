(function( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on(
		'updated_checkout',
		function() {
			if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
			}
		}
	);

	// Add Payment Method form.
	$( 'form#add_payment_method' ).on(
		'click payment_methods',
		function() {
			if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
			}
		}
	);

	var wc_monei_form = {
		$checkout_form: $( 'form.woocommerce-checkout' ),
		$add_payment_form: $( 'form#add_payment_method' ),
		$cardInput: null,
		$container: null,
		$errorContainer: null,
		$paymentForm: null,
		is_checkout: false,
		is_add_payment_method: false,
		form: null,
		submitted: false,
		init_counter: 0,
		init: function() {
			// Checkout Page
			if ( this.$checkout_form.length ) {
				this.is_checkout = true;
				this.form        = this.$checkout_form;
				this.form.on( 'checkout_place_order', this.place_order );
			}

			// Add payment method Page
			if ( this.$add_payment_form.length ) {
				this.is_add_payment_method = true;
				this.form                  = this.$add_payment_form;
				this.form.on( 'submit', this.place_order );
			}

			if ( this.form ) {
				this.form.on( 'change', this.on_change );
			}
		},
		submit_form: function() {
			wc_monei_form.form.submit();
		},
		on_change: function() {
			$( "[name='payment_method']" ).on(
				'change',
				function() {
					wc_monei_form.on_payment_selected();
				}
			);
		},
		on_payment_selected() {
			if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
				if ( wc_monei_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );
				}
			} else {
				if ( wc_monei_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).removeAttr( 'data-monei' );
				}
			}
		},
		is_monei_selected: function() {
			return $( '#payment_method_monei_card_input_component' ).is( ':checked' );
		},
		is_tokenized_cc_selected: function() {
			return ( $( 'input[name="wc-monei_card_input_component-payment-token"]' ).is( ':checked' ) && 'new' !== $( 'input[name="wc-monei_card_input_component-payment-token"]:checked' ).val() );
		},
		is_monei_saved_cc_selected: function() {
			return ( wc_monei_form.is_monei_selected() && wc_monei_form.is_tokenized_cc_selected() );
		},
		init_checkout_monei: function() {
			// init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_counter ) {
				return;
			}

			if ( wc_monei_form.is_checkout ) {
				$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );
			}

			wc_monei_form.$container = document.getElementById( 'card-input' );
			wc_monei_form.$errorContainer = document.getElementById( 'monei-card-error' );

			var style = {
				base: {
				},
				input: {
					color: "#31325F",
					fontSmoothing: "antialiased",
					fontSize: "16px",
					"::placeholder": {
						color: "#848589"
					},
					"-webkit-autofill": {
						backgroundColor: "#FAFFBD"
					}
				},
				invalid: {
					color: "#fa755a"
				}
			};

			wc_monei_form.$cardInput = monei.CardInput(
				{
					accountId: wc_monei_params.account_id,
					sessionId: wc_monei_params.session_id,
					style: style,
					onChange: function (event) {
						// Handle real-time validation errors.
						if (event.isTouched && event.error) {
							wc_monei_form.print_errors( event.error );
						} else {
							wc_monei_form.clear_errors();
						}
					}
				}
			);
			wc_monei_form.$cardInput.render( wc_monei_form.$container );

			// We already init CardInput.
			this.init_counter++;
		},
		place_order: function( e ) {
			// If MONEI token already created, submit form.
			if ( $( '#monei_payment_token' ).length ) {
				return true;
			}
			if ( ! wc_monei_form.is_monei_selected() ) {
				return true;
			}
			// If user has selected any tokenized CC, we just submit the form normally.
			if ( wc_monei_form.is_monei_saved_cc_selected() ) {
				return true;
			}
			e.preventDefault();
			// This will be trigger, when CC component is used and "Place order" has been clicked.
			wc_monei_form.$paymentForm = document.getElementById( 'payment-form' );
			monei.createToken( wc_monei_form.$cardInput )
				.then(
					function ( result ) {
						if ( result.error ) {
							// Inform the user if there was an error.
							wc_monei_form.print_errors( result.error );
						} else {
							// Create monei token and append it to DOM
							wc_monei_form.monei_token_handler( result.token );
						}
					}
				)
				.catch(
					function (error) {
						console.log( error );
						wc_monei_form.print_errors( error );
					}
				);
			return false;
		},
		/**
		 * Printing errors into checkout form.
		 * @param error_string
		 */
		print_errors: function (error_string ) {
			$( wc_monei_form.$errorContainer ).html( '<br /><ul class="woocommerce_error woocommerce-error monei-error"><li /></ul>' );
			$( wc_monei_form.$errorContainer ).find( 'li' ).text( error_string );
			/**
			 * Scroll to Monei Errors.
			 */
			if ( $( '.monei-error' ).length ) {
				$( 'html, body' ).animate(
					{
						scrollTop: ( $( '.monei-error' ).offset().top - 200 )
					},
					200
				);
			}
		},
		/**
		 * Clearing form errors.
		 */
		clear_errors: function() {
			$( '.monei-error' ).remove();
		},
		monei_token_handler: function( token ) {
			console.log( 'token', token );
			var hiddenInput = document.createElement( 'input' );
			hiddenInput.setAttribute( 'type', 'hidden' );
			hiddenInput.setAttribute( 'name', 'monei_payment_token' );
			hiddenInput.setAttribute( 'id', 'monei_payment_token' );
			hiddenInput.setAttribute( 'value', token );
			wc_monei_form.$paymentForm.appendChild( hiddenInput );

			// Once Token is created, submit form.
			wc_monei_form.form.submit();
		},
		get_form: function() {
			return this.form;
		}
	};

	$(
		function() {
			wc_monei_form.init();
		}
	);

})( jQuery );
