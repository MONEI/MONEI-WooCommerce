<?php
/**
 * Express checkout on the checkout page and in the Checkout block.
 *
 * There the wallet result fills the checkout form, and WooCommerce places the
 * order from the form. PayPal returns the payer's address with only a country,
 * and the classic form posts the shipping method still selected on the page.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\express\ExpressCheckoutAjaxHandler;
use PHPUnit\Framework\TestCase;

class ExpressCheckoutPageTest extends TestCase {

	use SourceCode;

	const HANDLER = 'src/Services/express/ExpressCheckoutAjaxHandler.php';

	public function test_wallet_shipping_pick_replaces_the_page_method() {
		$data = ExpressCheckoutAjaxHandler::with_shipping_option(
			array(
				'shipping_method' => array( 'free_shipping:1' ),
				'payment_method'  => 'monei_paypal',
			),
			'flat_rate:2'
		);

		$this->assertSame( array( 'flat_rate:2' ), $data['shipping_method'] );
		$this->assertSame( 'monei_paypal', $data['payment_method'] );
	}

	public function test_other_packages_keep_their_posted_methods() {
		// WooCommerce validates one method per package, so dropping package 1 fails the checkout.
		$data = ExpressCheckoutAjaxHandler::with_shipping_option(
			array( 'shipping_method' => array( 'free_shipping:1', 'local_pickup:3' ) ),
			'flat_rate:2'
		);

		$this->assertSame( array( 'flat_rate:2', 'local_pickup:3' ), $data['shipping_method'] );
	}

	public function test_wallet_callbacks_remember_the_rate_the_checkout_falls_back_on() {
		// The sheet does not always report a single option it selected itself.
		$code = $this->code( self::HANDLER );

		$this->assertSame( 2, substr_count( $code, '$this->remember_wallet_rate(' ), 'get_shipping_options and update_shipping_method' );
		$this->assertStringContainsString( 'WC()->session->get(self::WALLET_RATE_KEY,', $code );
	}

	public function test_no_wallet_pick_keeps_the_page_method() {
		$data = array( 'shipping_method' => array( 'free_shipping:1' ) );

		$this->assertSame( $data, ExpressCheckoutAjaxHandler::with_shipping_option( $data, '' ) );
	}

	public function test_checkout_hooks_are_registered() {
		$code = $this->code( self::HANDLER );

		$this->assertStringContainsString( "add_filter('woocommerce_checkout_posted_data',array(\$this,'apply_checkout_shipping_option'));", $code );
		$this->assertStringContainsString( "add_action('woocommerce_after_checkout_validation',array(\$this,'validate_checkout_amount'),10,2);", $code );
	}

	public function test_form_address_gets_the_billing_fallback() {
		// The checkout page and the Checkout block both fill the form from this response.
		$this->assertStringContainsString(
			"'billing'=>self::billing_address(\$this->get_posted_address('billing'),\$shipping),",
			$this->code( self::HANDLER )
		);
	}

	public function test_checkout_page_script_posts_the_fields_the_server_reads() {
		$script = (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/js/monei-express-checkout.js' );
		$code   = $this->code( self::HANDLER );

		foreach ( array( 'monei_express_shipping_option', 'monei_express_final_amount' ) as $field ) {
			$this->assertStringContainsString( "'$field'", $script );
			$this->assertStringContainsString( "\$_POST['$field']", $code );
		}
	}
}
