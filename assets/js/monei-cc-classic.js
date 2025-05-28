(function($) {
	'use strict';

	// Checkout form event handlers
	$(document.body).on('updated_checkout', (e, data) => {
		wcMoneiForm.updateAppleGoogleLabel();

		// Update total on every updated_checkout event
		if (data?.fragments?.['monei_new_total']) {
			wcMoneiForm.total = data.fragments['monei_new_total'];
		}

		if (wcMoneiForm.isMoneiSelected()) {
			wcMoneiForm.initCheckoutMonei();
		}
	});

	// Add Payment Method form event handler
	$('form#add_payment_method').on('click payment_methods', () => {
		if (wcMoneiForm.isMoneiSelected()) {
			wcMoneiForm.initCheckoutMonei();
		}
	});

	// Pay for order form event handler
	$('form#order_review').on('click', () => {
		if (wcMoneiForm.isMoneiSelected()) {
			wcMoneiForm.initCheckoutMonei();
		}
	});

	const wcMoneiForm = {
		// DOM element caches
		$checkoutForm: $('form.woocommerce-checkout'),
		$addPaymentForm: $('form#add_payment_method'),
		$orderPayForm: $('form#order_review'),
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
		total: wc_monei_params.total,

		// Validation regex
		cardholderNameRegex: /^[A-Za-zÀ-ú- ]{5,50}$/,

		init() {
			this.determineFormContext();
			this.attachEventListeners();
		},

		determineFormContext() {
			// Checkout Page
			if (this.$checkoutForm.length) {
				this.isCheckout = true;
				this.form = this.$checkoutForm;
				this.form.on('checkout_place_order', this.placeOrder.bind(this));
			}

			// Add payment method Page
			if (this.$addPaymentForm.length) {
				this.isAddPaymentMethod = true;
				this.form = this.$addPaymentForm;
				this.form.on('submit', this.placeOrder.bind(this));
			}

			// Pay for order (change_payment_method for subscriptions)
			if (this.$orderPayForm.length) {
				if (this.isMoneiSelected()) {
					this.onPaymentSelected();
				}

				this.isOrderPay = true;
				this.form = this.$orderPayForm;
				this.form.on('submit', this.placeOrderPage.bind(this));
			}
		},

		attachEventListeners() {
			if (!this.form) return;

			this.form.on('change', this.onChange.bind(this));

			// Order pay specific event handler
			if (this.isOrderPay) {
				$('input[name="payment_method"]').on('change', () => {
					if (this.isMoneiSelected()) {
						this.initCheckoutMonei();
					}
				});
			}
		},

		onChange() {
			// Payment method selection handler
			$("[name='payment_method']").on('change', () => {
				wcMoneiForm.onPaymentSelected();
			});

			// Saved card selection handler
			$("[name='wc-monei-payment-token']").on('change', () => {
				wcMoneiForm.onPaymentSelected();
			});
		},

		onPaymentSelected() {
			const $placeOrderBtn = $('#place_order');
			const $checkoutSubmitBtn = $("[name='woocommerce_checkout_place_order']");
			const $moneiInputs = $('.monei-input-container, .monei-card-input');

			if (this.isMoneiSelected()) {
				this.initCheckoutMonei();
				$placeOrderBtn.prop('disabled', false);

				if (this.isCheckout) {
					$checkoutSubmitBtn.attr('data-monei', 'submit');
				}

				// Show/hide input fields based on tokenized card selection
				if (this.isTokenizedCcSelected()) {
					$moneiInputs.hide();
				} else {
					$moneiInputs.show();
				}
			} else {
				if (this.isCheckout) {
					$placeOrderBtn.prop('disabled', false);
					$checkoutSubmitBtn.removeAttr('data-monei');
				}
			}
		},

		validateCardholderName() {
			const value = $('#monei_cardholder_name').val();
			const isValid = this.cardholderNameRegex.test(value);

			if (!isValid) {
				const errorString = wc_monei_params.nameErrorString;
				this.printErrors(errorString, '#monei-cardholder-name-error');
				return false;
			} else {
				this.clearErrors('#monei-cardholder-name-error');
				return true;
			}
		},

		isMoneiSelected() {
			return $('#payment_method_monei').is(':checked');
		},

		isTokenizedCcSelected() {
			const $tokenInput = $('input[name="wc-monei-payment-token"]');
			return $tokenInput.is(':checked') && $tokenInput.val() !== 'new';
		},

		isMoneiSavedCcSelected() {
			return this.isMoneiSelected() && this.isTokenizedCcSelected();
		},

		initCheckoutMonei() {
			const container = document.getElementById('monei-card-input');
			if (!container) {
				return;
			}

			// Reset counter if container was re-rendered
			if (this.$container?.childElementCount === 0) {
				this.initCounter = 0;
			}

			// Initialize only once
			if (this.initCounter !== 0) {
				return;
			}

			// Don't initialize when saved card is selected
			if (this.isMoneiSavedCcSelected()) {
				return;
			}

			// Set checkout submit attribute
			if (this.isCheckout) {
				$("[name='woocommerce_checkout_place_order']").attr('data-monei', 'submit');
			}

			this.setupCardholderValidation();
			this.initializeCardInput();

			// Mark as initialized
			this.initCounter++;
		},

		setupCardholderValidation() {
			$('#monei_cardholder_name').on('blur', () => {
				this.validateCardholderName();
			});
		},

		initializeCardInput() {
			this.$container = document.getElementById('monei-card-input');
			this.$errorContainer = document.getElementById('monei-card-error');

			const cardInputStyle = {
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

			this.$cardInput = monei.CardInput({
				accountId: wc_monei_params.account_id,
				sessionId: wc_monei_params.session_id,
				style: cardInputStyle,
				onChange: (event) => {
					// Handle real-time validation errors
					if (event.isTouched && event.error) {
						this.printErrors(event.error);
					} else {
						this.clearErrors();
					}
				},
				onEnter: () => {
					this.form.submit();
				},
				onFocus: () => {
					this.$container.classList.add('is-focused');
				},
				onBlur: () => {
					this.$container.classList.remove('is-focused');
				},
			});

			this.$cardInput.render(this.$container);
		},

		placeOrder(e) {
			const existingToken = document.getElementById('monei_payment_token');
			if (existingToken) {
				return true;
			}

			if (this.isMoneiSelected() && !this.isMoneiSavedCcSelected()) {
				if (!this.validateCardholderName()) {
					return false;
				}

				this.createTokenAndSubmit();
				return false;
			}
		},

		placeOrderPage(e) {
			const existingToken = document.getElementById('monei_payment_token');
			if (existingToken) {
				return true;
			}

			if (this.isMoneiSelected() && !this.isMoneiSavedCcSelected()) {
				if (!this.validateCardholderName()) {
					return false;
				}

				e.preventDefault();
				this.createTokenAndSubmit();
				return false;
			}
		},

		async createTokenAndSubmit() {
			try {
				const result = await monei.createToken(this.$cardInput);

				if (result.error) {
					console.error('Token creation error:', result.error);
					this.printErrors(result.error);
				} else {
					console.log('Token created successfully');
					this.moneiTokenHandler(result.token);
				}
			} catch (error) {
				console.error('Token creation failed:', error);
				this.printErrors(error.message);
			}
		},

		/**
		 * Display errors in the checkout form
		 * @param {string} errorString - Error message to display
		 * @param {string} errorContainer - Container selector for the error
		 */
		printErrors(errorString, errorContainer) {
			const $container = $(errorContainer || this.$errorContainer);

			$container.html('<br /><ul class="woocommerce_error woocommerce-error monei-error"><li /></ul>');
			$container.find('li').text(errorString);

			// Scroll to error if present
			const $errorElement = $container.find('.monei-error');
			if ($errorElement.length) {
				$('html, body').animate({
					scrollTop: ($container.offset().top - 200)
				}, 200);
			}
		},

		/**
		 * Clear form errors
		 * @param {string} errorContainer - Container selector to clear
		 */
		clearErrors(errorContainer) {
			const $container = $(errorContainer || this.$errorContainer);
			$container.html('');
		},

		moneiTokenHandler(token) {
			this.createHiddenInput('monei_payment_token', 'payment-form', token);
			// Submit form once token is created
			this.form.submit();
		},

		createHiddenInput(id, formId, token) {
			const hiddenInput = document.createElement('input');

			Object.assign(hiddenInput, {
				type: 'hidden',
				name: id,
				id: id,
				value: token
			});

			this.$paymentForm = document.getElementById(formId);
			this.$paymentForm?.appendChild(hiddenInput);
		},

		/**
		 * Update label and icon based on Apple Pay availability
		 * Note: This method is maintained for API compatibility but may not be used in card payment flow
		 */
		updateAppleGoogleLabel() {
			// Placeholder method for consistency with Apple/Google Pay integration
			// Implementation would be similar to the Apple Pay version if needed
		}
	};

	// Initialize when DOM is ready
	$(() => {
		wcMoneiForm.init();
	});

})(jQuery);