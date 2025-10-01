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
 * Version: 6.4.0
 * Author: MONEI
 * Author URI: https://www.monei.com/
 * Tested up to: 6.8
 * WC requires at least: 3.0
 * WC tested up to: 9.8
 * Requires PHP: 7.2
 * Text Domain: monei
 * Domain Path: /languages/
 * Copyright: (C) 2021 MONEI.
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'MONEI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MONEI_PLUGIN_FILE', __FILE__ );
require_once __DIR__ . '/vendor/autoload.php';
/**
 * Add compatibility with WooCommerce HPOS and cart checkout blocks
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables',  __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );
/**
 * Remove transients for payment methods on activation
 *
 * @return void
 */
function delete_payment_methods_transients() {
    global $wpdb;

    // Delete transients that match the pattern
    $result = $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_payment_methods_%' 
         OR option_name LIKE '_transient_timeout_payment_methods_%'"
    );
    if ($result === false) {
        error_log('MONEI: Failed to delete payment method transients');
    }
}

/**
 * Remove transients for payment methods on update of the plugin
 *
 * @param $upgrader_object
 * @param $options
 * @return void
 */
function delete_payment_methods_transients_on_update($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        if (isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin == plugin_basename(__FILE__)) {
                    delete_payment_methods_transients();
                    break;
                }
            }
        }
    }
}

register_activation_hook(__FILE__, 'delete_payment_methods_transients');

add_action('upgrader_process_complete', 'delete_payment_methods_transients_on_update', 10, 2);

require_once 'class-woocommerce-gateway-monei.php';
function WC_Monei() {
    return Woocommerce_Gateway_Monei::instance();
}

WC_Monei();

