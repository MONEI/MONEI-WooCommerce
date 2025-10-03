( function ( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on( 'updated_checkout', function ( e, data ) {
		if (
			'object' === typeof data &&
			data.fragments &&
			data.fragments.monei_new_total
		) {
			wc_monei_form.total = data.fragments.monei_new_total;
		}

		if ( wc_monei_form.is_monei_selected() ) {
			wc_monei_form.init_checkout_monei();
		}
	} );

	// Add Payment Method form.
	$( 'form#add_payment_method' ).on( 'click payment_methods', function () {
		if ( wc_monei_form.is_monei_selected() ) {
			wc_monei_form.init_checkout_monei();
		}
	} );

	// On Pay for order form.
	$( 'form#order_review' ).on( 'click', function () {
		if ( wc_monei_form.is_monei_selected() ) {
			wc_monei_form.init_checkout_monei();
		}
	} );

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
		init() {
			// Checkout Page
			if ( this.$checkout_form.length ) {
				this.is_checkout = true;
				this.form = this.$checkout_form;
				this.form.on( 'checkout_place_order', this.place_order );
			}

			// Add payment method Page
			if ( this.$add_payment_form.length ) {
				this.is_add_payment_method = true;
				this.form = this.$add_payment_form;
				this.form.on( 'submit', this.place_order );
			}

			// Pay for order ( change_payment_method for subscriptions)
			if ( this.$order_pay_form.length ) {
				if ( wc_monei_form.is_monei_selected() ) {
					wc_monei_form.on_payment_selected();
				}

				this.is_order_pay = true;
				this.form = this.$order_pay_form;
				this.form.on( 'submit', this.place_order_page );

				$( 'input[name="payment_method"]' ).on( 'change', function () {
					// Check if the monei method is selected
					if ( wc_monei_form.is_monei_selected() ) {
						wc_monei_form.init_checkout_monei();
					}
				} );
			}

			if ( this.form ) {
				this.form.on( 'change', this.on_change );
			}
		},
		on_change() {
			// Triggers on payment method selection.
			$( "[name='payment_method']" ).on( 'change', function () {
				wc_monei_form.on_payment_selected();
			} );
			// Triggers on saved card selection.
			$( "[name='wc-monei-payment-token']" ).on( 'change', function () {
				wc_monei_form.on_payment_selected();
			} );
		},
		on_payment_selected() {
			if ( wc_monei_form.is_monei_selected() ) {
				wc_monei_form.init_checkout_monei();
				$( '#place_order' ).prop( 'disabled', false );
				if ( wc_monei_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).attr(
						'data-monei',
						'submit'
					);
				}
				if ( wc_monei_form.is_tokenized_cc_selected() ) {
					$( '.monei-input-container, .monei-card-input' ).hide();
				} else {
					$( '.monei-input-container, .monei-card-input' ).show();
				}
			}
		},
		validate_cardholder_name() {
			const value = $( '#monei_cardholder_name' ).val();
			if ( ! wc_monei_form.cardholderNameRegex.test( value ) ) {
				const errorString = wc_monei_params.nameErrorString;
				// Show error
				wc_monei_form.print_errors(
					errorString,
					'#monei-cardholder-name-error'
				);
				return false;
			}
			// Clear error
			wc_monei_form.clear_errors( '#monei-cardholder-name-error' );
			return true;
		},
		is_monei_selected() {
			return $( '#payment_method_monei' ).is( ':checked' );
		},
		is_tokenized_cc_selected() {
			return (
				$( 'input[name="wc-monei-payment-token"]' ).is( ':checked' ) &&
				'new' !==
					$( 'input[name="wc-monei-payment-token"]:checked' ).val()
			);
		},
		is_monei_saved_cc_selected() {
			return (
				wc_monei_form.is_monei_selected() &&
				wc_monei_form.is_tokenized_cc_selected()
			);
		},
		init_checkout_monei() {
			const container = document.getElementById( 'monei-card-input' );
			if ( container === null ) {
				return;
			}
			// If checkout is updated (and monei was initiated already), ex, selecting new shipping methods, checkout is re-render by the ajax call.
			// and we need to reset the counter in order to initiate again the monei component.
			if (
				wc_monei_form.$container &&
				0 === wc_monei_form.$container.childElementCount
			) {
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
				$( "[name='woocommerce_checkout_place_order']" ).attr(
					'data-monei',
					'submit'
				);
			}

			$( '#monei_cardholder_name' ).on( 'blur', function () {
				wc_monei_form.validate_cardholder_name();
			} );

			wc_monei_form.$container =
				document.getElementById( 'monei-card-input' );
			wc_monei_form.$errorContainer =
				document.getElementById( 'monei-card-error' );

			wc_monei_form.$cardInput = monei.CardInput( {
				accountId: wc_monei_params.account_id,
				sessionId: wc_monei_params.session_id,
				style: wc_monei_params.card_input_style || {},
				onChange( event ) {
					// Handle real-time validation errors.
					if ( event.isTouched && event.error ) {
						wc_monei_form.print_errors( event.error );
					} else {
						wc_monei_form.clear_errors();
					}
				},
				onEnter() {
					wc_monei_form.form.submit();
				},
				onFocus() {
					wc_monei_form.$container.classList.add( 'is-focused' );
				},
				onBlur() {
					wc_monei_form.$container.classList.remove( 'is-focused' );
				},
			} );
			wc_monei_form.$cardInput.render( wc_monei_form.$container );

			// We already init CardInput.
			this.init_counter++;
		},
		place_order( e ) {
			const token = document.getElementById( 'monei_payment_token' );
			if ( token ) {
				return true;
			}
			if (
				wc_monei_form.is_monei_selected() &&
				! wc_monei_form.is_monei_saved_cc_selected()
			) {
				if ( ! wc_monei_form.validate_cardholder_name() ) {
					return false;
				}
				//e.preventDefault();
				// This will be trigger, when CC component is used and "Place order" has been clicked.
				monei
					.createToken( wc_monei_form.$cardInput )
					.then( function ( result ) {
						if ( result.error ) {
							// Error displayed via print_errors
							// Inform the user if there was an error.
							wc_monei_form.print_errors( result.error );
						} else {
							// Token created successfully
							// Create monei token and append it to DOM
							wc_monei_form.monei_token_handler( result.token );
						}
					} )
					.catch( function ( error ) {
						// Error displayed via print_errors
						wc_monei_form.print_errors( error.message );
					} );
				return false;
			}
		},
		place_order_page( e ) {
			const token = document.getElementById( 'monei_payment_token' );
			if ( token ) {
				return true;
			}
			if (
				wc_monei_form.is_monei_selected() &&
				! wc_monei_form.is_monei_saved_cc_selected()
			) {
				if ( ! wc_monei_form.validate_cardholder_name() ) {
					return false;
				}
				e.preventDefault();
				// This will be trigger, when CC component is used and "Place order" has been clicked.
				monei
					.createToken( wc_monei_form.$cardInput )
					.then( function ( result ) {
						if ( result.error ) {
							// Error displayed via print_errors
							// Inform the user if there was an error.
							wc_monei_form.print_errors( result.error );
						} else {
							// Token created successfully
							// Create monei token and append it to DOM
							wc_monei_form.monei_token_handler( result.token );
						}
					} )
					.catch( function ( error ) {
						// Error displayed via print_errors
						wc_monei_form.print_errors( error.message );
					} );
				return false;
			}
		},
		/**
		 * Printing errors into checkout form.
		 * @param error_string
		 * @param errorContainer
		 */
		print_errors( error_string, errorContainer ) {
			if ( ! errorContainer ) {
				errorContainer = wc_monei_form.$errorContainer;
			}
			$( errorContainer ).html(
				'<br /><ul class="woocommerce_error woocommerce-error monei-error"><li /></ul>'
			);
			$( errorContainer ).find( 'li' ).text( error_string );
			// Scroll to error
			if ( $( errorContainer ).find( '.monei-error' ).length ) {
				$( 'html, body' ).animate(
					{
						scrollTop: $( errorContainer ).offset().top - 200,
					},
					200
				);
			}
		},
		/**
		 * Clearing form errors.
		 * @param errorContainer
		 */
		clear_errors( errorContainer ) {
			if ( ! errorContainer ) {
				errorContainer = wc_monei_form.$errorContainer;
			}
			// Clear all content from the error container
			$( errorContainer ).html( '' );
		},
		monei_token_handler( token ) {
			wc_monei_form.create_hidden_input(
				'monei_payment_token',
				'payment-form',
				token
			);
			// Once Token is created, submit form.
			wc_monei_form.form.submit();
		},
		create_hidden_input( id, form, token ) {
			const hiddenInput = document.createElement( 'input' );
			hiddenInput.setAttribute( 'type', 'hidden' );
			hiddenInput.setAttribute( 'name', id );
			hiddenInput.setAttribute( 'id', id );
			hiddenInput.setAttribute( 'value', token );
			wc_monei_form.$paymentForm = document.getElementById( form );
			wc_monei_form.$paymentForm.appendChild( hiddenInput );
		},
		render_card_brands_in_label() {
			if ( ! wc_monei_params.card_brands ) {
				return;
			}

			const label = $( 'label[for="payment_method_monei"]' );
			if ( ! label.length || label.find( '.monei-card-brands' ).length ) {
				return;
			}

			let html = '<span class="monei-card-brands">';
			const brands = Object.values( wc_monei_params.card_brands );

			// Skip the 'default' brand
			for ( let i = 0; i < brands.length; i++ ) {
				if ( brands[ i ].title === 'Card' ) {
					continue;
				}
				html +=
					'<img src="' +
					brands[ i ].url +
					'" ' +
					'alt="' +
					brands[ i ].title +
					'" ' +
					'class="card-brand-icon" />';
			}

			html += '</span>';
			label.append( html );
		},
	};
	$( function () {
		wc_monei_form.init();
		wc_monei_form.render_card_brands_in_label();

		// Re-render card brands after checkout update
		$( document.body ).on( 'updated_checkout', function () {
			wc_monei_form.render_card_brands_in_label();
		} );
	} );
} )( jQuery );
