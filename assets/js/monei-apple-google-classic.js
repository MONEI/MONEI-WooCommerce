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
			// Reset to allow re-initialization
			wc_monei_form.init_apple_counter = 0;
		}

		if ( wc_monei_form.is_apple_selected() ) {
			wc_monei_form.init_checkout_apple_google();
			wc_monei_form.init_apple_google_pay();
		}
	} );
	// On Pay for order form.
	$( 'form#order_review' ).on( 'click', function () {
		if ( wc_monei_form.is_apple_selected() ) {
			wc_monei_form.init_checkout_apple_google();
			wc_monei_form.init_apple_google_pay();
		}
	} );

	const targetNode = document.getElementById( 'order_review' );

	if ( targetNode ) {
		const observer = new MutationObserver( function (
			mutationsList,
			observer
		) {
			for ( const mutation of mutationsList ) {
				if ( mutation.type === 'childList' ) {
					if ( wc_monei_form.is_apple_selected() ) {
						wc_monei_form.on_payment_selected();
					}
				}
			}
		} );

		observer.observe( targetNode, { childList: true, subtree: true } );
	}

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
				this.is_order_pay = true;
				this.form = this.$order_pay_form;

				if ( wc_monei_form.is_apple_selected() ) {
					wc_monei_form.init_checkout_apple_google();
					wc_monei_form.init_apple_google_pay();
				}

				$( 'input[name="payment_method"]' ).on( 'change', function () {
					// Check if the apple google pay method is selected
					if ( wc_monei_form.is_apple_selected() ) {
						wc_monei_form.init_checkout_apple_google();
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
				wc_monei_form.init_checkout_apple_google();
				wc_monei_form.init_apple_google_pay();
				// Apple/Google Pay initialized
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
			if ( wc_monei_form.$payment_request_container ) {
				const container = document.getElementById(
					'payment-request-container'
				);
				// Reset if stored container differs from current (recreated) OR current is empty
				if (
					wc_monei_form.$payment_request_container !== container ||
					container.childElementCount === 0
				) {
					wc_monei_form.init_apple_counter = 0;
				}
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

			wc_monei_form.init_apple_google_component();
			wc_monei_form.$payment_request_container = document.getElementById(
				'payment-request-container'
			);

			// We already init the button.
			this.init_apple_counter++;
		},
		init_checkout_apple_google() {
			// Check if container exists, create if needed (for order-pay page)
			let container = document.getElementById(
				'payment-request-container'
			);
			if ( ! container ) {
				// Create container structure if it doesn't exist
				const paymentMethodLi = document
					.querySelector( '#payment_method_monei_apple_google' )
					?.closest( 'li' );
				if ( ! paymentMethodLi ) {
					return;
				}

				// Create the container structure
				const fieldset = document.createElement( 'fieldset' );
				fieldset.id = 'wc-monei_apple_google-payment-request-form';
				fieldset.className =
					'monei-fieldset monei-payment-request-fieldset';

				container = document.createElement( 'div' );
				container.id = 'payment-request-container';
				container.className =
					'monei-payment-request-container wc-block-components-skeleton__element';

				fieldset.appendChild( container );
				paymentMethodLi.appendChild( fieldset );
			} else {
				// Ensure existing container has the correct class
				container.className =
					'monei-payment-request-container wc-block-components-skeleton__element';
			}
		},
		init_apple_google_component() {
			if ( window.paymentRequest ) {
				window.paymentRequest.close();
			}
			// Total amount updated
			wc_monei_form.instantiate_payment_request();
		},
		instantiate_payment_request() {
			const paymentRequest = monei.PaymentRequest( {
				accountId: wc_monei_apple_google_params.accountId,
				sessionId: wc_monei_apple_google_params.sessionId,
				amount: parseInt( wc_monei_form.total ),
				currency: wc_monei_apple_google_params.currency,
				style: wc_monei_apple_google_params.paymentRequestStyle || {},
				onSubmit( result ) {
					wc_monei_form.apple_google_token_handler( result.token );
				},
				onError( error ) {
					// Error handling is managed by MONEI SDK
				},
			} );
			paymentRequest.render( '#payment-request-container' );
			window.paymentRequest = paymentRequest;
		},
		apple_google_token_handler( token ) {
			$( '#place_order' ).prop( 'disabled', false );
			wc_monei_form.create_hidden_input(
				'monei_payment_request_token',
				'wc-monei_apple_google-payment-request-form',
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
			const label = document.querySelector(
				'label[for="payment_method_monei_apple_google"]'
			);
			if ( ! label ) {
				return;
			}

			if ( isApple ) {
				// Use Apple Pay title
				const appleTitle =
					wc_monei_apple_google_params.applePayTitle || 'Apple Pay';
				label.childNodes[ 0 ].nodeValue = appleTitle + ' ';
				const icon = label.querySelector( 'img' );
				if ( icon ) {
					icon.src = wc_monei_apple_google_params.appleLogo;
					icon.alt = 'Apple Pay';
				}
			} else {
				// Use Google Pay title
				const googleTitle =
					wc_monei_apple_google_params.googlePayTitle || 'Google Pay';
				label.childNodes[ 0 ].nodeValue = googleTitle + ' ';
			}
		},
	};

	$( function () {
		wc_monei_form.init();
		wc_monei_form.update_apple_google_label();
	} );
} )( jQuery );
