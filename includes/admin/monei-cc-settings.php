<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Monei Gateway Settings.
 */
return apply_filters(
	'wc_monei_settings',
	array(
		'enabled'        => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Credit Card by MONEI', 'monei' ),
			'default' => 'no',
		),
        'cc_mode'       => array(
            'title'       => __( 'Use Redirect Flow', 'monei' ),
            'type'        => 'checkbox',
            'label'       => __( 'This will redirect the customer to the Hosted Payment Page.', 'monei' ),
            'default'     => 'yes',
            'description' => sprintf( __( 'If disabled the credit card input will be rendered directly on the checkout page.', 'monei' ) ),
        ),
		'apple_google_pay' => array(
			'title'       => __( 'Apple Pay / Google Pay', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable Apple Pay / Google Pay buttons.', 'monei' ),
			'default'     => 'no',
			'description' => sprintf( __( 'Customers see Google Pay or Apple Pay button, depending on what their device and browser combination supports. By using Apple Pay, you agree to <a href="https://developer.apple.com/apple-pay/acceptable-use-guidelines-for-websites/" target="_blank">Apple\'s terms of service</a>. (Apple Pay domain verification is performed automatically in live mode)', 'monei' ) ),
		),
		'testmode'       => array(
			'title'       => __( 'Test mode', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable test mode', 'monei' ),
			'default'     => 'yes',
			'description' => sprintf( __( 'Place the payment gateway in test mode using test API key.', 'monei' ) ),
		),
		'title'          => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'The payment method title a user sees during checkout.', 'monei' ),
			'default'     => __( 'Credit Card', 'monei' ),
			'desc_tip'    => true,
		),
		'description'    => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'The payment method description a user sees during checkout.', 'monei' ),
			'default'     => __( 'Pay with credit card, you will be redirected to MONEI.', 'monei' ),
		),
		'hide_logo'        => array(
			'title'   => __( 'Hide Logo', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Hide payment method logo', 'monei' ),
			'default' => 'no',
			'description' => __( 'Hide payment method logo in the checkout.', 'monei' ),
			'desc_tip'    => true,
		),
        'accountid'       => array(
            'title'       => __( 'Account ID', 'monei' ) . ' <span class="required">*</span>',
            'type'        => 'text',
            'description' => __( 'Account ID', 'monei' ),
            'required'    => true,
            'desc_tip'    => true,
        ),
		'apikey'       => array(
			'title'       => __( 'API Key', 'monei' ) . ' <span class="required">*</span>',
			'type'        => 'text',
			'description' => __( 'You can find your API key in <a href="https://dashboard.monei.com/settings/api" target="_blank">MONEI Dashboard</a>.<br/> Account ID and API key in the test mode are different from the live<br/> (production) mode and can only be used for testing purposes.', 'monei' ),
			'desc_tip'    => 'no',
		),
		'tokenization'        => array(
			'title'   => __( 'Saved cards', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable payments via saved cards', 'monei' ),
			'default' => 'no',
			'description' => __( 'If enabled, customers will be able to pay with a saved card during checkout. Card details are saved on MONEI servers, not on your store.', 'monei' ),
			'desc_tip'    => true,
		),
		'pre-authorize'        => array(
			'title'   => __( 'Pre-Authorize', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Manually capture payments', 'monei' ),
			'description' => __( 'Place a hold on the funds when the customer authorizes the payment, but don’t capture the funds until later.<br>You can capture the payment changing order status to <strong>Completed</strong> or <strong>Processing</strong>.<br> You can cancel the Payment changing order to <strong>Cancelled</strong> or <strong>Refunded</strong>.', 'monei' ),
			'default' => 'no',
		),
		'orderdo'     => array(
			'title'       => __( 'What to do after payment?', 'monei' ),
			'type'        => 'select',
			'description' => __( 'Chose what to do after the customer pay the order.', 'monei' ),
			'default'     => 'processing',
			'options'     => array(
				'processing' => __( 'Mark as Processing (default & recommended)', 'monei' ),
				'completed'  => __( 'Mark as Complete', 'monei' ),
			),
		),
		'debug'          => array(
			'title'       => __( 'Debug Log', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable logging', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Log MONEI events, such as notifications requests, inside <code>WooCommerce > Status > Logs > Select MONEI Logs</code>', 'monei' ),
		),
	)
);

