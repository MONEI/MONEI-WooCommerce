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
 * Monei Multibanco Gateway Settings.
 */
return apply_filters(
	'wc_monei_multibanco_settings',
	array(
		'top_link'    => array(
			'title'       => '',
			'type'        => 'title',
			'description' => '<a href="' . $settings_link . '" class="button">' . __( 'Go to MONEI Api key Settings', 'monei' ) . '</a>',
			'id'          => 'multibanco_monei_top_link',
		),
		'enabled'     => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Multibanco by MONEI', 'monei' ),
			'default' => 'no',
		),
		'description' => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'Payment method description shown to customers during checkout.', 'monei' ),
			'default'     => __( 'Pay with Multibanco. Powered by MONEI.', 'monei' ),
			'class'       => 'monei-multibanco-description-field',
		),
		'title'       => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'The payment method title a user sees during checkout.', 'monei' ),
			'default'     => __( 'Multibanco', 'monei' ),
			'desc_tip'    => true,
		),
		'hide_title'  => array(
			'title'       => __( 'Hide Title', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method title', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method title in the checkout, showing only the logo.', 'monei' ),
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
