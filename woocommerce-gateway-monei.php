<?php
/**
 * MONEI Payments for WooCommerce
 *
 * @package   MONEI Payments
 * @author    MONEI
 * @copyright 2020-2020 MONEI
 * @license   GPL-2.0+
 *
 * Plugin Name: MONEI Payments for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/monei/
 * Description: Accept Card, Apple Pay, Google Pay, Bizum, PayPal and many more payment methods in your store.
 * Version: 5.8.13
 * Author: MONEI
 * Author URI: https://www.monei.com/
 * Tested up to: 6.6
 * WC requires at least: 3.0
 * WC tested up to: 9.2
 * Requires PHP: 7.2
 * Text Domain: monei
 * Domain Path: /languages/
 * Copyright: (C) 2021 MONEI.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'MONEI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MONEI_PLUGIN_FILE', __FILE__ );

/**
 * Add compatibility with WooCommerce HPOS.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

require_once 'class-woocommerce-gateway-monei.php';
function WC_Monei() {
    return Woocommerce_Gateway_Monei::instance();
}

WC_Monei();

