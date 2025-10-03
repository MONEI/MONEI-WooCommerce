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
 * Monei Bizum Gateway Settings.
 */
return apply_filters(
	'wc_monei_bizum_settings',
	array(
		'top_link'    => array(
			'title'       => '',
			'type'        => 'title',
			'description' => '<a href="' . $settings_link . '" class="button">' . __( 'Go to MONEI Api key Settings', 'monei' ) . '</a>',
			'id'          => 'bizum_monei_top_link',
		),
		'enabled'     => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Bizum by MONEI', 'monei' ),
			'default' => 'no',
		),
		'bizum_mode'  => array(
			'title'       => __( 'Use Redirect Flow', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'This will redirect the customer to the Hosted Payment Page.', 'monei' ),
			'default'     => 'no',
			'description' => sprintf( __( 'If disabled the Bizum button will be rendered directly on the checkout page. It is recommended to enable redirection in cases where Bizum payments do not function correctly.', 'monei' ) ),
		),
		'description' => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'This description is only displayed when using redirect mode. It will be shown to customers before they are redirected to the payment page.', 'monei' ),
			'default'     => __( 'Pay with Bizum. Powered by MONEI.', 'monei' ),
			'class'       => 'monei-bizum-description-field',
		),
		'bizum_style' => array(
			'title'       => __( 'Bizum Style', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'Configure in JSON format the style of the Bizum component. Documentation: ', 'monei' ) . '<a href="https://docs.monei.com/docs/monei-js/reference/#bizum-options" target="_blank">MONEI Bizum Style</a>',
			'default'     => '{"height": "42"}',
			'css'         => 'min-height: 80px;',
		),
		'title'       => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'The payment method title a user sees during checkout.', 'monei' ),
			'default'     => __( 'Bizum', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_logo'   => array(
			'title'       => __( 'Hide Logo', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method logo', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method logo in the checkout.', 'monei' ),
			'desc_tip'    => true,
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
	)
);
