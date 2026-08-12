/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { getMoneiErrorMessage } from './monei-shared-utils';

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
