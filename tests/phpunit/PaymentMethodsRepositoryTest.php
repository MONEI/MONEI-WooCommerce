<?php
/**
 * Which payment methods endpoint the plugin asks, and what it does when the answer
 * does not arrive.
 *
 * The endpoint choice is not cosmetic. /payment-methods is deprecated, takes an
 * account id instead of the API key, and MONEI can retire it. An empty answer is not
 * cosmetic either: it marks every gateway unavailable, which takes MONEI off the
 * checkout entirely.
 *
 * @package Monei
 */

namespace Monei\Repositories;

// WordPress is not loaded in this suite. PHP resolves an unqualified function call
// against the current namespace before the global one, so these shims answer the
// repository without a WordPress runtime. They live in the repository's namespace on
// purpose — nothing outside it sees them.

// Transient store, keyed the way WordPress keys it.
$GLOBALS['monei_test_transients'] = array();

function get_transient( $key ) {
	return $GLOBALS['monei_test_transients'][ $key ] ?? false;
}

function set_transient( $key, $value, $expiration ) {
	$GLOBALS['monei_test_transients'][ $key ] = $value;
	return true;
}

namespace Monei\Tests;

use Monei\Api\PaymentMethodsApi;
use Monei\MoneiClient;
use Monei\Repositories\PaymentMethodsRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentMethodsRepositoryTest extends TestCase {

	private const ACCOUNT_ID = 'acc_test_123';

	/**
	 * A trimmed but shape-accurate /allowed-payment-methods body. The repository hands
	 * this straight to PaymentMethodsService, which reads paymentMethods and metadata.
	 */
	private const API_BODY = '{"paymentMethods":["card","bizum","applePay"],"metadata":{"card":{"brands":["visa","mastercard"]},"bizum":{}}}';

	protected function setUp(): void {
		$GLOBALS['monei_test_transients'] = array();
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}
	}

	public function test_asks_the_allowed_payment_methods_endpoint() {
		// The regression guard for the whole change. paymentMethods->get() is the
		// deprecated /payment-methods; getAllowed() is /allowed-payment-methods.
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->once() )->method( 'getAllowed' )->willReturn( self::API_BODY );
		$api->expects( $this->never() )->method( 'get' );

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );

		$this->assertSame(
			array( 'card', 'bizum', 'applePay' ),
			$repository->getPaymentMethods()['paymentMethods']
		);
	}

	public function test_sends_no_account_id_to_the_endpoint() {
		// /allowed-payment-methods derives the account from the API key. Passing the
		// account id into the first argument would set paymentId instead, which asks
		// about a payment that does not exist.
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->once() )
			->method( 'getAllowed' )
			->with( null, null, null, null )
			->willReturn( self::API_BODY );

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );
		$repository->getPaymentMethods();
	}

	public function test_unconfigured_account_makes_no_api_call() {
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->never() )->method( 'getAllowed' );

		$repository = new PaymentMethodsRepository( '', $this->clientWith( $api ) );

		$this->assertSame( array(), $repository->getPaymentMethods() );
	}

	public function test_second_call_is_served_from_the_cache() {
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->once() )->method( 'getAllowed' )->willReturn( self::API_BODY );

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );
		$repository->getPaymentMethods();

		$this->assertSame( array( 'card', 'bizum', 'applePay' ), $repository->getPaymentMethods()['paymentMethods'] );
	}

	public function test_failed_call_falls_back_to_the_last_good_answer() {
		// The short cache is 30 seconds, so one failed call is always moments away. Without
		// the fallback that failure empties the checkout of every MONEI method.
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->method( 'getAllowed' )->willReturnOnConsecutiveCalls(
			self::API_BODY,
			$this->throwException( new \Exception( 'network down' ) )
		);

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );
		$repository->getPaymentMethods();

		// Expire only the short cache, the way WordPress would after 30 seconds.
		unset( $GLOBALS['monei_test_transients'][ 'payment_methods_' . md5( self::ACCOUNT_ID ) ] );

		$this->assertSame(
			array( 'card', 'bizum', 'applePay' ),
			$repository->getPaymentMethods()['paymentMethods'],
			'A failed call must not empty the checkout.'
		);
	}

	public function test_test_and_live_accounts_do_not_share_a_cache() {
		// Switching the API key mode must not serve the other mode's methods.
		$liveApi = $this->createMock( PaymentMethodsApi::class );
		$liveApi->method( 'getAllowed' )->willReturn( '{"paymentMethods":["card"],"metadata":{}}' );
		$testApi = $this->createMock( PaymentMethodsApi::class );
		$testApi->method( 'getAllowed' )->willReturn( self::API_BODY );

		$live = new PaymentMethodsRepository( 'acc_live_999', $this->clientWith( $liveApi ) );
		$test = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $testApi ) );

		$this->assertSame( array( 'card' ), $live->getPaymentMethods()['paymentMethods'] );
		$this->assertSame( array( 'card', 'bizum', 'applePay' ), $test->getPaymentMethods()['paymentMethods'] );
	}

	/**
	 * @param PaymentMethodsApi&MockObject $api
	 */
	private function clientWith( $api ): MoneiClient {
		// The real constructor builds a Guzzle stack and would reach the network.
		$client                 = $this->createMock( MoneiClient::class );
		$client->paymentMethods = $api;
		return $client;
	}
}
