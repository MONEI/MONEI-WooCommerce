/**
 * Express checkout buttons on the classic (non-block) storefront.
 *
 * Block-rendered cart and checkout pages never load this file — they register an
 * express payment method through the WooCommerce Blocks registry instead.
 */

import {
	expressBootstrap,
	expressRequest,
	setExpressParams,
} from './helpers/monei-express-api';
import { createExpressPaymentRequest } from './helpers/monei-express-payment-request';

( function ( $ ) {
	'use strict';

	const params = window.wc_monei_express_params || {};

	setExpressParams( params );

	const state = {
		instance: null,
		container: null,
		root: null,
		amount: null,
		cart: null,
		mounting: false,
	};

	const showError = ( message ) => {
		const target = state.root?.querySelector(
			'.monei-express-checkout__error'
		);

		if ( target ) {
			target.textContent = message || '';
		}
	};

	const hideExpress = () => {
		state.root?.classList.add( 'is-unavailable' );
		state.root?.classList.remove( 'is-loading' );
	};

	const revealExpress = () => {
		state.root?.classList.remove( 'is-loading', 'is-unavailable' );
	};

	/**
	 * Writes a value into a classic checkout field, letting WooCommerce see the change.
	 * @param {string} name  - Input name
	 * @param {string} value - Value to set
	 */
	const setField = ( name, value ) => {
		if ( value === undefined || value === null || value === '' ) {
			return;
		}

		const field = document.querySelector( `[name="${ name }"]` );

		if ( ! field ) {
			return;
		}

		field.value = value;
		$( field ).trigger( 'change' );
	};

	/**
	 * @param {string} prefix  - `billing` or `shipping`
	 * @param {Object} address - Address already normalized by the server
	 */
	const fillAddress = ( prefix, address ) => {
		if ( ! address ) {
			return;
		}

		[
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
		].forEach( ( key ) =>
			setField( `${ prefix }_${ key }`, address[ key ] )
		);

		if ( 'billing' === prefix ) {
			setField( 'billing_email', address.email );
			setField( 'billing_phone', address.phone );
		}
	};

	/**
	 * Hands the wallet result to the classic checkout form, which then runs the
	 * ordinary WooCommerce order flow through the Apple/Google Pay gateway.
	 * @param {Object} result - SubmitResult from monei.js
	 */
	const submitCheckoutForm = async ( result ) => {
		const form = document.querySelector( 'form.woocommerce-checkout' );

		if ( ! form ) {
			throw new Error( params.i18n?.genericError );
		}

		const normalized = await expressRequest( 'normalize_address', {
			billing: result.billingDetails || {},
			shipping: result.shippingDetails || result.billingDetails || {},
		} );

		fillAddress( 'billing', normalized.billing );

		if ( state.cart?.shippingRequired ) {
			const shipToDifferent = document.getElementById(
				'ship-to-different-address-checkbox'
			);

			if ( shipToDifferent ) {
				shipToDifferent.checked = true;
				$( shipToDifferent ).trigger( 'change' );
			}

			fillAddress( 'shipping', normalized.shipping );
		}

		const radio = document.getElementById(
			'payment_method_monei_apple_google'
		);

		if ( radio ) {
			radio.checked = true;
			$( radio ).trigger( 'change' );
		}

		let token = form.querySelector(
			'input[name="monei_payment_request_token"]'
		);

		if ( ! token ) {
			token = document.createElement( 'input' );
			token.type = 'hidden';
			token.name = 'monei_payment_request_token';
			form.appendChild( token );
		}

		token.value = result.token;

		$( form ).trigger( 'submit' );
	};

	/**
	 * Completes a wallet payment started somewhere other than the checkout page.
	 *
	 * ⚠️ Deliberately unimplemented. The classic cart and product pages carry no
	 * checkout form to submit, so they need the server-side order creation that Task 22
	 * of the plan owns. This is the single seam that task fills in; every caller
	 * already unwinds the flow when it rejects.
	 */
	const completeExpressPayment = async () => {
		throw new Error( params.i18n?.genericError );
	};

	const handleSubmit = ( result ) => {
		if ( ! result || ! result.token ) {
			showError( result?.error || params.i18n?.genericError );
			$( document.body ).trigger( 'monei_express_aborted' );
			return;
		}

		showError( '' );

		const finish =
			'checkout' === params.location
				? submitCheckoutForm( result )
				: completeExpressPayment( result );

		finish.catch( ( error ) => {
			showError( error.message || params.i18n?.genericError );
			$( document.body ).trigger( 'monei_express_aborted' );
		} );
	};

	/**
	 * Keeps the wallet total in step with the cart without rebuilding the component.
	 * Rebuilding would drop the sheet the shopper is looking at.
	 */
	const refreshAmount = async () => {
		if ( ! state.instance ) {
			return;
		}

		const cart = await expressRequest( 'get_cart_details' );

		state.cart = cart;

		if ( cart.amount === state.amount ) {
			return;
		}

		state.amount = cart.amount;

		await state.instance.updateProps( { amount: cart.amount } );
	};

	const mount = async () => {
		const root = document.querySelector(
			'.monei-express-checkout[data-monei-express-location]'
		);

		if ( ! root || state.mounting ) {
			return;
		}

		state.mounting = true;
		state.root = root;
		state.container = root.querySelector(
			'.monei-express-checkout__button'
		);

		try {
			const { sessionId } = await expressBootstrap();
			const cart = await expressRequest( 'get_cart_details' );

			state.cart = cart;
			state.amount = cart.amount;

			const instance = createExpressPaymentRequest( {
				accountId: params.accountId,
				sessionId,
				amount: cart.amount,
				currency: cart.currency,
				language: params.language,
				style: params.buttonStyle,
				requestShipping: cart.shippingRequired,
				onCartChange: ( payload ) => {
					state.cart = payload;
					state.amount = payload.amount;
				},
				onSubmit: handleSubmit,
				onError: ( error ) => {
					showError( error?.message || params.i18n?.genericError );
				},
				onLoad: ( isSupported ) => {
					if ( isSupported ) {
						revealExpress();
					} else {
						hideExpress();
					}
				},
			} );

			if ( ! instance ) {
				hideExpress();
				return;
			}

			state.instance = instance;

			await instance.render( state.container );
		} finally {
			state.mounting = false;
		}
	};

	/**
	 * Remounts when WooCommerce replaced the container, refreshes the amount otherwise.
	 *
	 * The classic cart replaces its whole totals block on every quantity, coupon or
	 * removal change, and that takes the mount container with it.
	 */
	const onCartUpdated = async () => {
		const root = document.querySelector(
			'.monei-express-checkout[data-monei-express-location]'
		);

		if ( root && root !== state.root ) {
			state.instance = null;
			await mount();
			return;
		}

		await refreshAmount();
	};

	$( function () {
		mount().catch( () => hideExpress() );

		$( document.body ).on(
			'updated_checkout updated_cart_totals',
			function () {
				onCartUpdated().catch( () => {} );
			}
		);
	} );
} )( jQuery );
