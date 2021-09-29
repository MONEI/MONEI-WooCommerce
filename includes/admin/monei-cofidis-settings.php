<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Money Cofidis Gateway Settings.
 */
return apply_filters(
	'wc_monei_cofidis_settings',
	array(
		'enabled'        => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Cofidis by MONEI', 'monei' ),
			'default' => 'no',
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

