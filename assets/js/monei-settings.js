jQuery( document ).ready( function ( $ ) {
	// Function to toggle API key fields
	function toggleApiKeyFields() {
		const mode = $( '#monei_apikey_mode' ).val();
		if ( mode === 'test' ) {
			$( '.monei-test-api-key-field' ).closest( 'tr' ).show();
			$( '.monei-live-api-key-field' ).closest( 'tr' ).hide();
		} else {
			$( '.monei-test-api-key-field' ).closest( 'tr' ).hide();
			$( '.monei-live-api-key-field' ).closest( 'tr' ).show();
		}
	}

	// Generic function to toggle description fields based on redirect mode
	function toggleDescriptionField( paymentMethod ) {
		// Credit Card gateway ID is 'monei', not 'monei_cc', so handle it specially
		const gatewayId =
			paymentMethod === 'cc' ? 'monei' : 'monei_' + paymentMethod;
		const redirectCheckbox = $(
			'#woocommerce_' + gatewayId + '_' + paymentMethod + '_mode'
		);
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
	paymentMethods.forEach( function ( method ) {
		const gatewayId = method === 'cc' ? 'monei' : 'monei_' + method;
		$( '#woocommerce_' + gatewayId + '_' + method + '_mode' ).change(
			function () {
				toggleDescriptionField( method );
			}
		);
	} );
} );
