(function( $ ) {
	'use strict';

	// On Checkout form.
	$( document.body ).on(
		'updated_checkout',
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

			if (this.form) {
				this.form.on( 'change', this.on_change );
			}
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
				$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );
			} else {
				$( "[name='woocommerce_checkout_place_order']" ).removeAttr( 'data-monei' );
			}
		},
		is_monei_selected: function() {
			return $( '#payment_method_monei_card_input_component' ).is( ':checked' );
		},
		is_monei_saved_token_selected: function() {
			return ( $( '#payment_method_monei_card_input_component' ).is( ':checked' ) && ( $( 'input[name="wc-monei_card_input_component-new-payment-method"]' ).is( ':checked' ) && 'new' !== $( 'input[name="wc-monei_card_input_component-new-payment-method"]:checked' ).val() ) );
		},
		init_checkout_monei: function() {
			// init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_counter ) {
				return;
			}

			$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );

			wc_monei_form.$container = document.getElementById( 'card-input' );
			wc_monei_form.$errorContainer = document.getElementById( 'monei-card-error' );

			wc_monei_form.$cardInput = monei.CardInput(
				{
					accountId: wc_monei_params.account_id,
					sessionId: wc_monei_params.session_id,
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
			e.preventDefault();
			if ( ! wc_monei_form.is_monei_selected() ) {
				return;
			}

			// If token already created
			if ( $('#monei_payment_token').length ) {
				return;
			}

			wc_monei_form.$paymentForm = document.getElementById( 'payment-form' );
			monei.createToken( wc_monei_form.$cardInput )
				.then(
					function (result) {
						if (result.error) {
							// Inform the user if there was an error.
							wc_monei_form.print_errors( result.error );
						} else {
							// Send the token to your server.
							wc_monei_form.monei_token_handler( result.token );
							wc_monei_form.$checkout_form.submit();
						}
						//paymentButton.disabled = false;
					}
				)
				.catch(
					function (error) {
						// paymentButton.disabled = false;
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
		},
		block_form: function() {
		},
		unblock_form: function() {
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
