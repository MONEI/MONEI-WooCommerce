(function( $ ) {
	'use strict';

	// Checkout form.
	$( document.body ).on(
		'updated_checkout',
		function(e, data) {

			// Update cofidis_widget.total on every updated_checkout event.
			if ( 'object' === typeof( data ) && data.fragments && data.fragments[ 'monei_new_total' ] ) {
				cofidis_widget.total = data.fragments[ 'monei_new_total' ];
				cofidis_widget.widget_update_props();

				// On each updated_checkout check new total, and hide or show cofidis method depending on this.
				if ( data.fragments[ 'monei_new_total' ] < 7500 || data.fragments[ 'monei_new_total' ] > 100000 ) {
					cofidis_widget.hide_cofidis();
				} else {
					cofidis_widget.show_cofidis();
				}
			}

			if ( cofidis_widget.is_cofidis_selected() ) {
				cofidis_widget.on_payment_selected();
			}
		}
	);

	var cofidis_widget = {
		$checkout_form: $( 'form.woocommerce-checkout' ),
		$codifis_widget_container: '#cofidis_widget',
		account_id: wc_monei_cofidis_params.account_id,
		total: wc_monei_cofidis_params.total,
		init_counter: 0,
		init: function() {
			// Track changes in form.
			this.$checkout_form.on( 'change', this.on_change );
			cofidis_widget.init_cofidis_widget();
		},
		init_cofidis_widget: function() {
			var cofidisWidget = monei.CofidisWidget({
				accountId: cofidis_widget.account_id, // Your MONEI Account ID
				amountInt: parseInt( cofidis_widget.total ), // The amount you want to display in cents
				language: cofidis_widget.lang, // Language, supported en, es
				style: {
					base: {
						color: '#6D6D6D'
					},
					label: {
						fontWeight: 'normal',
						color: '#333'
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
		widget_update_props: function( new_total ) {
			//window.cofidisWidget.updateProps( {amountInt: parseInt( cofidis_widget.total )} );
			// Instead of updating props, we close the instance and create a new one. We avoid weird bugs
			// where the widget doesn't show anymore.
			if ( window.cofidisWidget ) {
				window.cofidisWidget.close();
				cofidis_widget.init_cofidis_widget();
				// This is covered by on_payment_selected, we don't want to render twice.
				if ( 0 !== cofidis_widget.init_counter  ) {
					cofidis_widget.widget_render();
				}
			}
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
				if ( 0 === cofidis_widget.init_counter  ) {
					// Render only when Cofidis is selected.
					cofidis_widget.widget_render();
					cofidis_widget.init_counter++;
				}
			}
		},
		is_cofidis_selected: function() {
			return $( '#payment_method_monei_cofidis' ).is( ':checked' );
		},
		hide_cofidis: function() {
			$( 'li.payment_method_monei_cofidis' ).hide();
		},
		show_cofidis: function() {
			$( 'li.payment_method_monei_cofidis' ).show();
		},
	}

	$(
		function() {
			cofidis_widget.init();
		}
	);

})( jQuery );
