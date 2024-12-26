(function( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on(
		'updated_checkout',
		function(e, data) {
			wc_monei_form.update_apple_google_label();
			// Update cofidis_widget.total on every updated_checkout event.
			if ( 'object' === typeof( data ) && data.fragments && data.fragments[ 'monei_new_total' ] ) {
				wc_monei_form.total = data.fragments[ 'monei_new_total' ];
			}

			if (wc_monei_form.is_apple_selected()) {
				wc_monei_form.init_apple_google_pay();
			}

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

	// On Pay for order form.
	$( 'form#order_review' ).on(
		'click',
		function() {
			if (wc_monei_form.is_apple_selected()) {
				wc_monei_form.init_apple_google_pay();
			}
			if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
			}
		}
	);

	var wc_monei_form = {
		$checkout_form: $( 'form.woocommerce-checkout' ),
		$add_payment_form: $( 'form#add_payment_method' ),
		$order_pay_form: $( 'form#order_review' ),
		$cardInput: null,
		$container: null,
		$payment_request_container: null,
		$errorContainer: null,
		$paymentForm: null,
		is_checkout: false,
		is_add_payment_method: false,
		is_order_pay: false,
		form: null,
		submitted: false,
		init_counter: 0,
		init_apple_counter: 0,
		total: wc_monei_params.total,
		cardholderNameRegex: /^[A-Za-zÀ-ú- ]{5,50}$/,
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
					wc_monei_form.on_payment_selected()
				}
				if(wc_monei_form.is_apple_selected()) {
					wc_monei_form.init_apple_google_pay()
				}

				this.is_order_pay = true;
				this.form         = this.$order_pay_form;
				this.form.on( 'submit', this.place_order_page );

				$('input[name="payment_method"]').on('change', function() {
					console.log('radio changed')
					// Check if the apple google pay method is selected
					if (wc_monei_form.is_apple_selected()) {
						wc_monei_form.init_apple_google_pay();
					}
					// Check if the monei method is selected
					if (wc_monei_form.is_monei_selected()) {
						wc_monei_form.init_checkout_monei();
					}
				});
			}

			if ( this.form ) {
				this.form.on( 'change', this.on_change );
			}
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
			if ( wc_monei_form.is_apple_selected()) {
				wc_monei_form.init_apple_google_pay();
				if ( wc_monei_form.is_checkout ) {
					$("[name='woocommerce_checkout_place_order']").attr('data-monei', 'submit');
				}
				$('#place_order').prop('disabled', true);
				return false;
			} else if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
				$('#place_order').prop('disabled', false);
				if ( wc_monei_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );
				}
				if ( wc_monei_form.is_tokenized_cc_selected() ) {
					$('.monei-input-container, .monei-card-input').hide();
				} else {
					$('.monei-input-container, .monei-card-input').show();
				}
			} else {
				if ( wc_monei_form.is_checkout ) {
					$('#place_order').prop('disabled', false);
					$( "[name='woocommerce_checkout_place_order']" ).removeAttr( 'data-monei' );
				}
			}
		},
		validate_cardholder_name: function() {
			var value = $('#monei_cardholder_name').val();
			if (!wc_monei_form.cardholderNameRegex.test(value)) {
				const errorString = wc_monei_params.nameErrorString
				// Show error
				wc_monei_form.print_errors(errorString, '#monei-cardholder-name-error');
				return false;
			} else {
				// Clear error
				wc_monei_form.clear_errors('#monei-cardholder-name-error');
				return true;
			}
		},
		is_monei_selected: function() {
			return $( '#payment_method_monei' ).is( ':checked' );
		},
		is_apple_selected: function() {
			return $( '#payment_method_monei_apple_google' ).is( ':checked' );
		},
		is_tokenized_cc_selected: function() {
			return ( $( 'input[name="wc-monei-payment-token"]' ).is( ':checked' ) && 'new' !== $( 'input[name="wc-monei-payment-token"]:checked' ).val() );
		},
		is_monei_saved_cc_selected: function() {
			return ( wc_monei_form.is_monei_selected() && wc_monei_form.is_tokenized_cc_selected() );
		},
		init_apple_google_pay: function() {
			// If checkout is updated (and monei was initiated already), ex, selecting new shipping methods, checkout is re-render by the ajax call.
			// and we need to reset the counter in order to initiate again the monei component.
			if ( wc_monei_form.$payment_request_container && 0 === wc_monei_form.$payment_request_container.childElementCount ) {
				wc_monei_form.init_apple_counter = 0;
			}

			// init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_apple_counter ) {
				return;
			}

			if ( wc_monei_form.is_checkout ) {
				$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );
			}

			// Init Apple/Google Pay.
			if ( ! wc_monei_params.apple_google_pay ) {
				return;
			}

			wc_monei_form.instantiate_payment_request();
			wc_monei_form.$payment_request_container = document.getElementById('payment-request-container')

			// We already init the button.
			this.init_apple_counter++;

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
					console.error(error);
				},
			});
			// Render an instance of the Payment Request Component into the `payment_request_container` <div>.
			console.log('rendering')
			paymentRequest.render('#payment-request-container');
			// Assign a global variable to paymentRequest so it's accessible.
			window.paymentRequest = paymentRequest;
		},
		init_checkout_monei: function() {
			let container = document.getElementById('monei-card-input')
			if(container === null) {
				return;
			}
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

			$('#monei_cardholder_name').on('blur', function() {
				wc_monei_form.validate_cardholder_name();
			});

			wc_monei_form.$container      = document.getElementById( 'monei-card-input' );
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
			const token = document.getElementById('monei_payment_token')
			if(token) {
				return true;
			}
			if ( wc_monei_form.is_monei_selected() && ! wc_monei_form.is_monei_saved_cc_selected()) {
				if (!wc_monei_form.validate_cardholder_name()) {
					return false;
				}
				//e.preventDefault();
				// This will be trigger, when CC component is used and "Place order" has been clicked.
				monei.createToken( wc_monei_form.$cardInput )
					.then(
						function ( result ) {
							if ( result.error ) {
								console.error('error', result.error);
								// Inform the user if there was an error.
								wc_monei_form.print_errors( result.error );
							} else {
								console.log('token')
								// Create monei token and append it to Dconsole.logOM
								wc_monei_form.monei_token_handler( result.token );
							}
						}
					)
					.catch(
						function (error) {
							console.error( error );
							wc_monei_form.print_errors( error.message );
						}
					);
				return false;
			}
		},
		place_order_page: function( e ) {
			const token = document.getElementById('monei_payment_token')
			if(token) {
				return true;
			}
			if ( wc_monei_form.is_monei_selected() && ! wc_monei_form.is_monei_saved_cc_selected()) {
				if (!wc_monei_form.validate_cardholder_name()) {
					return false;
				}
				e.preventDefault();
				// This will be trigger, when CC component is used and "Place order" has been clicked.
				monei.createToken( wc_monei_form.$cardInput )
					.then(
						function ( result ) {
							if ( result.error ) {
								console.error('error', result.error);
								// Inform the user if there was an error.
								wc_monei_form.print_errors( result.error );
							} else {
								console.log('token', result.token)
								// Create monei token and append it to Dconsole.logOM
								wc_monei_form.monei_token_handler( result.token );
							}
						}
					)
					.catch(
						function (error) {
							console.error( error );
							wc_monei_form.print_errors( error.message );
						}
					);
				return false;
			}
		},
		/**
		 * Printing errors into checkout form.
		 * @param error_string
		 * @param errorContainer
		 */
		print_errors: function(error_string, errorContainer) {
			if (!errorContainer) {
				errorContainer = wc_monei_form.$errorContainer;
			}
			$(errorContainer).html('<br /><ul class="woocommerce_error woocommerce-error monei-error"><li /></ul>');
			$(errorContainer).find('li').text(error_string);
			// Scroll to error
			if ($(errorContainer).find('.monei-error').length) {
				$('html, body').animate({
					scrollTop: ($(errorContainer).offset().top - 200)
				}, 200);
			}
		},
		/**
		 * Clearing form errors.
		 */
		clear_errors: function(errorContainer) {
			if (!errorContainer) {
				errorContainer = wc_monei_form.$errorContainer;
			}
			// Clear all content from the error container
			$(errorContainer).html('');
		},
		monei_token_handler: function( token ) {
			wc_monei_form.create_hidden_input( 'monei_payment_token', 'payment-form' , token );
			// Once Token is created, submit form.
			wc_monei_form.form.submit();
		},
		apple_google_token_handler: function (token ) {
			$('#place_order').prop('disabled', false);
			wc_monei_form.create_hidden_input( 'monei_payment_request_token', 'payment-request-form', token );
			// Once Token is created, submit form.
			wc_monei_form.form.submit();
		},
		create_hidden_input: function( id, form,  token ) {
			var hiddenInput = document.createElement( 'input' );
			hiddenInput.setAttribute( 'type', 'hidden' );
			hiddenInput.setAttribute( 'name', id );
			hiddenInput.setAttribute( 'id', id );
			hiddenInput.setAttribute( 'value', token );
			wc_monei_form.$paymentForm = document.getElementById( form );
			wc_monei_form.$paymentForm.appendChild( hiddenInput );
		},
		/**
		 * If Apple can make payments then we need to show the apple logo and title instead of Google
		 */
		update_apple_google_label: function () {
			//if apple google is selected and Apple can make payment
			if ( ! wc_monei_params.apple_google_pay ) {
				return;
			}
			const isApple = window.ApplePaySession?.canMakePayments();
			if (isApple) {
				const label = document.querySelector('label[for="payment_method_monei_apple_google"]');
				if (label) {
					// Change the label text to "Apple Pay"
					label.childNodes[0].nodeValue = "Apple Pay ";

					// Select the image within the label and change its source
					const icon = label.querySelector('img');
					if (icon) {
						icon.src = "https://mollie-payments-for-woocommerce.ddev.site/wp-content/plugins/monei-woocommerce-do-not-delete/assets/images/apple-logo.svg";
						icon.alt = "Apple Pay"; // Optional: update alt text as well
					}
				}
			}
		}
	};

	$(
		function() {
			wc_monei_form.init();
			wc_monei_form.update_apple_google_label();
		}
	);

})( jQuery );
