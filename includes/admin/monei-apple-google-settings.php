<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings_link = esc_url(
	admin_url(
		add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'monei_settings',
			),
			'admin.php'
		)
	)
);

/**
 * Monei Apple/Google Gateway Settings.
 */
return apply_filters(
	'wc_monei_apple_google_settings',
	array(
		'top_link' => array(
			'title'       => '',
			'type'        => 'title',
			'description' => '<a href="' . $settings_link . '" class="button">' . __( 'Go to MONEI Api key Settings', 'monei' ) . '</a>',
			'id'          => 'apple_google_monei_top_link',
		),
		'enabled'  => array(
			'title'       => __( 'Enable/Disable', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable Apple Pay / Google Pay buttons.', 'monei' ),
			'default'     => 'no',
			'description' => sprintf( __( 'Customers see Google Pay or Apple Pay button, depending on what their device and browser combination supports. By using Apple Pay, you agree to <a href="https://developer.apple.com/apple-pay/acceptable-use-guidelines-for-websites/" target="_blank">Apple\'s terms of service</a>. (Apple Pay domain verification is performed automatically in live mode)', 'monei' ) ),
		),
	)
);
