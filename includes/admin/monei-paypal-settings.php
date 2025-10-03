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

/** Monei Paypal Gateway Settings. */
return apply_filters(
	'wc_monei_paypal_settings',
	array(
		'top_link'     => array(
			'title'       => '',
			'type'        => 'title',
			'description' => '<a href="' . $settings_link . '" class="button">' . __( 'Go to MONEI Api key Settings', 'monei' ) . '</a>',
			'id'          => 'paypal_monei_top_link',
		),
		'enabled'      => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable PayPal by MONEI', 'monei' ),
			'default' => 'no',
		),
		'paypal_mode'  => array(
			'title'       => __( 'Use Redirect Flow', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'This will redirect the customer to the Hosted Payment Page.', 'monei' ),
			'default'     => 'no',
			'description' => sprintf( __( 'If disabled the PayPal button will be rendered directly on the checkout page. It is recommended to enable redirection in cases where PayPal payments do not function correctly.', 'monei' ) ),
		),
		'title'        => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'The payment method title a user sees during checkout.', 'monei' ),
			'default'     => __( 'PayPal', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_title'   => array(
			'title'       => __( 'Hide Title', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method title', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method title in the checkout, showing only the logo.', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_logo'    => array(
			'title'       => __( 'Hide Logo', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method logo', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method logo in the checkout.', 'monei' ),
			'desc_tip'    => true,
		),
		'description'  => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'This description is only displayed when using redirect mode. It will be shown to customers before they are redirected to the payment page.', 'monei' ),
			'default'     => __( 'You will be redirected to PayPal to complete the payment. Powered by MONEI.	', 'monei' ),
			'class'       => 'monei-paypal-description-field',
		),
		'paypal_style' => array(
			'title'       => __( 'PayPal Style', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'Configure in JSON format the style of the PayPal component. Documentation: ', 'monei' ) . '<a href="https://docs.monei.com/docs/monei-js/reference/#paypal-options" target="_blank">MONEI PayPal Style</a>',
			'default'     => '{"height": "50px", "disableMaxWidth": true}',
			'css'         => 'min-height: 80px;',
		),
	)
);
