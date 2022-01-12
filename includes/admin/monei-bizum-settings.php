<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Monei Bizum Gateway Settings.
 */
return apply_filters(
	'wc_monei_bizum_settings',
	array(
		'enabled'        => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Bizum by MONEI', 'monei' ),
			'default' => 'no',
		),
		'title'          => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'The payment method title a user sees during checkout.', 'monei' ),
			'default'     => __( 'Bizum', 'monei' ),
			'desc_tip'    => true,
		),
		'description'    => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'The payment method description a user sees during checkout.', 'monei' ),
			'default'     => __( 'Pay with Bizum, you will be redirected to Bizum. Powered by MONEI', 'monei' ),
		),
		'hide_logo'        => array(
			'title'   => __( 'Hide Logo', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Hide payment method logo', 'monei' ),
			'default' => 'no',
			'description' => __( 'Hide payment method logo in the checkout.', 'monei' ),
			'desc_tip'    => true,
		),
		'apikey'       => array(
			'title'       => __( 'API Key', 'monei' ),
			'type'        => 'text',
			'description' => __( 'You can find your API key in <a href="https://dashboard.monei.com/settings/api" target="_blank">MONEI Dashboard</a>.<br/> Account ID and API key in the test mode are different from the live<br/> (production) mode and can only be used for testing purposes.', 'monei' ),
			'desc_tip'    => 'no',
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

