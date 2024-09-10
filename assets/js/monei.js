(function( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on(
		'updated_checkout',
		function(e, data) {
			// Update cofidis_widget.total on every updated_checkout event.
			if ( 'object' === typeof( data ) && data.fragments && data.fragments[ 'monei_new_total' ] ) {
				wc_monei_form.total = data.fragments[ 'monei_new_total' ];
			}

			if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
				// We need to re-init payment request with the new price.
				wc_monei_form.init_apple_google_pay();

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

	// On Pay for order form.
	$( 'form#order_review' ).on(
		'click',
		function() {
			if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
			}
		}
	);

	var targetNode = document.getElementById('order_review');

	if (targetNode) {
		var observer = new MutationObserver(function(mutationsList, observer) {
			for (var mutation of mutationsList) {
				if (mutation.type === 'childList') {
					if ( wc_monei_form.is_monei_selected() ) {
						wc_monei_form.init_checkout_monei();
					}
				}
			}
		});

		observer.observe(targetNode, { childList: true, subtree: true });
	}

	var wc_monei_form = {
		$checkout_form: $( 'form.woocommerce-checkout' ),
		$add_payment_form: $( 'form#add_payment_method' ),
		$order_pay_form: $( 'form#order_review' ),
		$cardInput: null,
		$container: null,
		$payment_request_container: '#payment_request_container',
		$errorContainer: null,
		$paymentForm: null,
		is_checkout: false,
		is_add_payment_method: false,
		is_order_pay: false,
		form: null,
		submitted: false,
		init_counter: 0,
		total: wc_monei_params.total,
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

			// Pay for order ( change_payment_method for subscriptions)
			if ( this.$order_pay_form.length ) {
				if ( wc_monei_form.is_monei_selected() ) {
					wc_monei_form.init_checkout_monei();
				}

				this.is_order_pay = true;
				this.form         = this.$order_pay_form;
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
			// Triggers on payment method selection.
			$( "[name='payment_method']" ).on(
				'change',
				function() {
					wc_monei_form.on_payment_selected();
				}
			);
			// Triggers on saved card selection.
			$( "[name='wc-monei-payment-token']" ).on(
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

				// If a tokenised card is checked, we hide google/apple request button.
				if ( wc_monei_form.is_checkout && wc_monei_params.apple_google_pay ) {
					if ( wc_monei_form.is_tokenized_cc_selected() ) {
						wc_monei_form.hide_payment_request_container();
					} else {
						wc_monei_form.show_payment_request_container();
					}
				}
			} else {
				if ( wc_monei_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).removeAttr( 'data-monei' );
				}
			}
		},
		is_monei_selected: function() {
			return $( '#payment_method_monei' ).is( ':checked' );
		},
		is_tokenized_cc_selected: function() {
			return ( $( 'input[name="wc-monei-payment-token"]' ).is( ':checked' ) && 'new' !== $( 'input[name="wc-monei-payment-token"]:checked' ).val() );
		},
		is_monei_saved_cc_selected: function() {
			return ( wc_monei_form.is_monei_selected() && wc_monei_form.is_tokenized_cc_selected() );
		},
		init_apple_google_pay: function() {
			if ( ! wc_monei_params.apple_google_pay ) {
				return;
			}
			if ( window.paymentRequest ) {
				window.paymentRequest.close();
			}
			wc_monei_form.instantiate_payment_request();
		},
		instantiate_payment_request: function() {
			// Create an instance of the Apple/Google Pay component.
			var paymentRequest = monei.PaymentRequest({
				accountId: wc_monei_params.account_id,
				sessionId: wc_monei_params.session_id,
				amount: parseInt( wc_monei_form.total ),
				currency: wc_monei_params.currency,
				onSubmit(result) {
					wc_monei_form.apple_google_token_handler( result.token );
				},
				onError(error) {
					console.log(error);
				},
			});
			// Render an instance of the Payment Request Component into the `payment_request_container` <div>.
			paymentRequest.render('#payment-request-container');
			// Assign a global variable to paymentRequest so it's accessible.
			window.paymentRequest = paymentRequest;
		},
		hide_payment_request_container: function() {
			$('#payment-request-container').hide();
		},
		show_payment_request_container: function() {
			$('#payment-request-container').show();
		},
		init_checkout_monei: function() {
			// If checkout is updated (and monei was initiated already), ex, selecting new shipping methods, checkout is re-render by the ajax call.
			// and we need to reset the counter in order to initiate again the monei component.
			if ( wc_monei_form.$container && 0 === wc_monei_form.$container.childElementCount ) {
				wc_monei_form.init_counter = 0;
			}

			// init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_counter ) {
				return;
			}

			// We don't want to initialise when a saved cc is selected, since form is not visible.
			if ( wc_monei_form.is_monei_saved_cc_selected() ) {
				return;
			}

			if ( wc_monei_form.is_checkout ) {
				$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );
			}

			// Init Apple/Google Pay.
			wc_monei_form.init_apple_google_pay();

			wc_monei_form.$container      = document.getElementById( 'card-input' );
			wc_monei_form.$errorContainer = document.getElementById( 'monei-card-error' );

			var style = {
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
					},
					onEnter: function () {
						wc_monei_form.form.submit();
					},
					onFocus: function () {
						wc_monei_form.$container.classList.add( 'is-focused' );
					},
					onBlur: function () {
						wc_monei_form.$container.classList.remove( 'is-focused' );
					},
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
			// If MONEI payment request token already created (apple/google), submit form.
			if ( $( '#monei_payment_request_token' ).length ) {
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
			monei.createToken( wc_monei_form.$cardInput )
				.then(
					function ( result ) {
						if ( result.error ) {
							console.log('error', result.error);
							// Inform the user if there was an error.
							wc_monei_form.print_errors( result.error );
						} else {
							// Create monei token and append it to Dconsole.logOM
							wc_monei_form.monei_token_handler( result.token );
						}
					}
				)
				.catch(
					function (error) {
						console.log( error );
						wc_monei_form.print_errors( error.message );
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
			wc_monei_form.create_hidden_input( 'monei_payment_token', token );
			// Once Token is created, submit form.
			wc_monei_form.form.submit();
		},
		apple_google_token_handler: function (token ) {
			wc_monei_form.create_hidden_input( 'monei_payment_request_token', token );
			// Once Token is created, submit form.
			wc_monei_form.form.submit();
		},
		create_hidden_input: function( id, token ) {
			console.log( 'token', token );
			var hiddenInput = document.createElement( 'input' );
			hiddenInput.setAttribute( 'type', 'hidden' );
			hiddenInput.setAttribute( 'name', id );
			hiddenInput.setAttribute( 'id', id );
			hiddenInput.setAttribute( 'value', token );
			wc_monei_form.$paymentForm = document.getElementById( 'payment-form' );
			wc_monei_form.$paymentForm.appendChild( hiddenInput );
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
