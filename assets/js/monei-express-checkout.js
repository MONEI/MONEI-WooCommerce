/**
 * Express checkout buttons on the classic (non-block) storefront.
 *
 * Block-rendered cart and checkout pages never load this file — they register an
 * express payment method through the WooCommerce Blocks registry instead.
 */

import {
	expressBeacon,
	expressBootstrap,
	expressRequest,
	setExpressParams,
} from './helpers/monei-express-api';
import { createExpressComponent } from './helpers/monei-express-payment-request';
import { isWalletDismissal } from './helpers/monei-shared-utils';

( function ( $ ) {
	'use strict';

	const params = window.wc_monei_express_params || {};

	setExpressParams( params );

	const isProductPage = 'product' === params.location;

	const state = {
		// One entry per wallet the merchant enabled here: `{ method, instance }`.
		components: [],
		root: null,
		amount: null,
		sessionId: null,
		cart: null,
		mounting: false,
		// Signature of the product selection currently sitting in the borrowed cart,
		// or null when the shopper's own cart has not been touched.
		cartHolds: null,
	};

	/**
	 * The product, quantity and variation the shopper has chosen on the page.
	 * @return {Object|null} Request fields for the product endpoints
	 */
	const readProductSelection = () => {
		if ( ! isProductPage || ! params.product?.id ) {
			return null;
		}

		const form = document.querySelector( 'form.cart' );
		const quantity =
			parseInt( form?.querySelector( '[name="quantity"]' )?.value, 10 ) ||
			1;
		const variationId =
			parseInt(
				form?.querySelector( '[name="variation_id"]' )?.value,
				10
			) || 0;
		const attributes = {};

		form?.querySelectorAll( '[name^="attribute_"]' ).forEach( ( field ) => {
			attributes[ field.name ] = field.value;
		} );

		return {
			product_id: params.product.id,
			quantity,
			variation_id: variationId,
			attributes,
		};
	};

	const selectionSignature = ( selection ) => JSON.stringify( selection );

	/**
	 * Makes sure the borrowed cart holds exactly the current selection.
	 *
	 * ⚠️ The server snapshots the shopper's own cart before emptying it, and this is
	 * safe to call repeatedly: a second call with the same selection does nothing, and
	 * a changed selection re-adds without taking a second snapshot.
	 * @return {Promise<void>}
	 */
	const ensureProductInCart = async () => {
		const selection = readProductSelection();

		if ( ! selection ) {
			return;
		}

		const signature = selectionSignature( selection );

		if ( state.cartHolds === signature ) {
			return;
		}

		const cart = await expressRequest( 'add_to_cart', selection );

		state.cartHolds = signature;
		state.cart = cart;
		state.amount = cart.amount;
	};

	/**
	 * Puts the shopper's own cart back. Called on every way out of the flow.
	 *
	 * ⚠️ The hold is only given up once the server has confirmed the release. Dropping
	 * it on a failed call would lose the shopper's cart for good: the `pagehide`
	 * beacon and every other exit path skip a cart they believe is already returned.
	 * @return {Promise<void>}
	 */
	const releaseCart = async () => {
		const held = state.cartHolds;

		if ( null === held ) {
			return;
		}

		// Cleared up front so a second call cannot release the same cart twice, and
		// put back below so a later exit path retries.
		state.cartHolds = null;

		try {
			await expressRequest( 'clear_cart' );
		} catch ( error ) {
			state.cartHolds = held;
			throw error;
		}
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
	 * A wallet reporting whether it can be used here.
	 *
	 * Each button hides on its own, so an unsupported wallet never leaves a dead
	 * control next to a working one, and the block as a whole only disappears once
	 * every wallet has reported and none of them can pay.
	 * @param {Object}  component   - Component entry from `state.components`
	 * @param {boolean} isSupported - What the wallet reported
	 */
	const setAvailability = ( component, isSupported ) => {
		component.supported = !! isSupported;
		component.container.classList.toggle( 'is-unavailable', ! isSupported );

		if ( state.components.some( ( entry ) => entry.supported === true ) ) {
			revealExpress();
			return;
		}

		if (
			state.components.every( ( entry ) => entry.supported === false )
		) {
			hideExpress();
		}
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
			'paypal' === result.paymentMethod
				? 'payment_method_monei_paypal'
				: 'payment_method_monei_apple_google'
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
	 * The classic cart and product pages carry no checkout form to submit, so the order
	 * is created server-side instead. `finalAmount` is forwarded exactly as the wallet
	 * reported it and is never substituted: the server recomputes the total and refuses
	 * the order on any mismatch, which only works while the two figures are independent.
	 * @param {Object} result - SubmitResult from monei.js
	 * @return {Promise<void>}
	 */
	const completeExpressPayment = async ( result ) => {
		const response = await expressRequest( 'create_order', {
			// Named for the field the classic checkout form posts, because the gateways
			// read the wallet token straight out of it.
			monei_payment_request_token: result.token,
			payment_method: result.paymentMethod || '',
			location: params.location,
			session_id: state.sessionId,
			final_amount: result.finalAmount,
			shipping_option: result.shippingOption?.id || '',
			billing: result.billingDetails || {},
			shipping: result.shippingDetails || result.billingDetails || {},
		} );

		// The server put the shopper's own cart back as part of placing the order, so
		// no exit path may release it a second time.
		state.cartHolds = null;

		window.location.href = response.redirect;
	};

	/**
	 * WooCommerce's own loading treatment, over the whole page.
	 *
	 * The wallet sheet closes the moment the shopper authorises, and the order is
	 * only built afterwards — a server round trip on the product and cart pages that
	 * ends in a redirect. Without this the storefront just sits there looking idle
	 * while money is being taken, which reads as a failed click and invites a second
	 * one. `blockUI` is what WooCommerce itself shows while a checkout is
	 * processing, so the shopper sees the spinner they would expect.
	 * @param {boolean} on - Whether to cover the page
	 */
	const setProcessing = ( on ) => {
		if ( ! $.blockUI ) {
			return;
		}

		if ( on ) {
			$.blockUI( {
				message: null,
				overlayCSS: { background: '#fff', opacity: 0.6 },
			} );
			return;
		}

		$.unblockUI();
	};

	const handleSubmit = ( result ) => {
		if ( ! result || ! result.token ) {
			showError( result?.error || params.i18n?.genericError );
			releaseCart().catch( () => {} );
			return;
		}

		showError( '' );
		setProcessing( true );

		const finish =
			'checkout' === params.location
				? submitCheckoutForm( result )
				: ensureProductInCart().then( () =>
						completeExpressPayment( result )
				  );

		// Deliberately not cleared on success: the page is on its way to the redirect,
		// and uncovering it first would flash an interactive storefront the shopper
		// could click during a payment that has already been authorised.
		finish.catch( ( error ) => {
			setProcessing( false );
			showError( error.message || params.i18n?.genericError );
			releaseCart().catch( () => {} );
		} );
	};

	/**
	 * Where the wallet total comes from.
	 *
	 * On a product page the cart is not the answer until the flow has borrowed it, so
	 * the amount comes from the selected product instead. Once the borrow has happened
	 * the cart is authoritative again.
	 * @return {Promise<Object>} Cart-shaped payload
	 */
	const readAmountSource = () => {
		if ( isProductPage && null === state.cartHolds ) {
			return expressRequest(
				'get_selected_product_data',
				readProductSelection()
			);
		}

		return expressRequest( 'get_cart_details' );
	};

	/**
	 * Keeps the wallet total in step without rebuilding the component. Rebuilding
	 * would drop the sheet the shopper is looking at.
	 */
	const refreshAmount = async () => {
		if ( ! state.components.length ) {
			return;
		}

		const cart = await readAmountSource();

		state.cart = cart;

		if ( cart.amount === state.amount ) {
			return;
		}

		state.amount = cart.amount;

		await Promise.all(
			state.components.map( ( { instance } ) =>
				// One wallet refusing the update must not abandon the rest of
				// the batch, or the others keep the previous amount.
				instance
					.updateProps( { amount: cart.amount } )
					.catch( () => {} )
			)
		);
	};

	/**
	 * Builds and renders one wallet into its own container.
	 * @param {Element} container - Mount container carrying the method name
	 * @param {Object}  cart      - Cart-shaped payload
	 * @return {Promise<void>}
	 */
	const mountMethod = async ( container, cart ) => {
		const method = container.dataset.moneiExpressMethod;
		const entry = { method, container, instance: null, supported: null };

		const instance = createExpressComponent( method, {
			accountId: params.accountId,
			sessionId: state.sessionId,
			amount: cart.amount,
			currency: cart.currency,
			language: params.language,
			style: params.methods?.[ method ]?.style,
			requestShipping: cart.shippingRequired,
			onCartChange: ( payload ) => {
				state.cart = payload;
				state.amount = payload.amount;
			},
			beforeServerCall: isProductPage ? ensureProductInCart : null,
			onBeforeOpen: isProductPage
				? () => {
						// The wallet button is a cross-origin iframe, so this is the
						// only signal there is that the sheet is opening. It cannot
						// await, so the cart is prepared here optimistically and
						// again — idempotently — before anything depends on it.
						ensureProductInCart().catch( () => {} );
						return true;
				  }
				: null,
			onSubmit: handleSubmit,
			onError: ( error ) => {
				// Uncovers the page when the wallet fails after the token was
				// handed over, which is the one way `handleSubmit` can leave it
				// blocked without reaching its own catch. A no-op otherwise.
				setProcessing( false );

				// A dismissed sheet still has to release the cart, but it is not
				// something to report: the shopper chose it.
				showError(
					isWalletDismissal( error )
						? ''
						: error?.message || params.i18n?.genericError
				);
				releaseCart().catch( () => {} );
			},
			onLoad: ( isSupported ) => setAvailability( entry, isSupported ),
		} );

		if ( ! instance ) {
			container.classList.add( 'is-unavailable' );
			return;
		}

		entry.instance = instance;
		state.components.push( entry );

		await instance.render( container );
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
		state.components = [];

		try {
			const { sessionId } = await expressBootstrap();
			const cart = await readAmountSource();

			state.sessionId = sessionId;
			state.cart = cart;
			state.amount = cart.amount;

			const containers = root.querySelectorAll(
				'.monei-express-checkout__button[data-monei-express-method]'
			);

			for ( const container of containers ) {
				await mountMethod( container, cart );
			}

			if ( ! state.components.length ) {
				hideExpress();
			}
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
			state.components = [];
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

		if ( isProductPage ) {
			// Quantity typing, variation selection and variation reset all move the
			// price the wallet must show.
			$( 'form.cart' ).on(
				'change keyup',
				'[name="quantity"], [name^="attribute_"]',
				function () {
					refreshAmount().catch( () => {} );
				}
			);

			$( document.body ).on(
				'found_variation reset_data',
				'form.cart',
				function () {
					refreshAmount().catch( () => {} );
				}
			);

			// Navigating away mid-flow is an exit path like any other, and a normal
			// request would be cancelled with the document.
			window.addEventListener( 'pagehide', function () {
				if (
					null !== state.cartHolds &&
					expressBeacon( 'clear_cart' )
				) {
					state.cartHolds = null;
				}
			} );
		}
	} );
} )( jQuery );
