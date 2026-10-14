<?php
/**
 * Gateway constructors must not ask the MONEI API which payment methods the account has.
 *
 * Gateways are built on every product page for the express buttons, crawlers included.
 * When the constructors called is_valid_for_use(), each of those page views refreshed the
 * 30 second payment methods cache: one store with 10 orders in two weeks made about 8,700
 * /v1/allowed-payment-methods calls a day. The API check belongs in is_available() and the
 * checkout script guards, which run only where payment methods are shown.
 *
 * @package Monei
 */

namespace Monei\Tests;

use PHPUnit\Framework\TestCase;

class GatewayConstructorTest extends TestCase {

	public function test_no_gateway_constructor_calls_the_payment_methods_api() {
		$files = glob( dirname( __DIR__, 2 ) . '/src/Gateways/PaymentMethods/*.php' );
		$this->assertNotEmpty( $files );

		foreach ( $files as $file ) {
			$source = file_get_contents( $file );
			if ( ! preg_match( '/function __construct\b.*?\n\t}\n/s', $source, $match ) ) {
				continue;
			}
			foreach ( array( 'is_valid_for_use(', 'is_available(', 'paymentMethodsService->' ) as $call ) {
				$this->assertStringNotContainsString( $call, $match[0], basename( $file ) . " constructor calls $call" );
			}
		}
	}
}
