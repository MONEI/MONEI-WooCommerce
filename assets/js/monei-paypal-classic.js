( function ( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on( 'updated_checkout', function ( e, data ) {
		// Update paypal_widget.total on every updated_checkout event.
		if (
			'object' === typeof data &&
			data.fragments &&
			data.fragments.monei_new_total
		) {
			wc_paypal_form.total = data.fragments.monei_new_total;
			// Reset to allow re-initialization
			wc_paypal_form.init_counter = 0;
		}
		if ( wc_paypal_form.is_paypal_selected() ) {
			wc_paypal_form.init_checkout_paypal();
		}
	} );

	// Add Payment Method form.
	$( 'form#add_payment_method' ).on( 'click payment_methods', function () {
		if ( wc_paypal_form.is_paypal_selected() ) {
			wc_paypal_form.init_checkout_paypal();
		}
	} );

	// On Pay for order form.
	$( 'form#order_review' ).on( 'click', function () {
		if ( wc_paypal_form.is_paypal_selected() ) {
			wc_paypal_form.init_checkout_paypal();
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
					if ( wc_paypal_form.is_paypal_selected() ) {
						wc_paypal_form.on_payment_selected();
					}
				}
			}
		} );

		observer.observe( targetNode, { childList: true, subtree: true } );
	}

	var wc_paypal_form = {
		$checkout_form: $( 'form.woocommerce-checkout' ),
		$add_payment_form: $( 'form#add_payment_method' ),
		$order_pay_form: $( 'form#order_review' ),
		$container: null,
		$paymentForm: null,
		is_checkout: false,
		is_add_payment_method: false,
		is_order_pay: false,
		form: null,
		submitted: false,
		init_counter: 0,
		total: wc_paypal_params.total,
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
				if ( wc_paypal_form.is_paypal_selected() ) {
					wc_paypal_form.init_checkout_paypal();
				}
				this.is_order_pay = true;
				this.form = this.$order_pay_form;
				console.log( 'TOTAL', this.form );
			}

			if ( this.form ) {
				this.form.on( 'change', this.on_change );
			}
		},
		on_change() {
			// Triggers on payment method selection.
			$( "[name='payment_method']" ).on( 'change', function () {
				wc_paypal_form.on_payment_selected();
			} );
		},
		on_payment_selected() {
			if ( wc_paypal_form.is_paypal_selected() ) {
				wc_paypal_form.init_checkout_paypal();
				if ( wc_paypal_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).attr(
						'paypal-data-monei',
						'submit'
					);
				}
				$( '#place_order' ).prop( 'disabled', true );
			} else {
				if ( wc_paypal_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).removeAttr(
						'paypal-data-monei'
					);
				}
				//todo central state. If Apple is selected we dont want to mess with the disable after it
				if ( ! wc_paypal_form.is_apple_selected() ) {
					$( '#place_order' ).prop( 'disabled', false );
				}
			}
		},
		is_paypal_selected() {
			return $( '#payment_method_monei_paypal' ).is( ':checked' );
		},
		is_apple_selected() {
			return $( '#payment_method_monei_apple_google' ).is( ':checked' );
		},
		init_paypal_component() {
			if ( window.paypalRequest ) {
				window.paypalRequest.close();
			}
			console.log( 'despues', wc_paypal_form.total );
			wc_paypal_form.instantiate_payment_request();
		},
		instantiate_payment_request() {
			const paymentRequest = monei.PaymentRequest( {
				accountId: wc_paypal_params.account_id,
				sessionId: wc_paypal_params.session_id,
				amount: parseInt( wc_paypal_form.total ),
				currency: wc_paypal_params.currency,
				language: wc_paypal_params.language,
				paymentMethod: 'paypal',
				style: wc_paypal_params.paypal_style || {},
				onSubmit( result ) {
					$( '#place_order' ).prop( 'disabled', false );
					wc_paypal_form.request_token_handler( result.token );
				},
				onError( error ) {
					console.error( error );
				},
			} );
			// Render an instance of the Payment Request Component into the `payment_request_container` <div>.
			paymentRequest.render( '#paypal-container' );
			// Assign a global variable to paymentRequest so it's accessible.
			window.paypalRequest = paymentRequest;
		},
		init_checkout_paypal() {
			// If checkout is updated (and monei was initiated already), ex, selecting new shipping methods, checkout is re-render by the ajax call.
			// and we need to reset the counter in order to initiate again the monei component.
			if (
				wc_paypal_form.$container &&
				0 === wc_paypal_form.$container.childElementCount
			) {
				wc_paypal_form.init_counter = 0;
			}

			// init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_counter ) {
				return;
			}

			if ( wc_paypal_form.is_checkout ) {
				$( "[name='woocommerce_checkout_place_order']" ).attr(
					'paypal-data-monei',
					'submit'
				);
			}

			wc_paypal_form.init_paypal_component();
			wc_paypal_form.$container =
				document.getElementById( 'paypal-container' );

			// We already init PayPal.
			this.init_counter++;
		},
		request_token_handler( token ) {
			wc_paypal_form.create_hidden_input(
				'monei_payment_request_token',
				token
			);
			// Once Token is created, submit form.
			$( '#place_order' ).prop( 'disabled', false );
			wc_paypal_form.form.submit();
		},
		create_hidden_input( id, token ) {
			const hiddenInput = document.createElement( 'input' );
			hiddenInput.setAttribute( 'type', 'hidden' );
			hiddenInput.setAttribute( 'name', id );
			hiddenInput.setAttribute( 'id', id );
			hiddenInput.setAttribute( 'value', token );
			wc_paypal_form.$paymentForm =
				document.getElementById( 'monei-paypal-form' );
			wc_paypal_form.$paymentForm.appendChild( hiddenInput );
		},
	};

	$( function () {
		wc_paypal_form.init();
	} );
} )( jQuery );
