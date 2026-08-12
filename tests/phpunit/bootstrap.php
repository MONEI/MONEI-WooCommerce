<?php
/**
 * PHPUnit bootstrap.
 *
 * Deliberately does NOT load WordPress. This suite covers pure helpers only —
 * anything that needs a WordPress runtime belongs in the manual matrix or in a
 * separate integration suite.
 *
 * @package Monei
 */

// Plugin files open with `if ( ! defined( 'ABSPATH' ) ) { exit; }`. Without this
// the first `use` of such a class kills the PHPUnit process with a zero exit code
// and no failure — a silently passing suite. Nothing in a no-WordPress suite reads
// the value, only its presence.
define( 'ABSPATH', dirname( __DIR__, 4 ) . '/' );

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// tests/stubs/ is deliberately NOT loaded here. Those files are declaration-only
// PHPStan input: woocommerce-subscriptions-stubs.php extends WC_Order, which lives
// in the WooCommerce stubs, which in turn extend WordPress stubs. Loading that chain
// at runtime is both fatal and pointless for pure helpers. A helper that genuinely
// needs a WooCommerce symbol does not belong in this suite.
