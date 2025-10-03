( function ( $ ) {
	'use strict';
	// Checkout form.
	$( document.body ).on( 'updated_checkout', function ( e, data ) {
		wc_monei_form.update_apple_google_label();
		// Update cofidis_widget.total on every updated_checkout event.
		if (
			'object' === typeof data &&
			data.fragments &&
			data.fragments.monei_new_total
		) {
			wc_monei_form.total = data.fragments.monei_new_total;
		}

		if ( wc_monei_form.is_apple_selected() ) {
			wc_monei_form.init_apple_google_pay();
		}
	} );
	// On Pay for order form.
	$( 'form#order_review' ).on( 'click', function () {
		if ( wc_monei_form.is_apple_selected() ) {
			wc_monei_form.init_apple_google_pay();
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
		total: wc_monei_apple_google_params.total,
		cardholderNameRegex: /^[A-Za-zÀ-ú- ]{5,50}$/,
		init() {
			// Checkout Page
			if ( this.$checkout_form.length ) {
				this.is_checkout = true;
				this.form = this.$checkout_form;
			}

			// Add payment method Page
			if ( this.$add_payment_form.length ) {
				this.is_add_payment_method = true;
				this.form = this.$add_payment_form;
			}

			// Pay for order ( change_payment_method for subscriptions)
			if ( this.$order_pay_form.length ) {
				if ( wc_monei_form.is_apple_selected() ) {
					wc_monei_form.init_apple_google_pay();
				}

				this.is_order_pay = true;
				this.form = this.$order_pay_form;

				$( 'input[name="payment_method"]' ).on( 'change', function () {
					// Check if the apple google pay method is selected
					if ( wc_monei_form.is_apple_selected() ) {
						wc_monei_form.init_apple_google_pay();
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
			if ( wc_monei_form.is_apple_selected() ) {
				wc_monei_form.init_apple_google_pay();
				console.log( 'after' );
				if ( wc_monei_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).attr(
						'data-monei',
						'submit'
					);
				}
				$( '#place_order' ).prop( 'disabled', true );
				return false;
			}
			if ( wc_monei_form.is_checkout ) {
				$( '#place_order' ).prop( 'disabled', false );
				$( "[name='woocommerce_checkout_place_order']" ).removeAttr(
					'data-monei'
				);
			}
		},
		is_apple_selected() {
			return $( '#payment_method_monei_apple_google' ).is( ':checked' );
		},
		init_apple_google_pay() {
			// If checkout is updated (and monei was initiated already), ex, selecting new shipping methods, checkout is re-render by the ajax call.
			// and we need to reset the counter in order to initiate again the monei component.
			if (
				wc_monei_form.$payment_request_container &&
				0 === wc_monei_form.$payment_request_container.childElementCount
			) {
				wc_monei_form.init_apple_counter = 0;
			}

			// init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_apple_counter ) {
				return;
			}

			if ( wc_monei_form.is_checkout ) {
				$( "[name='woocommerce_checkout_place_order']" ).attr(
					'data-monei',
					'submit'
				);
			}

			wc_monei_form.instantiate_payment_request();
			wc_monei_form.$payment_request_container = document.getElementById(
				'payment-request-container'
			);

			// We already init the button.
			this.init_apple_counter++;
		},
		instantiate_payment_request() {
			const paymentRequest = monei.PaymentRequest( {
				accountId: wc_monei_apple_google_params.account_id,
				sessionId: wc_monei_apple_google_params.session_id,
				amount: parseInt( wc_monei_form.total ),
				currency: wc_monei_apple_google_params.currency,
				style: wc_monei_apple_google_params.payment_request_style || {},
				onSubmit( result ) {
					wc_monei_form.apple_google_token_handler( result.token );
				},
				onError( error ) {
					console.error( error );
				},
			} );
			paymentRequest.render( '#payment-request-container' );
			window.paymentRequest = paymentRequest;
		},
		apple_google_token_handler( token ) {
			$( '#place_order' ).prop( 'disabled', false );
			wc_monei_form.create_hidden_input(
				'monei_payment_request_token',
				'payment-request-form',
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
		/**
		 * If Apple can make payments then we need to show the apple logo and title instead of Google
		 */
		update_apple_google_label() {
			const isApple = window.ApplePaySession?.canMakePayments();
			if ( isApple ) {
				const label = document.querySelector(
					'label[for="payment_method_monei_apple_google"]'
				);
				if ( label ) {
					label.childNodes[ 0 ].nodeValue = 'Apple Pay ';
					const icon = label.querySelector( 'img' );
					if ( icon ) {
						icon.src = wc_monei_apple_google_params.apple_logo;
						icon.alt = 'Apple Pay';
					}
				}
			}
		},
	};

	$( function () {
		wc_monei_form.init();
		wc_monei_form.update_apple_google_label();
	} );
} )( jQuery );
