(function( $ ) {
    'use strict';

    // On Checkout form.
    $( document.body ).on(
        'updated_checkout', function() {
            if ( wc_monei_form.is_monei_selected ) {
                wc_monei_form.init_checkout_monei();
                console.log('updated_checkout monei selected');
            }
        }
    );

    var wc_monei_form = {
        $checkout_form: $( 'form.woocommerce-checkout' ),
        $add_payment_form: $( 'form#add_payment_method' ),
        is_checkout: false,
        form: null,
        submitted: false,
        init: function() {
            // Checkout Page
            if ( this.$checkout_form.length ) {
                this.form        = this.$checkout_form;
                this.is_checkout = true;
            }

            if (this.form) {
                this.form.on( 'change', this.on_change );
            }
        },
        on_change: function() {
        },
        is_monei_selected: function() {
            return $( '#payment_method_monei_card_input_component' ).is( ':checked' );
        },
        is_monei_saved_token_selected: function() {
            return ( $( '#payment_method_monei_card_input_component' ).is( ':checked' ) && ( $( 'input[name="wc-monei_card_input_component-new-payment-method"]' ).is( ':checked' ) && 'new' !== $( 'input[name="wc-monei_card_input_component-new-payment-method"]:checked' ).val() ) );
        },
        init_checkout_monei: function() {
            var container = document.getElementById('card-input');
            var errorText = document.getElementById('card-error');

            // Create an instance of the Card Input using payment_id.
            var cardInput = monei.CardInput({
                accountId: 'f3d6f2d6-4cb3-4fa4-8ae7-5c514913c0a5',//wc_monei_params.account_id,
                sessionId: '1',//wc_monei_params.session_id,
                onChange: function (event) {
                    // Handle real-time validation errors.
                    if (event.isTouched && event.error) {
                        container.classList.add('is-invalid');
                        errorText.innerText = event.error;
                    } else {
                        container.classList.remove('is-invalid');
                        errorText.innerText = '';
                    }
                }
            });
            cardInput.render(container);
            //
        },
        render_monei_form: function() {
            // Render an instance of the Card Input into the `card_input` <div>.


        },
        submit_form: function() {
        },
        block_form: function() {
        },
        unblock_form: function() {
        },
        get_form: function() {
            return this.form;
        }
    };

    $(
        function() {
            wc_monei_form.init();
        }
    );

})( jQuery );
