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
 * Monei Paypal Gateway Settings.
 */
return apply_filters(
	'wc_monei_paypal_settings',
	array(
		'top_link'      => array(
			'title'       => '',
			'type'        => 'title',
			'description' => '<a href="' . $settings_link . '" class="button">' . __( 'Go to MONEI Api key Settings', 'monei' ) . '</a>',
			'id'          => 'paypal_monei_top_link',
		),
		'enabled'       => array(
			'title'   => __( 'Enable/Disable', 'monei' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable PayPal by MONEI', 'monei' ),
			'default' => 'no',
		),
		'title'         => array(
			'title'       => __( 'Title', 'monei' ),
			'type'        => 'text',
			'description' => __( 'The payment method title a user sees during checkout.', 'monei' ),
			'default'     => __( 'PayPal', 'monei' ),
			'desc_tip'    => true,
		),
		'description'   => array(
			'title'       => __( 'Description', 'monei' ),
			'type'        => 'textarea',
			'description' => __( 'The payment method description a user sees during checkout.', 'monei' ),
			'default'     => __( 'Pay with PayPal, you will be redirected to PayPal. Powered by MONEI.', 'monei' ),
		),
		'hide_logo'     => array(
			'title'       => __( 'Hide Logo', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Hide payment method logo', 'monei' ),
			'default'     => 'no',
			'description' => __( 'Hide payment method logo in the checkout.', 'monei' ),
			'desc_tip'    => true,
		),
		'pre-authorize' => array(
			'title'       => __( 'Pre-Authorize', 'monei' ),
			'type'        => 'checkbox',
			'label'       => __( 'Manually capture payments', 'monei' ),
			'description' => __( 'Place a hold on the funds when the customer authorizes the payment, but don’t capture the funds until later.<br>You can capture the payment changing order status to <strong>Completed</strong> or <strong>Processing</strong>.<br> You can cancel the Payment changing order to <strong>Cancelled</strong> or <strong>Refunded</strong>.', 'monei' ),
			'default'     => 'no',
		),
		'orderdo'       => array(
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
