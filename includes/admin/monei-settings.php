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
			'label'   => __( 'Enable MONEI', 'monei' ),
			'default' => 'no',
		),
		'testmode'       => array(
			'title'       => __( 'Running in test mode', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Running in test mode', 'monei' ),
			'default'     => 'yes',
			'description' => sprintf( __( 'Select this option for the initial testing required by MONEI, deselect this option once you pass the required test phase and your production environment is active.', 'monei' ) ),
		),
		'title'          => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'monei' ),
			'default'     => __( 'Card (MONEI)', 'monei' ),
			'desc_tip'    => true,
		),
		'description'    => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'This controls the description which the user sees during checkout.', 'monei' ),
			'default'     => __( 'Pay via MONEI; you can pay with your credit card.', 'monei' ),
		),
		'logo'           => array(
			'title'       => __( 'Logo', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Add link to image logo.', 'monei' ),
			'desc_tip'    => true,
		),
		'commercename'   => array(
			'title'       => __( 'Shop Name', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Shop Name', 'monei' ),
			'desc_tip'    => true,
		),
		'accountid'       => array(
			'title'       => __( 'Account ID', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Account ID', 'monei' ),
			'desc_tip'    => true,
		),
		'apikey'       => array(
			'title'       => __( 'API Key', 'monei' ),
			'type'        => 'text',
			'description' => __( 'API Key', 'monei' ),
		),
		'tokenization'        => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Tokenization', 'monei' ),
			'default' => 'no',
		),
		'pre-authorize'        => array(
			'title'   => __( 'Pre-Authorize', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Pre-Authorize payment instead of Capture', 'monei' ),
			'description' => __( 'If checked, gateway will issue a <strong>pre-authorization</strong>, that will need to be captured later manually. If unchecked we <strong>capture</strong> payments by default. <br>You can capture the payment changing order status to <strong>Completed</strong> or <strong>Processing</strong>.<br> You can cancel the Payment changing order to <strong>Cancelled</strong> or <strong>Refunded</strong>.', 'monei' ),
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
			'description' => __( 'Log MONEY events, such as notifications requests, inside <code>WooCommerce > Status > Logs > Select MONEI Logs</code>', 'monei' ),
		),
	)
);

