jQuery( document ).ready(
	function ($) {
		// Function to toggle API key fields
		function toggleApiKeyFields() {
			var mode = $( '#monei_apikey_mode' ).val();
			if (mode === 'test') {
				$( '.monei-test-api-key-field' ).closest( 'tr' ).show();
				$( '.monei-live-api-key-field' ).closest( 'tr' ).hide();
			} else {
				$( '.monei-test-api-key-field' ).closest( 'tr' ).hide();
				$( '.monei-live-api-key-field' ).closest( 'tr' ).show();
			}
		}

		// Initial call to set the correct fields on page load
		toggleApiKeyFields();

		// Bind the function to the change event of the selector
		$( '#monei_apikey_mode' ).change( toggleApiKeyFields );
	}
);