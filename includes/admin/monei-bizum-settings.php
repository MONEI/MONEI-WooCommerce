<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Money Bizum Gateway Settings.
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
			'description' => __( 'This controls the title which the user sees during checkout.', 'monei' ),
			'default'     => __( 'Bizum (MONEI)', 'monei' ),
			'desc_tip'    => true,
		),
		'description'    => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'This controls the description which the user sees during checkout.', 'monei' ),
			'default'     => __( 'Pay via Bizum; you will be redirected to MONEI to pay.', 'monei' ),
		),
		'logo'           => array(
			'title'       => __( 'Logo', 'monei' ),
			'type'        => 'text',
			'description' => __( 'Add link to image logo.', 'monei' ),
			'desc_tip'    => true,
		),
	)
);

