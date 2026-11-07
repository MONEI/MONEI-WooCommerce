<?php
/**
 * A guest who creates an account at checkout and pays with a MONEI.js token.
 *
 * The token is bound to the guest session ID. WooCommerce logs the buyer in during
 * the checkout request, so the current session ID is the user ID when the payment is
 * built. Sending the user ID fails the payment with "Invalid payment method".
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\Services\payment\TokenSessionId;
use PHPUnit\Framework\TestCase;

class TokenSessionIdTest extends TestCase {

	use SourceCode;

	protected function setUp(): void {
		TokenSessionId::remember_guest_session_id( '' );
	}

	public function test_session_without_login_is_sent_as_is() {
		$this->assertSame( 't_0f3a9c', TokenSessionId::for_token( 't_0f3a9c' ) );
	}

	public function test_guest_session_is_sent_after_login_at_checkout() {
		TokenSessionId::remember_guest_session_id( 't_0f3a9c' );

		$this->assertSame( 't_0f3a9c', TokenSessionId::for_token( '42' ) );
	}

	/**
	 * @dataProvider builders
	 */
	public function test_builder_sends_the_token_session_id( string $file ) {
		$code = $this->code( $file );

		$this->assertSame( 1, preg_match_all( '/\$payload\[\'sessionId\'\]=([^;]+);/', $code, $matches ), "$file must set sessionId once" );
		$this->assertStringStartsWith( 'TokenSessionId::for_token(', $matches[1][0] );
	}

	public function builders(): array {
		return array(
			'component' => array( 'src/Gateways/Abstracts/WCMoneiPaymentGatewayComponent.php' ),
			'hosted'    => array( 'src/Gateways/Abstracts/WCMoneiPaymentGatewayHosted.php' ),
		);
	}

	public function test_guest_session_id_is_captured_on_login() {
		$this->assertStringContainsString(
			"add_action('woocommerce_guest_session_to_user_id',array(TokenSessionId::class,'remember_guest_session_id'));",
			$this->code( 'class-woocommerce-gateway-monei.php' )
		);
	}
}
