jQuery( document ).ready( function ( $ ) {
	// Connect is the main flow: the manual key fields stay collapsed while the
	// shop can connect, until the merchant asks for them.
	let manualKeysOpen =
		$( '#monei-connect-section' ).data( 'manual-keys' ) !== 'closed';

	// Function to toggle API key and Account ID fields
	function toggleApiKeyFields() {
		const mode = $( '#monei_apikey_mode' ).val();
		if ( ! manualKeysOpen ) {
			$(
				'.monei-test-api-key-field, .monei-test-account-id-field, .monei-live-api-key-field, .monei-live-account-id-field'
			)
				.closest( 'tr' )
				.hide();
		} else if ( mode === 'test' ) {
			$( '.monei-test-api-key-field, .monei-test-account-id-field' )
				.closest( 'tr' )
				.show();
			$( '.monei-live-api-key-field, .monei-live-account-id-field' )
				.closest( 'tr' )
				.hide();
		} else {
			$( '.monei-test-api-key-field, .monei-test-account-id-field' )
				.closest( 'tr' )
				.hide();
			$( '.monei-live-api-key-field, .monei-live-account-id-field' )
				.closest( 'tr' )
				.show();
		}
	}

	// Generic function to toggle description fields based on redirect mode
	function toggleDescriptionField( paymentMethod ) {
		// Credit Card gateway ID is 'monei', not 'monei_cc', so handle it specially
		const gatewayId =
			paymentMethod === 'cc' ? 'monei' : 'monei_' + paymentMethod;
		const redirectCheckbox = $( '#woocommerce_' + gatewayId + '_mode' );
		const descriptionField = $(
			'.monei-' + paymentMethod + '-description-field'
		);

		if ( redirectCheckbox.length ) {
			// If redirect mode checkbox exists, show/hide based on its state
			if ( redirectCheckbox.is( ':checked' ) ) {
				descriptionField.closest( 'tr' ).show();
			} else {
				descriptionField.closest( 'tr' ).hide();
			}
		} else {
			// If no redirect mode checkbox, always hide description
			descriptionField.closest( 'tr' ).hide();
		}
	}

	// Payment methods that have description fields (only for methods with both redirect and embedded modes)
	// MBWay and Multibanco are redirect-only, so their descriptions are always visible
	const paymentMethods = [ 'cc', 'paypal', 'bizum' ];

	// Initial call to set the correct fields on page load
	toggleApiKeyFields();
	paymentMethods.forEach( function ( method ) {
		toggleDescriptionField( method );
	} );

	// Bind the function to the change event of the selectors
	$( '#monei_apikey_mode' ).change( toggleApiKeyFields );
	$( '#monei-manual-keys-toggle' ).on( 'click', function ( event ) {
		event.preventDefault();
		manualKeysOpen = true;
		$( this ).closest( 'p' ).hide();
		toggleApiKeyFields();
	} );
	paymentMethods.forEach( function ( method ) {
		const gatewayId = method === 'cc' ? 'monei' : 'monei_' + method;
		$( '#woocommerce_' + gatewayId + '_mode' ).change( function () {
			toggleDescriptionField( method );
		} );
	} );
} );
