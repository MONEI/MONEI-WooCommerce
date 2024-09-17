( function() {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    const { __ } = wp.i18n;
    const { Fragment, useEffect, useState } = wp.element;

    const MoneiContent = (props) => {
        const {  responseTypes } = props.emitResponse;
        const moneiData = wc.wcSettings.getSetting('monei_data');
        const isHostedWorkflow = moneiData.redirect === 'yes'
        const {onPaymentSetup, onCheckoutValidation, onCheckoutSuccess} = props.eventRegistration;
        let cardInput = null;
        let token = null;
        /**
         * Printing errors into checkout form.
         * @param error_string
         */
        const print_errors = (error_string ) => {
            console.log( 'Card input error: ', error_string );
            document.getElementById( 'monei-card-error' ).innerHTML = error_string
        }
        /**
         * Clearing form errors.
         */
        const clear_errors = () => {
            document.getElementById( 'monei-card-error' ).innerHTML = ''
        }
        if (isHostedWorkflow) {
            return (
                <div className="wc-block-components-text-input wc-block-components-address-form__email">
                    <p>{__('You will be redirected to the payment page', 'monei')}</p>
                </div>
            );
        }
        useEffect(() => {
            // We assume the MONEI SDK is already loaded via wp_enqueue_script on the backend.
            if (typeof monei !== 'undefined' && monei.CardInput) {
                initMoneiCard();
            } else {
                console.error('MONEI SDK is not available');
                }
            }, [] ); // Empty dependency array ensures this runs only once when the component mounts.

            /**
             * Initialize MONEI card input and handle token creation.
             */
            const initMoneiCard = () => {
                cardInput = monei.CardInput({
                    accountId: moneiData.accountId,
                    sessionId: moneiData.sessionId,
                    language: moneiData.language,
                    onChange( event ) {
                        if ( event.isTouched && event.error ) {
                            print_errors(event.error)
                        } else {
                            clear_errors()
                        }
                    },
                    onEnter() {
                        // Handle form submission when card details are entered
                        console.log('onEnter')
                        createMoneiToken();
                    },
                });
                cardInput.render( document.getElementById( 'monei-card-input' ) );
            };

            /**
             * Handle MONEI token creation when form is submitted.
             */
            const createMoneiToken = () => {

                // Create a token using the MONEI SDK
                return monei.createToken(cardInput)
                    .then(result => {
                        if (result.error) {
                            // Inform the user if there was an error
                            print_errors(result.error);
                            return null;  // Return null to indicate failure
                        } else {
                            console.log('create token', result.token)
                            // Set the token and attach it to the form
                            document.querySelector('#monei_payment_token').value = result.token;
                            token = result.token;
                            return result.token;  // Return the token for further use
                        }
                    })
                    .catch(error => {
                        // Handle any error in the promise chain
                        print_errors(error.message);
                        return null;  // Return null in case of error
                    });
            };

        // Hook into the validation process
        useEffect(() => {
            const unsubscribeValidation = onCheckoutValidation( () => {
                console.log('on validation')
                // If no token is available, create a fresh token
                if (!token) {
                    return createMoneiToken().then(freshToken => {
                        if (!freshToken) {
                            return {
                                errorMessage: __('MONEI token could not be generated.', 'monei'),
                            };
                        }
                        console.log('token in var after validation', token)
                        return true;  // Validation passed
                    });
                }

                return true;  // Validation passed (token already exists)
            });

            return () => {
                unsubscribeValidation();
            };
        }, [onCheckoutValidation]);

        // Hook into the payment setup
        useEffect(() => {
            const unsubscribePaymentSetup = onPaymentSetup(() => {
                // Get the token from the hidden input field
                let cardholderName = document.querySelector('#cardholder_name').value;

                // If no token is available, create a fresh token
                if (!token) {
                    return createMoneiToken().then(freshToken => {
                        // If the token is generated successfully
                        if (freshToken && freshToken.length) {
                            return {
                                type: responseTypes.SUCCESS,
                                meta: {
                                    paymentMethodData: {
                                        monei_payment_token: freshToken,
                                        monei_cardholder_name: cardholderName,
                                        monei_is_block_checkout: 'yes'
                                    },
                                },
                            };
                        }

                        // If the token generation failed
                        return {
                            type: 'error',
                            message: __('MONEI token could not be generated.', 'monei'),
                        };
                    });
                }
                console.log('token in paymentsetup', token)
                // Token is already available, proceed with setup
                return {
                    type: responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            monei_payment_token: token,
                            monei_cardholder_name: cardholderName,
                            monei_is_block_checkout: 'yes'
                        },
                    },
                };
            });

            return () => {
                unsubscribePaymentSetup();
            };
        }, [onPaymentSetup]);

        useEffect(() => {
            const unsubscribeSuccess = onCheckoutSuccess(({processingResponse}) => {
                const { paymentDetails } = processingResponse;
console.log('processing response')
                // Ensure we have the paymentId from the server
                if (paymentDetails && paymentDetails.paymentId) {
                    const paymentId = paymentDetails.paymentId;
                    console.log('payment id', paymentId)

                    // Retrieve the token from the hidden input field
                    const tokenValue = paymentDetails.token
                    console.log('token', tokenValue)
                    // Call monei.confirmPayment to complete the payment (with 3D Secure)
                    monei.confirmPayment({
                        paymentId: paymentId,
                        paymentToken: tokenValue,
                        paymentMethod: {
                            card: {
                                cardholderName: document.querySelector('#cardholder_name').value,
                            }
                        }
                    }).then(result => {
                        console.log('Payment confirmed:', result);
                        window.location.href = paymentDetails.completeUrl
                        // Handle success (3D Secure completed, etc.)
                        // You may also want to trigger further order processing here
                    }).catch(error => {
                        console.log('Error during payment confirmation:', error);
                        // Handle failure (failed 3D Secure, etc.)
                    });
                } else {
                    console.error('No paymentId found in paymentDetails');
                }

                // Return true to indicate that the checkout is successful
                return true;
            });

            return () => {
                unsubscribeSuccess();
            };
        }, [onCheckoutSuccess]);


        return (
            <Fragment>
                <div className="wc-block-components-text-input wc-block-components-address-form__email">
                    <input type="text" id="cardholder_name"
                           name="cardholder_name"
                           placeholder={__('Cardholder Name', 'monei')} required/>
                </div>

                <div id="monei-card-input"/>
                <input type="hidden" id="monei_payment_token" name="monei_payment_token" value=''/>
                <div id="monei-card-error"/>
            </Fragment>
    );
    }
    const MoneiAppleGoogleContent = (props) => {
        const {  responseTypes } = props.emitResponse;
        const moneiData = wc.wcSettings.getSetting('monei_data');
        const {onPaymentSetup, onCheckoutValidation, onCheckoutSuccess} = props.eventRegistration;
        let cardInput = null;
        let token = null;
        /**
         * Printing errors into checkout form.
         * @param error_string
         */
        const print_errors = (error_string ) => {
            console.log( 'Card input error: ', error_string );
            document.getElementById( 'monei-card-error' ).innerHTML = error_string
        }
        /**
         * Clearing form errors.
         */
        const clear_errors = () => {
            document.getElementById( 'monei-card-error' ).innerHTML = ''
        }
        useEffect(() => {
            // We assume the MONEI SDK is already loaded via wp_enqueue_script on the backend.
            if (typeof monei !== 'undefined' && monei.CardInput) {
                initMoneiCard();
            } else {
                console.error('MONEI SDK is not available');
            }
        }, [] ); // Empty dependency array ensures this runs only once when the component mounts.

        /**
         * Initialize MONEI card input and handle token creation.
         */
        const initMoneiCard = () => {

                if ( window.paymentRequest ) {
                    window.paymentRequest.close();
                }
                let paymentRequest = monei.PaymentRequest({
                    accountId: moneiData.accountId,
                    sessionId: moneiData.sessionId,
                    language: moneiData.language,
                    amount: parseInt(wc_monei_params.total),
                    currency: moneiData.currency,
                    onSubmit(result) {
                        console.log('submitting')
                    },
                    onError(error) {
                        console.log(error);
                    },
                });
                paymentRequest.render('#payment-request-container');

        };

        /**
         * Handle MONEI token creation when form is submitted.
         */
        const createMoneiToken = () => {

            // Create a token using the MONEI SDK
            return monei.createToken(cardInput)
                .then(result => {
                    if (result.error) {
                        // Inform the user if there was an error
                        print_errors(result.error);
                        return null;  // Return null to indicate failure
                    } else {
                        console.log('create token', result.token)
                        token = result.token;
                        return result.token;  // Return the token for further use
                    }
                })
                .catch(error => {
                    // Handle any error in the promise chain
                    print_errors(error.message);
                    return null;  // Return null in case of error
                });
        };

        // Hook into the validation process
        useEffect(() => {
            const unsubscribeValidation = onCheckoutValidation( () => {
                console.log('on validation')
                // If no token is available, create a fresh token
                if (!token) {
                    return createMoneiToken().then(freshToken => {
                        if (!freshToken) {
                            return {
                                errorMessage: __('MONEI token could not be generated.', 'monei'),
                            };
                        }
                        console.log('token in var after validation', token)
                        return true;  // Validation passed
                    });
                }

                return true;  // Validation passed (token already exists)
            });

            return () => {
                unsubscribeValidation();
            };
        }, [onCheckoutValidation]);

        // Hook into the payment setup
        useEffect(() => {
            const unsubscribePaymentSetup = onPaymentSetup(() => {
                // Get the token from the hidden input field
                let cardholderName = document.querySelector('#cardholder_name').value;

                // If no token is available, create a fresh token
                if (!token) {
                    return createMoneiToken().then(freshToken => {
                        // If the token is generated successfully
                        if (freshToken && freshToken.length) {
                            return {
                                type: responseTypes.SUCCESS,
                                meta: {
                                    paymentMethodData: {
                                        monei_payment_request_token: freshToken,
                                    },
                                },
                            };
                        }

                        // If the token generation failed
                        return {
                            type: 'error',
                            message: __('MONEI token could not be generated.', 'monei'),
                        };
                    });
                }
                console.log('token in paymentsetup', token)
                // Token is already available, proceed with setup
                return {
                    type: responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            monei_payment_request_token: freshToken,
                        },
                    },
                };
            });

            return () => {
                unsubscribePaymentSetup();
            };
        }, [onPaymentSetup]);


        return (
            <Fragment>
                <div id="payment-request-container"></div>
                <input type="hidden" id="monei_payment_token" name="monei_payment_token" value=''/>
                <div id="monei-card-error"/>
            </Fragment>
        );
    }
    /**
    * MONEI Payment Method React Component
    */
    const MoneiPaymentMethod = {
        name: 'monei',
        label: <Fragment>
                { __( 'Credit Card', 'monei' ) }
            </Fragment>,
        ariaLabel: __( 'MONEI Payment Gateway', 'monei' ),

        // React content to render on the checkout page
        content: <MoneiContent />,

        // Optional edit mode for the block editor
        edit:
            <Fragment>
                <div id="monei-card-input" />
                { __( 'MONEI Payment Form (Edit Mode)', 'monei' ) }
            </Fragment>,

        canMakePayment: () => true,
        supports: wc.wcSettings.getSetting( 'monei_data' ).supports,
    };
    const AppleGooglePaymentMethod = {
        name: 'monei_apple_google',
        label: <Fragment>
            { __( 'Apple/Google pay', 'monei' ) }
        </Fragment>,
        ariaLabel: __( 'Apple/Google Pay Payment Gateway', 'monei' ),
        content: <MoneiAppleGoogleContent/>,
        edit:  <Fragment>
            <div id="payment-request-container" />
            { __( 'MONEI Payment Form (Edit Mode)', 'monei' ) }
        </Fragment>,
        canMakePayment: () => true,
        supports: wc.wcSettings.getSetting( 'monei_data' ).supports,
    };
    registerPaymentMethod( MoneiPaymentMethod );
    console.log(wc.wcSettings.getSetting( 'monei_data' ).supports)
    registerPaymentMethod( AppleGooglePaymentMethod );
} )();
