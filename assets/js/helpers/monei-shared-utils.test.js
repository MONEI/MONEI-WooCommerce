/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import {
	getAmountInMinorUnits,
	getMoneiErrorMessage,
} from './monei-shared-utils';

describe( 'test harness', () => {
	it( 'runs in a jsdom environment', () => {
		expect( typeof document ).toBe( 'object' );
	} );

	it( 'resolves modules from assets/js', () => {
		expect(
			getMoneiErrorMessage( { message: 'Card declined' }, 'fallback' )
		).toBe( 'Card declined' );
	} );

	it( 'renders hooks, which the component hooks are tested through', () => {
		const { result } = renderHook( () => useState( 'ready' ) );
		expect( result.current[ 0 ] ).toBe( 'ready' );
	} );
} );

describe( 'getAmountInMinorUnits', () => {
	// Every one of these totals lands just under the integer once multiplied,
	// so truncating charges the shopper a cent less than the order.
	it.each( [
		[ 0.29, 29 ],
		[ 0.57, 57 ],
		[ 0.58, 58 ],
		[ 1.13, 113 ],
		[ 1.14, 114 ],
		[ 2.05, 205 ],
	] )( 'charges %p in full as %p minor units', ( total, expected ) => {
		expect( getAmountInMinorUnits( undefined, total ) ).toBe( expected );
	} );

	it( 'converts totals that need no rounding', () => {
		expect( getAmountInMinorUnits( undefined, 35.99 ) ).toBe( 3599 );
		expect( getAmountInMinorUnits( undefined, 10 ) ).toBe( 1000 );
	} );

	it( 'never truncates a total between 0.01 and 2000.00', () => {
		// The cent count we started from is the amount the shopper owes, so it
		// is an oracle the conversion cannot be checked against by repeating it.
		const wrong = [];
		for ( let minor = 1; minor <= 200000; minor++ ) {
			if (
				getAmountInMinorUnits( undefined, Number( minor ) / 100 ) !==
				minor
			) {
				wrong.push( minor );
			}
		}
		expect( wrong ).toEqual( [] );
	} );

	it( 'prefers the Store API total, which is already in minor units', () => {
		expect( getAmountInMinorUnits( { total_price: '3599' }, 12.34 ) ).toBe(
			3599
		);
	} );

	it( 'reads a Store API total with a leading zero as decimal', () => {
		expect( getAmountInMinorUnits( { total_price: '0899' }, 12.34 ) ).toBe(
			899
		);
	} );
} );
