<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Money Gateway Settings.
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
		'testmode'       => array(
			'title'       => __( 'Test mode', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable test mode', 'monei' ),
			'default'     => 'yes',
			'description' => sprintf( __( 'Select this option for the initial testing required by MONEI, deselect this option once you pass the required test phase and your production environment is active.', 'monei' ) ),
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
			'default'     => __( 'Pay with credit card. Powered by MONEI.', 'monei' ),
		),
		'hide_logo'        => array(
			'title'   => __( 'Hide Logo', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Hide payment method logo', 'monei' ),
			'default' => 'no',
			'description' => __( 'Hide Logo in checkout.', 'monei' ),
			'desc_tip'    => true,
		),
		'apikey'       => array(
			'title'       => __( 'API Key', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Account ID and API key in the test mode are different from the live (production) mode and can only be used for testing purposes.', 'monei' ),
			'desc_tip'    => true,
		),
		'tokenization'        => array(
			'title'   => __( 'Tokenization', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable tokenization', 'monei' ),
			'default' => 'no',
			'description' => __( 'Allow your customers to securely Save payment information in the account for faster checkout.', 'monei' ),
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

