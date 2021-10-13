<?php
/**
 * WooCommerce MONEI Gateway
 *
 * @package WooCommerce MONEI Gateway
 * @author Manuel Rodriguez
 * @copyright 2020-2020 MONEI
 * @license GPL-3.0+
 *
 * Plugin Name: WooCommerce MONEI Gateway
 * Plugin URI: https://wordpress.org/plugins/monei/
 * Description: Accept Credit Card / PayPal / Bizum / Cofidis payments through MONEI.
 * Version: 5.3.1
 * Author: MONEI
 * Author URI: https://www.monei.com/
 * Tested up to: 5.8
 * WC requires at least: 3.0
 * WC tested up to: 5.5.2
 * Text Domain: monei
 * Domain Path: /languages/
 * Copyright: (C) 2021 MONEI.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'MONEI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MONEI_PLUGIN_FILE', __FILE__ );

require_once 'class-woocommerce-gateway-monei.php';
function WC_Monei() {
	return Woocommerce_Gateway_Monei::instance();
}

WC_Monei();

