( function ( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on( 'updated_checkout', function ( e, data ) {
		// Update bizum_widget.total on every updated_checkout event.
		if (
			'object' === typeof data &&
			data.fragments &&
			data.fragments.monei_new_total
		) {
			wc_bizum_form.total = data.fragments.monei_new_total;
			// Reset to allow re-initialization
			wc_bizum_form.init_counter = 0;
		}
		if ( wc_bizum_form.is_bizum_selected() ) {
			wc_bizum_form.init_checkout_bizum();
		}
	} );

	// Add Payment Method form.
	$( 'form#add_payment_method' ).on( 'click payment_methods', function () {
		if ( wc_bizum_form.is_bizum_selected() ) {
			wc_bizum_form.init_checkout_bizum();
		}
	} );

	// On Pay for order form.
	$( 'form#order_review' ).on( 'click', function () {
		if ( wc_bizum_form.is_bizum_selected() ) {
			wc_bizum_form.init_checkout_bizum();
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
					if ( wc_bizum_form.is_bizum_selected() ) {
						wc_bizum_form.on_payment_selected();
					}
				}
			}
		} );

		observer.observe( targetNode, { childList: true, subtree: true } );
	}

	var wc_bizum_form = {
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
		total: wc_bizum_params.total,
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
				// Form reference set
				if ( wc_bizum_form.is_bizum_selected() ) {
					wc_bizum_form.init_checkout_bizum();
				}
			}

			if ( this.form ) {
				this.form.on( 'change', this.on_change );
			}
		},
		on_change() {
			// Triggers on payment method selection.
			$( "[name='payment_method']" ).on( 'change', function () {
				wc_bizum_form.on_payment_selected();
			} );
		},
		on_payment_selected() {
			if ( wc_bizum_form.is_bizum_selected() ) {
				wc_bizum_form.init_checkout_bizum();
				if ( wc_bizum_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).attr(
						'bizum-data-monei',
						'submit'
					);
				}
				$( '#place_order' ).prop( 'disabled', true );
			} else {
				if ( wc_bizum_form.is_checkout ) {
					$( "[name='woocommerce_checkout_place_order']" ).removeAttr(
						'bizum-data-monei'
					);
				}
				//todo central state. If Apple is selected we dont want to mess with the disable after it
				if ( ! wc_bizum_form.is_apple_selected() ) {
					$( '#place_order' ).prop( 'disabled', false );
				}
			}
		},
		is_bizum_selected() {
			return $( '#payment_method_monei_bizum' ).is( ':checked' );
		},
		is_apple_selected() {
			return $( '#payment_method_monei_apple_google' ).is( ':checked' );
		},
		init_bizum_component() {
			if ( window.bizumRequest ) {
				window.bizumRequest.close();
			}
			// Total amount updated
			wc_bizum_form.instantiate_payment_request();
		},
		instantiate_payment_request() {
			const paymentRequest = monei.Bizum( {
				accountId: wc_bizum_params.accountId,
				sessionId: wc_bizum_params.sessionId,
				amount: parseInt( wc_bizum_form.total ),
				currency: wc_bizum_params.currency,
				style: wc_bizum_params.bizumStyle || {},
				onSubmit( result ) {
					$( '#place_order' ).prop( 'disabled', false );
					wc_bizum_form.request_token_handler( result.token );
				},
				onError( error ) {
					// Error handling is managed by MONEI SDK
				},
			} );
			// Render an instance of the Payment Request Component into the `payment_request_container` <div>.
			paymentRequest.render( '#bizum-container' );
			// Assign a global variable to paymentRequest so it's accessible.
			window.bizumRequest = paymentRequest;
		},
		init_checkout_bizum() {
			// Check if container exists, create if needed (for order-pay page)
			let container = document.getElementById( 'bizum-container' );
			if ( ! container ) {
				// Create container structure if it doesn't exist
				const paymentMethodLi = document.querySelector( '#payment_method_monei_bizum' )?.closest( 'li' );
				if ( ! paymentMethodLi ) {
					return;
				}

				// Create the container structure
				const fieldset = document.createElement( 'fieldset' );
				fieldset.id = 'wc-monei_bizum-cc-form';
				fieldset.className = 'wc-bizum-form';
				fieldset.style.background = 'transparent';
				fieldset.style.border = 'none';

				container = document.createElement( 'div' );
				container.id = 'bizum-container';

				fieldset.appendChild( container );
				paymentMethodLi.appendChild( fieldset );
			}

			// If checkout is updated (and monei was initiated already), ex, selecting new shipping methods, checkout is re-render by the ajax call.
			// and we need to reset the counter in order to initiate again the monei component.
			if (
				wc_bizum_form.$container &&
				0 === wc_bizum_form.$container.childElementCount
			) {
				wc_bizum_form.init_counter = 0;
			}

			// init monei just once, despite how many times this may be triggered.
			if ( 0 !== this.init_counter ) {
				return;
			}

			if ( wc_bizum_form.is_checkout ) {
				$( "[name='woocommerce_checkout_place_order']" ).attr(
					'bizum-data-monei',
					'submit'
				);
			}

			wc_bizum_form.init_bizum_component();
			wc_bizum_form.$container =
				document.getElementById( 'bizum-container' );

			// We already init Bizum.
			this.init_counter++;
		},
		request_token_handler( token ) {
			wc_bizum_form.create_hidden_input(
				'monei_payment_request_token',
				token
			);
			// Once Token is created, submit form.
			$( '#place_order' ).prop( 'disabled', false );
			wc_bizum_form.form.submit();
		},
		create_hidden_input( id, token ) {
			const hiddenInput = document.createElement( 'input' );
			hiddenInput.setAttribute( 'type', 'hidden' );
			hiddenInput.setAttribute( 'name', id );
			hiddenInput.setAttribute( 'id', id );
			hiddenInput.setAttribute( 'value', token );
			wc_bizum_form.$paymentForm =
				document.getElementById( 'monei-bizum-form' );
			wc_bizum_form.$paymentForm.appendChild( hiddenInput );
		},
	};

	$( function () {
		wc_bizum_form.init();
	} );
} )( jQuery );
