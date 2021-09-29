(function( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on(
		'updated_checkout',
		function(e, data) {
			console.log(data);

			if ( cofidis_widget.is_cofidis_selected() ) {
				cofidis_widget.widget_render();
				// Update Cofidis widget with new total.
				if ( 'object' === typeof( data ) && data.fragments && data.fragments[ 'monei_new_total' ] ) {
					cofidis_widget.widget_update( data.fragments[ 'monei_new_total' ] );
				}
			}
		}
	);

	var cofidis_widget = {
		$checkout_form: $( 'form.woocommerce-checkout' ),
		$codifis_widget_container: '#cofidis_widget',
		account_id: wc_monei_params.account_id,
		total: wc_monei_params.total,
		init_counter: 0,
		init: function() {
			// Track changes in form.
			this.$checkout_form.on( 'change', this.on_change );

			var cofidisWidget = monei.CofidisWidget({
				accountId: cofidis_widget.account_id, // Your MONEI Account ID
				amountInt: parseInt( cofidis_widget.total ), // The amount you want to display in cents
				language: cofidis_widget.lang, // Language, supported en, es

				// You can pass additional styles
				style: {
					base: {
						//textAlign: 'right',
						//fontFamily: 'Helvetica,"Helvetica Neue",Arial,"Lucida Grande",sans-serif'
					}
				}
			});
			// Assign a global variable to cofidisWidget so you can update props later
			window.cofidisWidget = cofidisWidget;
		},
		widget_render: function() {
			// Render Cofidis Widget to the container element
			window.cofidisWidget.render( '#cofidis_widget' );
		},
		widget_update: function( new_total ) {
			window.cofidisWidget.updateProps({amountInt: parseInt( new_total )});
		},
		on_change: function() {
			$( "[name='payment_method']" ).on(
				'change',
				function() {
					cofidis_widget.on_payment_selected();
				}
			);
		},
		on_payment_selected() {
			if ( cofidis_widget.is_cofidis_selected() ) {
				if ( 0 === cofidis_widget.init_counter ) {
					cofidis_widget.widget_render();
					cofidis_widget.init_counter++;
				}
			}
		},
		is_cofidis_selected: function() {
			return $( '#payment_method_monei_cofidis' ).is( ':checked' );
		},
	}

	$(
		function() {
			cofidis_widget.init();
		}
	);

})( jQuery );
