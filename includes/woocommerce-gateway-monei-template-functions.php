<?php
/**
 * Functions related to templates.
 *
 * @author   Manuel Rodriguez
 * @category Core
 * @package  Woocommerce_Gateway_Monei/Functions
 * @version  5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @param $template_name
 * @param array $args
 * @param string $template_path
 * @param string $default_path
 */
function woocommerce_gateway_monei_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
    if ( $args && is_array( $args ) ) {
        extract( $args, EXTR_SKIP ); // Avoid overriding existing variables
    }

    // Locate the template
    $located = woocommerce_gateway_monei_locate_template( $template_name, $template_path, $default_path );

    // Validate the located template
    // Validate that the located file exists and is within the plugin's template directory
    $template_directory = trailingslashit( WP_PLUGIN_DIR . '/templates' );
    if ( ! $located || ! file_exists( $located ) || strpos( realpath( $located ), realpath( $template_directory ) ) !== 0 ) {
        _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> is not a valid or existing template.', esc_html( $template_name ) ), '1.0.0' );
        return;
    }

    // Trigger actions before including the template
    do_action( 'woocommerce_gateway_monei_before_template', $template_name, $template_path, $located, $args );

    // Include the validated template file
    include $located;

    // Trigger actions after including the template
    do_action( 'woocommerce_gateway_monei_after_template', $template_name, $template_path, $located, $args );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * @param $template_name
 * @param string $template_path
 * @param string $default_path
 *
 * @return mixed|void|null
 */
function woocommerce_gateway_monei_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = WC_Monei()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = WC_Monei()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template
	if ( ! $template ) {
		$template = $default_path . $template_name;
	}

	// Return what we found
	return apply_filters( 'woocommerce_gateway_monei_locate_template', $template, $template_name, $template_path );
}

/**
 *
 * @param $template_name
 * @param array $args
 * @param string $template_path
 * @param string $default_path
 *
 * @return false|string
 */
function woocommerce_gateway_monei_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	woocommerce_gateway_monei_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}

