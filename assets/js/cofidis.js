(function( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on(
		'updated_checkout',
		function(e, data) {
			console.log(e, data);
		}
	);

	var cofidis_widget = {
		$checkout_form: $( 'form.woocommerce-checkout' ),
		$codifis_widget_container: '#cofidis_widget',
		account_id: wc_monei_params.account_id,
		total: wc_monei_params.total,
		init: function() {

			var cofidisWidget = monei.CofidisWidget({
				accountId: cofidis_widget.account_id, // Your MONEI Account ID
				amountInt: parseInt( cofidis_widget.total ), // The amount you want to display in cents
				language: 'en', // Language, supported en, es

				// You can pass additional styles
				style: {
				base: {
					textAlign: 'right',
						fontFamily: 'Helvetica,"Helvetica Neue",Arial,"Lucida Grande",sans-serif'
					}
				}
			});

			// Render Cofidis Widget to the container element
			cofidisWidget.render( '#cofidis_widget' );
			// Assign a global variable to cofidisWidget so you can update props later
			window.cofidisWidget = cofidisWidget;
		}
	}

	$(
		function() {
			cofidis_widget.init();
		}
	);

})( jQuery );
