(function ($) {
	'use strict';

	// Checkout form event handlers
	$( document.body ).on(
		'updated_checkout',
		(e, data) => {
			wcMoneiForm.updateAppleGoogleLabel();
			// Update total on every updated_checkout event
			if (data?.fragments?.['monei_new_total']) {
				wcMoneiForm.total = data.fragments['monei_new_total'];
			}

			if (wcMoneiForm.isAppleSelected()) {
				wcMoneiForm.initAppleGooglePay();
			}
		}
	);

	// Pay for order form event handler
	$( 'form#order_review' ).on(
		'click',
		() => {
			if (wcMoneiForm.isAppleSelected()) {
				wcMoneiForm.initAppleGooglePay();
			}
		}
	);

	const wcMoneiForm = {
		// DOM element caches
		$checkoutForm: $( 'form.woocommerce-checkout' ),
		$addPaymentForm: $( 'form#add_payment_method' ),
		$orderPayForm: $( 'form#order_review' ),
		$cardInput: null,
		$container: null,
		$paymentRequestContainer: null,
		$errorContainer: null,
		$paymentForm: null,

		// State flags
		isCheckout: false,
		isAddPaymentMethod: false,
		isOrderPay: false,

		// Form reference and state
		form: null,
		submitted: false,
		initCounter: 0,
		initAppleCounter: 0,
		total: wc_monei_apple_google_params.total,

		// Validation regex
		cardholderNameRegex: /^[A-Za-zÀ-ú- ]{5,50}$/,

		init() {
			this.determineFormContext();
			this.attachEventListeners();
		},

		determineFormContext() {
			// Determine which form context we're in
			if (this.$checkoutForm.length) {
				this.isCheckout = true;
				this.form       = this.$checkoutForm;
			} else if (this.$addPaymentForm.length) {
				this.isAddPaymentMethod = true;
				this.form               = this.$addPaymentForm;
			} else if (this.$orderPayForm.length) {
				this.isOrderPay = true;
				this.form       = this.$orderPayForm;

				// Handle initial Apple Pay selection on order pay form
				if (this.isAppleSelected()) {
					this.initAppleGooglePay();
				}
			}
		},

		attachEventListeners() {
			if ( ! this.form) {
				return;
			}

			this.form.on( 'change', this.onChange.bind( this ) );

			// Order pay specific event handler
			if (this.isOrderPay) {
				$( 'input[name="payment_method"]' ).on(
					'change',
					() => {
						if (this.isAppleSelected()) {
							this.initAppleGooglePay();
						}
					}
				);
			}
		},

		onChange() {
			// Payment method selection handler
			$( "[name='payment_method']" ).on(
				'change',
				() => {
					wcMoneiForm.onPaymentSelected();
				}
			);

			// Saved card selection handler
			$( "[name='wc-monei-payment-token']" ).on(
				'change',
				() => {
					wcMoneiForm.onPaymentSelected();
				}
			);
		},

		onPaymentSelected() {
			const $placeOrderBtn     = $( '#place_order' );
			const $checkoutSubmitBtn = $( "[name='woocommerce_checkout_place_order']" );

			if (this.isAppleSelected()) {
				this.initAppleGooglePay();

				if (this.isCheckout) {
					$checkoutSubmitBtn.attr( 'data-monei', 'submit' );
				}

				$placeOrderBtn.prop( 'disabled', true );
				return false;
			} else {
				if (this.isCheckout) {
					$placeOrderBtn.prop( 'disabled', false );
					$checkoutSubmitBtn.removeAttr( 'data-monei' );
				}
			}
		},

		isAppleSelected() {
			return $( '#payment_method_monei_apple_google' ).is( ':checked' );
		},

		initAppleGooglePay() {
			// Reset counter if payment container was re-rendered
			if (this.$paymentRequestContainer?.childElementCount === 0) {
				this.initAppleCounter = 0;
			}

			// Initialize only once
			if (this.initAppleCounter !== 0) {
				return;
			}

			// Set checkout submit attribute
			if (this.isCheckout) {
				$( "[name='woocommerce_checkout_place_order']" ).attr( 'data-monei', 'submit' );
			}

			// Exit if Apple/Google Pay is not enabled
			if ( ! wc_monei_apple_google_params.apple_google_pay) {
				return;
			}

			this.instantiatePaymentRequest();
			this.$paymentRequestContainer = document.getElementById( 'payment-request-container' );

			// Mark as initialized
			this.initAppleCounter++;
		},

		instantiatePaymentRequest() {
			const paymentRequest = monei.PaymentRequest(
				{
					accountId: wc_monei_apple_google_params.account_id,
					sessionId: wc_monei_apple_google_params.session_id,
					amount: parseInt( this.total ),
					currency: wc_monei_apple_google_params.currency,
					onSubmit: (result) => {
						this.appleGoogleTokenHandler( result.token );
					},
					onError: (error) => {
						console.error( 'Payment request error:', error );
					}
				}
			);

			paymentRequest.render( '#payment-request-container' );
			window.paymentRequest = paymentRequest;
		},

		appleGoogleTokenHandler( token ) {
			$( '#place_order' ).prop( 'disabled', false );
			this.createHiddenInput( 'monei_payment_request_token', 'payment-request-form', token );

			// Submit form once token is created
			this.form.submit();
		},

		createHiddenInput( id, formId, token ) {
			const hiddenInput = document.createElement( 'input' );

			Object.assign(
				hiddenInput,
				{
					type: 'hidden',
					name: id,
					id: id,
					value: token
				}
			);

			this.$paymentForm = document.getElementById( formId );
			this.$paymentForm?.appendChild( hiddenInput );
		},

		/**
		 * Update label and icon based on Apple Pay availability
		 * Shows Apple Pay branding if device supports it, otherwise shows Google Pay
		 */
		updateAppleGoogleLabel() {
			if ( ! wc_monei_apple_google_params.apple_google_pay) {
				return;
			}

			const isApplePayAvailable = window.ApplePaySession?.canMakePayments();

			if (isApplePayAvailable) {
				const label = document.querySelector( 'label[for="payment_method_monei_apple_google"]' );

				if (label) {
					// Update label text
					label.childNodes[0].nodeValue = "Apple Pay ";

					// Update icon
					const icon = label.querySelector( 'img' );
					if (icon) {
						icon.src = wc_monei_apple_google_params.apple_logo;
						icon.alt = "Apple Pay";
					}
				}
			}
		}
	};

	// Initialize when DOM is ready
	$(
		() => {
			wcMoneiForm.init();
			wcMoneiForm.updateAppleGoogleLabel();
		}
	);

})( jQuery );