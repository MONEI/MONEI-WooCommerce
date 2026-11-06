<?php
/**
 * Both payment builders must check the owner of a saved token before they use it.
 *
 * PaymentTokenGuardTest covers the rule. This test catches a builder that never calls
 * it, which leaves the hole open: a shopper pays with another user's saved card.
 * An inverted condition inside the helper is not caught here.
 *
 * @package Monei
 */

namespace Monei\Tests;

use PHPUnit\Framework\TestCase;

class SavedTokenCheckWiringTest extends TestCase {

	const CHECK = '/\$failure=\$this->saved_token_failure\(\$order\);if\(null!==\$failure\)\{return\$failure;\}/';

	/**
	 * Source of a file with comments and whitespace removed, so a commented-out call does not count.
	 */
	private function code( string $relative_path ): string {
		$code = '';
		foreach ( token_get_all( file_get_contents( dirname( __DIR__, 2 ) . '/' . $relative_path ) ) as $token ) {
			if ( is_array( $token ) ) {
				if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT, T_WHITESPACE ), true ) ) {
					continue;
				}
				$token = $token[1];
			}
			$code .= $token;
		}
		return $code;
	}

	private function process_payment_body( string $code ): string {
		$start = strpos( $code, 'functionprocess_payment(' );
		$this->assertNotFalse( $start, 'process_payment not found' );
		$end = strpos( $code, 'publicfunction', $start + 1 );
		return substr( $code, $start, false === $end ? null : $end - $start );
	}

	/**
	 * @dataProvider builders
	 */
	public function test_builder_returns_the_check_result_before_the_payload( string $file, string $payload_marker ) {
		$body = $this->process_payment_body( $this->code( $file ) );

		$this->assertSame( 1, preg_match( self::CHECK, $body, $match, PREG_OFFSET_CAPTURE ), "$file does not return the saved token check" );
		$payload_at = strpos( $body, $payload_marker );
		$this->assertNotFalse( $payload_at, "$file payload marker not found" );
		$this->assertLessThan( $payload_at, $match[0][1], "$file builds the payload before the saved token check" );
	}

	public function builders(): array {
		return array(
			'component' => array( 'src/Gateways/Abstracts/WCMoneiPaymentGatewayComponent.php', '$this->create_payload(' ),
			'hosted'    => array( 'src/Gateways/Abstracts/WCMoneiPaymentGatewayHosted.php', '$payload=array(' ),
		);
	}

	public function test_helper_checks_the_owner_against_the_current_user() {
		$code = $this->code( 'src/Gateways/Abstracts/WCMoneiPaymentGateway.php' );
		$this->assertSame( 1, preg_match( '/functionsaved_token_failure\(.*?returnarray\(\'result\'=>\'failure\'\);\}/s', $code, $match ) );
		$this->assertStringContainsString( 'PaymentTokenGuard::belongs_to(', $match[0] );
		$this->assertStringContainsString( 'get_current_user_id()', $match[0] );
	}
}
