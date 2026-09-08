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

// Transient store, keyed the way WordPress keys it. Set monei_test_transients_persist
// to false to model a host whose object cache drops every transient: set_transient
// still reports success, get_transient never finds anything.
$GLOBALS['monei_test_transients']         = array();
$GLOBALS['monei_test_transients_persist'] = true;

function get_transient( $key ) {
	if ( ! $GLOBALS['monei_test_transients_persist'] ) {
		return false;
	}
	return $GLOBALS['monei_test_transients'][ $key ] ?? false;
}

function set_transient( $key, $value, $expiration ) {
	$GLOBALS['monei_test_transients'][ $key ]            = $value;
	$GLOBALS['monei_test_transient_expirations'][ $key ] = $expiration;
	return true;
}

// Option store. Unlike the transients above it always persists: that difference
// is the whole reason the backoff lives in an option.
$GLOBALS['monei_test_options'] = array();

function get_option( $key, $default = false ) {
	return $GLOBALS['monei_test_options'][ $key ] ?? $default;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['monei_test_options'][ $key ] = $value;
	return true;
}

function delete_option( $key ) {
	unset( $GLOBALS['monei_test_options'][ $key ] );
	return true;
}

// Clock, so a test can let a backoff window expire without waiting for it.
$GLOBALS['monei_test_now'] = 1_700_000_000;

function time() {
	return $GLOBALS['monei_test_now'];
}

namespace Monei\Tests;

use Monei\Api\PaymentMethodsApi;
use Monei\ApiException;
use Monei\MoneiClient;
use Monei\Repositories\PaymentMethodsRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Exception;

class PaymentMethodsRepositoryTest extends TestCase {

	private const ACCOUNT_ID = 'acc_test_123';

	/**
	 * A trimmed but shape-accurate /allowed-payment-methods body. The repository hands
	 * this straight to PaymentMethodsService, which reads paymentMethods and metadata.
	 */
	private const API_BODY = '{"paymentMethods":["card","bizum","applePay"],"metadata":{"card":{"brands":["visa","mastercard"]},"bizum":{}}}';

	protected function setUp(): void {
		$GLOBALS['monei_test_transients']            = array();
		$GLOBALS['monei_test_transients_persist']    = true;
		$GLOBALS['monei_test_transient_expirations'] = array();
		$GLOBALS['monei_test_options']               = array();
		$GLOBALS['monei_test_now']                   = 1_700_000_000;
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
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
			$this->throwException( new Exception( 'network down' ) )
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

	public function test_failure_with_no_last_good_answer_is_not_repeated_within_the_cache_window() {
		// A store with a wrong or missing API key has never had a good answer to fall
		// back to. Before this, that failure never reached the cache (an empty array is
		// falsy) and every checkout render repeated the call: one store did it 15
		// times a second for a week, against an endpoint that could only say 401.
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->once() )
			->method( 'getAllowed' )
			->willThrowException( new Exception( '401 Unauthorized' ) );

		$client = $this->clientWith( $api );

		$this->assertSame( array(), ( new PaymentMethodsRepository( self::ACCOUNT_ID, $client ) )->getPaymentMethods() );

		// The marker must live in the transient, not in the instance: every checkout
		// render is a new request and a new repository. And it must expire with the
		// short cache, or a corrected key would stay dark for an hour.
		$this->assertSame(
			30,
			$GLOBALS['monei_test_transient_expirations'][ 'payment_methods_' . md5( self::ACCOUNT_ID ) ]
		);
		$this->assertSame(
			array(),
			( new PaymentMethodsRepository( self::ACCOUNT_ID, $client ) )->getPaymentMethods(),
			'Second render within 30 s must not call again.'
		);
	}

	public function test_cached_failure_still_reads_as_no_methods() {
		// The marker that lets the failure be cached must never leak into the checkout
		// as a payment method list.
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->method( 'getAllowed' )->willThrowException( new Exception( 'network down' ) );

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );
		$repository->getPaymentMethods();

		$this->assertSame( array(), $repository->getPaymentMethods() );
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

	public function test_one_request_makes_one_api_call_even_when_transients_do_not_persist() {
		// Six gateways ask the repository several times each per checkout render.
		// The 30 second transient was meant to answer all but the first; on a host
		// whose object cache drops transients it answers none of them, and one
		// render became ten API calls. One store still sent nine requests a second
		// three days after the transient fix shipped.
		$GLOBALS['monei_test_transients_persist'] = false;
		$api                                      = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->once() )->method( 'getAllowed' )->willReturn( self::API_BODY );

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );
		$repository->getPaymentMethods();
		$repository->getPaymentMethods();

		$this->assertSame( array( 'card', 'bizum', 'applePay' ), $repository->getPaymentMethods()['paymentMethods'] );
	}

	/**
	 * @dataProvider rejectedStatuses
	 */
	public function test_rejected_key_holds_off_the_api_for_an_hour_across_requests( int $status ) {
		// A wrong key does not fix itself, and every rejected call still costs at
		// the gateway. The hold must survive where transients do not: an option.
		$GLOBALS['monei_test_transients_persist'] = false;
		$api                                      = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->once() )->method( 'getAllowed' )->willThrowException( new ApiException( 'rejected', $status ) );
		$client = $this->clientWith( $api );

		$this->assertSame( array(), ( new PaymentMethodsRepository( self::ACCOUNT_ID, $client ) )->getPaymentMethods() );
		$this->assertSame(
			array(
				'until' => $GLOBALS['monei_test_now'] + HOUR_IN_SECONDS,
				'delay' => HOUR_IN_SECONDS,
			),
			$GLOBALS['monei_test_options'][ PaymentMethodsRepository::BACKOFF_OPTION ]
		);

		// A new request, one second before the window ends.
		$GLOBALS['monei_test_now'] += HOUR_IN_SECONDS - 1;
		$next                       = new PaymentMethodsRepository( self::ACCOUNT_ID, $client );
		$this->assertSame( array(), $next->getPaymentMethods(), 'Must not call again inside the window.' );
		$this->assertSame( $GLOBALS['monei_test_now'] + 1, $next->getBackoffUntil() );
	}

	public function rejectedStatuses(): array {
		// 401 is the API refusing the key; 403 is the edge refusing the caller.
		// Neither changes by retrying.
		return array(
			'401' => array( 401 ),
			'403' => array( 403 ),
		);
	}

	public function test_each_further_rejection_doubles_the_hold_up_to_a_day() {
		// A store nobody is maintaining keeps its wrong key for months. One probe an
		// hour is still 24 wasted calls a day per store; the doubling brings a
		// forgotten store down to one.
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->method( 'getAllowed' )->willThrowException( new ApiException( 'rejected', 401 ) );
		$client = $this->clientWith( $api );

		$expected = array( HOUR_IN_SECONDS, 2 * HOUR_IN_SECONDS, 4 * HOUR_IN_SECONDS, 8 * HOUR_IN_SECONDS, 16 * HOUR_IN_SECONDS, DAY_IN_SECONDS, DAY_IN_SECONDS );
		foreach ( $expected as $delay ) {
			( new PaymentMethodsRepository( self::ACCOUNT_ID, $client ) )->getPaymentMethods();
			$this->assertSame( $delay, $GLOBALS['monei_test_options'][ PaymentMethodsRepository::BACKOFF_OPTION ]['delay'] );
			// Let the window run out, and the short transient with it.
			$GLOBALS['monei_test_now']       += $delay;
			$GLOBALS['monei_test_transients'] = array();
		}
	}

	public function test_a_working_key_clears_the_hold() {
		// Otherwise a corrected key inherits the previous key's doubled delay the
		// next time anything goes wrong.
		$GLOBALS['monei_test_options'][ PaymentMethodsRepository::BACKOFF_OPTION ] = array(
			'until' => $GLOBALS['monei_test_now'] - 1,
			'delay' => DAY_IN_SECONDS,
		);
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->once() )->method( 'getAllowed' )->willReturn( self::API_BODY );

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );

		$this->assertSame( array( 'card', 'bizum', 'applePay' ), $repository->getPaymentMethods()['paymentMethods'] );
		$this->assertArrayNotHasKey( PaymentMethodsRepository::BACKOFF_OPTION, $GLOBALS['monei_test_options'] );
		$this->assertNull( $repository->getBackoffUntil() );
	}

	public function test_outages_do_not_start_a_hold() {
		// A 5xx or a dropped connection is MONEI's problem or the network's, and it
		// passes. Holding the checkout dark for an hour over it would be a regression
		// from the 30 second retry.
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->method( 'getAllowed' )->willReturnOnConsecutiveCalls(
			$this->throwException( new ApiException( 'Service Unavailable', 503 ) ),
			$this->throwException( new Exception( 'network down' ) )
		);
		$client = $this->clientWith( $api );

		( new PaymentMethodsRepository( self::ACCOUNT_ID, $client ) )->getPaymentMethods();
		$GLOBALS['monei_test_transients'] = array();
		( new PaymentMethodsRepository( self::ACCOUNT_ID, $client ) )->getPaymentMethods();

		$this->assertArrayNotHasKey( PaymentMethodsRepository::BACKOFF_OPTION, $GLOBALS['monei_test_options'] );
	}

	public function test_checkout_during_the_hold_still_gets_the_last_good_answer() {
		// The hold changes only whether the API is asked. What the checkout sees must
		// equal a failed lookup: the last good answer while it lives, nothing after.
		$GLOBALS['monei_test_options'][ PaymentMethodsRepository::BACKOFF_OPTION ] = array(
			'until' => $GLOBALS['monei_test_now'] + HOUR_IN_SECONDS,
			'delay' => HOUR_IN_SECONDS,
		);
		$GLOBALS['monei_test_transients'][ 'payment_methods_' . md5( self::ACCOUNT_ID ) . '_last_ok' ] = json_decode( self::API_BODY, true );
		$api = $this->createMock( PaymentMethodsApi::class );
		$api->expects( $this->never() )->method( 'getAllowed' );

		$repository = new PaymentMethodsRepository( self::ACCOUNT_ID, $this->clientWith( $api ) );

		$this->assertSame( array( 'card', 'bizum', 'applePay' ), $repository->getPaymentMethods()['paymentMethods'] );
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
