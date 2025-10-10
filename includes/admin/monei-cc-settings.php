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

/** Monei Gateway Settings. */
return apply_filters(
	'wc_monei_cc_settings',
	array(
		'top_link'         => array(
			'title'       => '',
			'type'        => 'title',
			'description' => '<a href="' . $settings_link . '" class="button">' . __( 'Go to MONEI API Key Settings', 'monei' ) . '</a>',
			'id'          => 'cc_monei_top_link',
		),
		'enabled'          => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Credit Card by MONEI', 'monei' ),
			'default' => 'no',
		),
		'cc_mode'          => array(
			'title'       => __( 'Use Redirect Flow', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'This will redirect the customer to the Hosted Payment Page.', 'monei' ),
			'default'     => 'no',
			'description' => __( 'If disabled the credit card input will be rendered directly on the checkout page. It is recommended to enable redirection in cases where card payments do not function correctly.', 'monei' ),
		),
		'title'            => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'The payment method title a user sees during checkout.', 'monei' ),
			'default'     => __( 'Credit Card', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_title'       => array(
			'title'       => __( 'Hide Title', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method title', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method title in the checkout, showing only the logo.', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_logo'        => array(
			'title'       => __( 'Hide Logo', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method logo', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method logo in the checkout.', 'monei' ),
			'desc_tip'    => true,
		),
		'description'      => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'This description is only displayed when using redirect mode. It will be shown to customers before they are redirected to the payment page.', 'monei' ),
			'default'     => __( 'You will be redirected to Credit Card to complete the payment. Powered by MONEI.', 'monei' ),
			'class'       => 'monei-cc-description-field',
		),
		'card_input_style' => array(
			'title'       => __( 'Card Input Style', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'Configure in JSON format the style of the Card Input component. Documentation: ', 'monei' ) . '<a href="https://docs.monei.com/docs/monei-js/reference/#cardinput-style-object" target="_blank">MONEI Card Input Style</a>',
			'default'     => '{"base": {"height": "50px"}, "input": {"background": "none"}}',
			'css'         => 'min-height: 80px;',
		),
		'tokenization'     => array(
			'title'       => __( 'Saved cards', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Enable payments via saved cards', 'monei' ),
			'default'     => 'no',
			'description' => __( 'If enabled, customers will be able to pay with a saved card during checkout. Card details are saved on MONEI servers, not on your store.', 'monei' ),
			'desc_tip'    => true,
		),
	)
);
