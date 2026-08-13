/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import * as React from 'react';

// The hook destructures `wp.element` at module scope, so the global must exist
// before it is required.
global.wp = { element: React };

/**
 * Internal dependencies
 */
const { useMoneiCardInput } = require( './monei-card-input-hooks' );

const CONFIG = {
	accountId: 'acc_123',
	sessionId: 'sess_123',
	language: 'en',
	currency: 'EUR',
	style: { base: { height: '42px' } },
};

/**
 * Build a fake monei SDK and install it as the global.
 * @return {Object} Mock handles
 */
const installMoneiMock = () => {
	const cardInput = {
		render: jest.fn(),
		submit: jest
			.fn()
			.mockResolvedValue( { token: 'tok_123', paymentMethod: 'card' } ),
		updateProps: jest.fn().mockResolvedValue( undefined ),
		destroy: jest.fn(),
		clear: jest.fn(),
	};

	global.monei = { CardInput: jest.fn( () => cardInput ) };

	return { monei: global.monei, cardInput };
};

/**
 * Render the hook inside a component that owns the mount container.
 * @param {number} amount - Initial amount in minor units
 * @return {Object} Render handles plus a live view of the hook result
 */
const renderCardInput = ( amount = 3600 ) => {
	const api = {};

	const Harness = ( props ) => {
		const cardInput = useMoneiCardInput( props.config, props.amount );
		api.current = cardInput;
		return <div data-testid="card" ref={ cardInput.containerRef } />;
	};

	const utils = render( <Harness amount={ amount } config={ CONFIG } /> );

	return { ...utils, api };
};

/**
 * Advance past the delayed initialization timer.
 */
const flushInit = () => {
	act( () => {
		jest.advanceTimersByTime( 500 );
	} );
};

describe( 'useMoneiCardInput', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete global.monei;
	} );

	it( 'mounts the card input and reports ready', () => {
		const { monei } = installMoneiMock();
		const { api, getByTestId } = renderCardInput();

		expect( monei.CardInput ).not.toHaveBeenCalled();

		flushInit();

		expect( monei.CardInput ).toHaveBeenCalledWith(
			expect.objectContaining( {
				accountId: 'acc_123',
				amount: 3600,
				currency: 'EUR',
			} )
		);
		expect( api.current.isReady ).toBe( true );
		expect( getByTestId( 'card' ) ).toBeTruthy();
	} );

	// The token request carries the amount the card input holds. A shopper who
	// pays while an amount update is still crossing into the iframe would
	// otherwise be tokenized against the previous total.
	describe( 'amount update serialization', () => {
		/**
		 * Make `updateProps` hang until the returned settler is called.
		 * @param {Object} cardInput - Mock card input instance
		 * @return {Object} Settlers for the pending update
		 */
		const holdUpdateProps = ( cardInput ) => {
			const settlers = {};
			cardInput.updateProps.mockImplementation(
				() =>
					new Promise( ( resolve, reject ) => {
						settlers.resolve = resolve;
						settlers.reject = reject;
					} )
			);
			return settlers;
		};

		it( 'does not submit until a pending amount update settles', async () => {
			const { cardInput } = installMoneiMock();
			const { api } = renderCardInput( 3600 );
			flushInit();

			const update = holdUpdateProps( cardInput );
			act( () => {
				api.current.updateProps( { amount: 5400 } );
			} );

			let pending;
			act( () => {
				pending = api.current.createToken();
			} );
			await act( async () => {} );

			expect( cardInput.submit ).not.toHaveBeenCalled();

			await act( async () => {
				update.resolve();
				await pending;
			} );

			expect( cardInput.submit ).toHaveBeenCalledTimes( 1 );
			await expect( pending ).resolves.toBe( 'tok_123' );
		} );

		it( 'still submits when the amount update rejects', async () => {
			const errorSpy = jest
				.spyOn( console, 'error' )
				.mockImplementation( () => {} );
			const { cardInput } = installMoneiMock();
			const { api } = renderCardInput( 3600 );
			flushInit();

			const update = holdUpdateProps( cardInput );
			act( () => {
				api.current.updateProps( { amount: 5400 } );
			} );

			let pending;
			act( () => {
				pending = api.current.createToken();
			} );

			await act( async () => {
				update.reject( new Error( 'iframe gone' ) );
				await pending;
			} );

			expect( cardInput.submit ).toHaveBeenCalledTimes( 1 );
			await expect( pending ).resolves.toBe( 'tok_123' );
			expect( errorSpy ).toHaveBeenCalled();
			errorSpy.mockRestore();
		} );

		it( 'submits on the deadline when the amount update never settles', async () => {
			const warnSpy = jest
				.spyOn( console, 'warn' )
				.mockImplementation( () => {} );
			const { cardInput } = installMoneiMock();
			const { api } = renderCardInput( 3600 );
			flushInit();

			holdUpdateProps( cardInput );
			act( () => {
				api.current.updateProps( { amount: 5400 } );
			} );

			let pending;
			act( () => {
				pending = api.current.createToken();
			} );
			await act( async () => {} );

			expect( cardInput.submit ).not.toHaveBeenCalled();

			await act( async () => {
				jest.advanceTimersByTime( 2000 );
				await pending;
			} );

			expect( cardInput.submit ).toHaveBeenCalledTimes( 1 );
			await expect( pending ).resolves.toBe( 'tok_123' );
			expect( warnSpy ).toHaveBeenCalled();
			warnSpy.mockRestore();
		} );
	} );
} );
