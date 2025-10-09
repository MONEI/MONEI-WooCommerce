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

/** Apple Google Gateway Settings. */
return apply_filters(
	'wc_monei_apple_google_settings',
	array(
		'top_link'              => array(
			'title'       => '',
			'type'        => 'title',
			'description' => '<a href="' . $settings_link . '" class="button">' . __( 'Go to MONEI API Key Settings', 'monei' ) . '</a>',
			'id'          => 'cc_monei_top_link',
		),
		'enabled'               => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Apple Pay and Google Pay by MONEI', 'monei' ),
			'default' => 'no',
		),
		'title'                 => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Generic payment method name shown in order emails, order history, and admin areas. Not displayed on checkout (checkout shows Apple Pay or Google Pay based on device).', 'monei' ),
			'default'     => __( 'Apple Pay / Google Pay', 'monei' ),
			'desc_tip'    => true,
		),
		'apple_pay_title'       => array(
			'title'       => __( 'Apple Pay Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Title shown on checkout page for Apple devices (iPhone, iPad, Mac with Safari).', 'monei' ),
			'default'     => __( 'Apple Pay', 'monei' ),
			'desc_tip'    => true,
		),
		'google_pay_title'      => array(
			'title'       => __( 'Google Pay Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Title shown on checkout page for non-Apple devices (Android, Chrome, etc).', 'monei' ),
			'default'     => __( 'Google Pay', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_title'            => array(
			'title'       => __( 'Hide Title', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method title', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method title in the checkout, showing only the logo.', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_logo'             => array(
			'title'       => __( 'Hide Logo', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method logo', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method logo in the checkout.', 'monei' ),
			'desc_tip'    => true,
		),
		'payment_request_style' => array(
			'title'       => __( 'Apple Pay / Google Pay Style', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'Configure in JSON format the style of the Apple Pay / Google Pay component. Documentation: ', 'monei' ) . '<a href="https://docs.monei.com/docs/monei-js/reference/#paymentrequest-options" target="_blank">MONEI Payment Request Style</a>',
			'default'     => '{"height": "50px"}',
			'css'         => 'min-height: 80px;',
		),
	)
);
