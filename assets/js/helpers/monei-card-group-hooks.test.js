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
const { useMoneiCardGroup } = require( './monei-card-group-hooks' );

const CONFIG = {
	accountId: 'acc_123',
	sessionId: 'sess_123',
	language: 'en',
	currency: 'EUR',
	style: { base: { height: '50px' } },
};

const PART_FACTORIES = [ 'CardNumber', 'CardExpiry', 'CardCvc' ];

/**
 * Build a fake monei SDK and install it as the global.
 * @param {Object} options              - Mock options
 * @param {Object} options.submitResult - Result `group.submit()` resolves with
 * @return {Object} Mock handles
 */
const installMoneiMock = ( { submitResult } = {} ) => {
	const destroyOrder = [];
	const partInstances = {};

	const group = {
		submit: jest
			.fn()
			.mockResolvedValue(
				submitResult || { token: 'tok_123', paymentMethod: 'card' }
			),
		updateProps: jest.fn().mockResolvedValue( undefined ),
		destroy: jest.fn( () => destroyOrder.push( 'group' ) ),
	};

	const monei = {
		CardGroup: jest.fn( () => group ),
	};

	PART_FACTORIES.forEach( ( name ) => {
		monei[ name ] = jest.fn( () => {
			const part = {
				render: jest.fn(),
				destroy: jest.fn( () => destroyOrder.push( name ) ),
				clear: jest.fn(),
			};
			partInstances[ name ] = part;
			return part;
		} );
	} );

	global.monei = monei;

	return { monei, group, partInstances, destroyOrder };
};

/**
 * Render the hook inside a component that owns the three mount containers.
 * @param {number} amount - Initial amount in minor units
 * @return {Object} Render handles plus a live view of the hook result
 */
const renderCardGroup = ( amount = 3600 ) => {
	const api = {};

	const Harness = ( props ) => {
		const cardGroup = useMoneiCardGroup( props.config, props.amount );
		api.current = cardGroup;
		return (
			<div>
				<div data-testid="number" ref={ cardGroup.cardNumberRef } />
				<div data-testid="expiry" ref={ cardGroup.cardExpiryRef } />
				<div data-testid="cvc" ref={ cardGroup.cardCvcRef } />
			</div>
		);
	};

	const utils = render( <Harness amount={ amount } config={ CONFIG } /> );

	return {
		...utils,
		api,
		setAmount: ( next ) =>
			act( () => {
				utils.rerender( <Harness amount={ next } config={ CONFIG } /> );
			} ),
		// A fresh config object is what re-arms the init effect, the same way a
		// re-rendered checkout hands the hook a new settings object.
		remountConfig: () =>
			act( () => {
				utils.rerender(
					<Harness amount={ amount } config={ { ...CONFIG } } />
				);
			} ),
	};
};

/**
 * Advance past the delayed initialization timer.
 */
const flushInit = () => {
	act( () => {
		jest.advanceTimersByTime( 500 );
	} );
};

describe( 'useMoneiCardGroup', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
		delete global.monei;
	} );

	it( 'mounts all three parts and reports ready', () => {
		const { monei, partInstances } = installMoneiMock();
		const { api, getByTestId } = renderCardGroup();

		expect( monei.CardGroup ).not.toHaveBeenCalled();

		flushInit();

		expect( monei.CardGroup ).toHaveBeenCalledTimes( 1 );
		expect( monei.CardGroup ).toHaveBeenCalledWith(
			expect.objectContaining( {
				accountId: 'acc_123',
				sessionId: 'sess_123',
				amount: 3600,
				currency: 'EUR',
			} )
		);

		expect( partInstances.CardNumber.render ).toHaveBeenCalledWith(
			getByTestId( 'number' )
		);
		expect( partInstances.CardExpiry.render ).toHaveBeenCalledWith(
			getByTestId( 'expiry' )
		);
		expect( partInstances.CardCvc.render ).toHaveBeenCalledWith(
			getByTestId( 'cvc' )
		);

		expect( api.current.isReady ).toBe( true );
		expect( api.current.error ).toBe( '' );
	} );

	it( 'never forwards payment identifiers to a part', () => {
		const { monei } = installMoneiMock();
		renderCardGroup();
		flushInit();

		PART_FACTORIES.forEach( ( name ) => {
			const props = monei[ name ].mock.calls[ 0 ][ 0 ];
			expect( Object.keys( props ) ).toEqual( [ 'group' ] );
			[
				'amount',
				'currency',
				'accountId',
				'sessionId',
				'paymentId',
			].forEach( ( forbidden ) => {
				expect( props ).not.toHaveProperty( forbidden );
			} );
		} );
	} );

	it( 'returns a token on submit', async () => {
		installMoneiMock();
		const { api } = renderCardGroup();
		flushInit();

		let token;
		await act( async () => {
			token = await api.current.createToken();
		} );

		expect( token ).toBe( 'tok_123' );
		expect( api.current.token ).toBe( 'tok_123' );
		expect( api.current.error ).toBe( '' );
		expect( api.current.isCreatingToken ).toBe( false );
	} );

	it( 'reports an error when the SDK is unavailable', () => {
		delete global.monei;
		const { api } = renderCardGroup();
		flushInit();

		expect( api.current.isReady ).toBe( false );
		expect( api.current.error ).toBe( 'MONEI SDK is not available' );
	} );

	it( 'retries after an attempt that found no SDK', () => {
		delete global.monei;
		const { api, remountConfig } = renderCardGroup();
		flushInit();

		expect( api.current.isReady ).toBe( false );

		const { monei } = installMoneiMock();
		remountConfig();
		flushInit();

		expect( monei.CardGroup ).toHaveBeenCalledTimes( 1 );
		expect( api.current.isReady ).toBe( true );
		expect( api.current.error ).toBe( '' );
	} );

	it( 'does not re-create a group that already mounted', () => {
		const { monei } = installMoneiMock();
		const { remountConfig } = renderCardGroup();
		flushInit();

		remountConfig();
		flushInit();

		expect( monei.CardGroup ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'refuses to submit before the group is ready', async () => {
		installMoneiMock();
		const { api } = renderCardGroup();

		let token;
		await act( async () => {
			token = await api.current.createToken();
		} );

		expect( token ).toBeNull();
		expect( api.current.error ).toBe( 'Card input not initialized' );
	} );

	it( 'surfaces an error returned by submit', async () => {
		installMoneiMock( {
			submitResult: {
				error: { message: 'Invalid card number' },
				paymentMethod: 'card',
			},
		} );
		const { api } = renderCardGroup();
		flushInit();

		let token;
		await act( async () => {
			token = await api.current.createToken();
		} );

		expect( token ).toBeNull();
		expect( api.current.token ).toBeNull();
		expect( api.current.error ).toBe( 'Invalid card number' );
	} );

	it( 'tears down parts before the group when unmounted mid-submit', async () => {
		const { destroyOrder } = installMoneiMock();
		const { api, unmount } = renderCardGroup();
		flushInit();

		let pending;
		act( () => {
			pending = api.current.createToken();
		} );
		unmount();

		await act( async () => {
			await expect( pending ).resolves.toBe( 'tok_123' );
		} );
		expect( destroyOrder ).toEqual( [
			'CardNumber',
			'CardExpiry',
			'CardCvc',
			'group',
		] );
	} );

	it( 'updates the amount only when the total changed', () => {
		const { group } = installMoneiMock();
		const { setAmount } = renderCardGroup( 3600 );
		flushInit();

		// Seeded through the constructor, so nothing to push yet.
		expect( group.updateProps ).not.toHaveBeenCalled();

		setAmount( 5400 );
		expect( group.updateProps ).toHaveBeenCalledTimes( 1 );
		expect( group.updateProps ).toHaveBeenCalledWith( { amount: 5400 } );

		setAmount( 5400 );
		expect( group.updateProps ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not re-create the group when the amount changes', () => {
		const { monei } = installMoneiMock();
		const { setAmount } = renderCardGroup( 3600 );
		flushInit();

		setAmount( 5400 );
		setAmount( 7200 );

		expect( monei.CardGroup ).toHaveBeenCalledTimes( 1 );
		expect( monei.CardNumber ).toHaveBeenCalledTimes( 1 );
	} );
} );
