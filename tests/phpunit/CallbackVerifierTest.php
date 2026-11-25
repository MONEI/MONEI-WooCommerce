<?php
/**
 * CallbackVerifier: which payment a MONEI callback may process.
 *
 * The fetch returns a real Monei\Model\Payment, deserialized from a JSON answer the way the SDK does it, so the
 * camelCase shape of the fetch path is the SDK's, not a mock's.
 *
 * Every test names the bug it catches.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\ApiException;
use Monei\Model\Payment;
use Monei\ObjectSerializer;
use Monei\Services\CallbackVerifier;
use PHPUnit\Framework\TestCase;

class CallbackVerifierTest extends TestCase {

	const ID     = 'af6029f80f5fc73a8ad2753eea0b1be0';
	const SECRET = 'mksec_test_secret';
	const KEY    = 'mk_test_key';

	/** @var int */
	private $now = 1700000000;
	/** @var array<array-key, array{paymentId: string, paymentStatus: string, paid: bool}> */
	private $orders = array();
	/** @var array<string, mixed> Fields of the payment that MONEI answers. */
	private $remote = array();
	/** @var bool */
	private $fetch_fails = false;
	/** @var array[] */
	private $fetches = array();

	protected function setUp(): void {
		$this->orders = array(
			'1001' => array(
				'paymentId'     => '',
				'paymentStatus' => '',
				'paid'          => false,
			),
		);
		$this->remote = self::payment_fields( 'SUCCEEDED' );
	}

	private static function payment_fields( string $status, array $overrides = array() ): array {
		return $overrides + array(
			'id'            => self::ID,
			'orderId'       => '1001',
			'amount'        => 1000,
			'currency'      => 'EUR',
			'livemode'      => false,
			'status'        => $status,
			'statusCode'    => 'E000',
			'statusMessage' => 'Transaction approved',
		);
	}

	private static function connected_shop(): MemoryOptionStore {
		return new MemoryOptionStore(
			array(
				'monei_apikey_mode'         => 'test',
				'monei_test_apikey'         => self::KEY,
				'monei_test_signing_secret' => self::SECRET,
			)
		);
	}

	private function verifier( MemoryOptionStore $store ): CallbackVerifier {
		return new CallbackVerifier(
			$store,
			function () {
				return $this->now;
			},
			function ( string $order_id ) {
				return $this->orders[ $order_id ] ?? null;
			},
			function ( string $mode, string $id ) {
				$this->fetches[] = array( $mode, $id );
				if ( $this->fetch_fails ) {
					throw new ApiException( '[0] cURL error 28', 0 );
				}
				return ObjectSerializer::deserialize( json_decode( (string) json_encode( $this->remote ) ), '\Monei\Model\Payment' );
			}
		);
	}

	private static function sign( string $body, string $element, string $key ): string {
		return $element . '=' . hash_hmac( 'SHA256', '1700000000.' . $body, $key );
	}

	private static function body( array $fields ): string {
		return (string) json_encode( $fields );
	}

	/**
	 * The fields handle_valid_ipn reads, in a fixed order; a missing one shows as 'missing'.
	 */
	private static function six( array $payment ): array {
		$six = array();
		foreach ( array( 'id', 'orderId', 'amount', 'status', 'statusCode', 'statusMessage' ) as $key ) {
			$six[ $key ] = array_key_exists( $key, $payment ) ? $payment[ $key ] : 'missing';
		}
		return $six;
	}

	/**
	 * Bug: a connected shop fetches on every callback, or drops a body that v2 proves.
	 */
	public function test_a_valid_v2_returns_the_body_payment_without_a_fetch() {
		$body = self::body( self::payment_fields( 'AUTHORIZED' ) );

		$decision = $this->verifier( self::connected_shop() )->verify( $body, 't=1700000000,' . self::sign( $body, 'v2', self::SECRET ) );

		$this->assertSame( 200, $decision['status'] );
		$this->assertSame( 'AUTHORIZED', $decision['payment']['status'] );
		$this->assertSame( array(), $this->fetches );
	}

	/**
	 * Bug: a callback that v2 does not prove changes the order from its body. This is also the test with a real
	 * Monei\Model\Payment: the v1-only row is a callback of an order made before the connect, and it settles
	 * through the fetched payment.
	 *
	 * @dataProvider unproven_signatures
	 */
	public function test_a_connected_shop_fetches_the_payment_and_ignores_the_body( string $element_key, ?string $element ) {
		$body      = self::body( self::payment_fields( 'SUCCEEDED', array( 'statusMessage' => 'from the body' ) ) );
		$signature = null === $element ? null : 't=1700000000,' . self::sign( $body, $element, $element_key );

		$decision = $this->verifier( self::connected_shop() )->verify( $body, $signature );

		$this->assertSame( array( array( 'test', self::ID ) ), $this->fetches );
		$this->assertSame( 200, $decision['status'] );
		$this->assertSame(
			array(
				'id'            => self::ID,
				'orderId'       => '1001',
				'amount'        => 1000,
				'status'        => 'SUCCEEDED',
				'statusCode'    => 'E000',
				'statusMessage' => 'Transaction approved',
			),
			self::six( $decision['payment'] )
		);
	}

	public function unproven_signatures(): array {
		return array(
			'v1 only, the old pk_ key' => array( 'pk_test_old', 'v1' ),
			'v2 of a replaced secret'  => array( 'mksec_test_replaced', 'v2' ),
			'no header'                => array( '', null ),
		);
	}

	/**
	 * Bug: the two paths hand handle_valid_ipn different shapes (snake_case model data, a missing key), so a
	 * fetched payment does not settle the order.
	 */
	public function test_both_paths_return_the_same_shape() {
		$expected = array(
			'id'            => self::ID,
			'orderId'       => '1001',
			'amount'        => 1000,
			'status'        => 'SUCCEEDED',
			'statusCode'    => 'E000',
			'statusMessage' => 'Transaction approved',
		);
		$body     = self::body( $this->remote );

		$signed  = $this->verifier( self::connected_shop() )->verify( $body, 't=1700000000,' . self::sign( $body, 'v2', self::SECRET ) );
		$fetched = $this->verifier( self::connected_shop() )->verify( $body, null );

		$this->assertSame( $expected, self::six( $signed['payment'] ) );
		$this->assertSame( $expected, self::six( $fetched['payment'] ) );
	}

	/**
	 * Bug: a forged body or a fetched payment that does not belong to the order changes it. Each answers 200, so
	 * MONEI does not retry.
	 *
	 * @dataProvider payments_that_change_nothing
	 */
	public function test_a_fetched_payment_that_does_not_fit_changes_nothing( array $remote, bool $paid ) {
		$this->remote                 = $remote;
		$this->orders['1001']['paid'] = $paid;
		$body                         = self::body( self::payment_fields( 'SUCCEEDED' ) );

		$decision = $this->verifier( self::connected_shop() )->verify( $body, null );

		$this->assertSame( array( 200, null ), array( $decision['status'], $decision['payment'] ) );
	}

	public function payments_that_change_nothing(): array {
		return array(
			'forged SUCCEEDED, MONEI says PENDING' => array( self::payment_fields( 'PENDING' ), false ),
			'FAILED for an order already paid'     => array( self::payment_fields( 'FAILED' ), true ),
			'another orderId'                      => array( self::payment_fields( 'SUCCEEDED', array( 'orderId' => '1002' ) ), false ),
			'another payment id'                   => array( self::payment_fields( 'SUCCEEDED', array( 'id' => str_repeat( 'b', 32 ) ) ), false ),
		);
	}

	/**
	 * Bug: a fetched payment with another amount than the order answers 200 and leaves no trace. It goes on to
	 * handle_valid_ipn, which puts the order on hold with a note, as for a signed body.
	 */
	public function test_a_fetched_payment_with_another_amount_goes_on_to_the_order() {
		$this->remote = self::payment_fields( 'SUCCEEDED', array( 'amount' => 1002 ) );

		$decision = $this->verifier( self::connected_shop() )->verify( self::body( self::payment_fields( 'SUCCEEDED' ) ), null );

		$this->assertSame( 1002, $decision['payment']['amount'] );
	}

	/**
	 * Bug: a payment with a value this SDK does not know (a new status) answers 503 on every retry.
	 */
	public function test_a_payment_the_sdk_cannot_read_answers_200() {
		$this->remote = self::payment_fields( 'NEW_STATUS' );

		$decision = $this->verifier( self::connected_shop() )->verify( self::body( self::payment_fields( 'SUCCEEDED' ) ), null );

		$this->assertSame( array( 200, null ), array( $decision['status'], $decision['payment'] ) );
	}

	/**
	 * Bug: a callback without an order (a card tokenization, orderId "<user>generatetoken<rand>") answers 400, so
	 * MONEI retries it and sends failure emails.
	 */
	public function test_a_callback_without_an_order_answers_200_with_no_fetch() {
		$body = self::body( self::payment_fields( 'SUCCEEDED', array( 'orderId' => '1generatetoken42' ) ) );

		$decision = $this->verifier( self::connected_shop() )->verify( $body, null );

		$this->assertSame( array( 200, null ), array( $decision['status'], $decision['payment'] ) );
		$this->assertSame( array(), $this->fetches );
	}

	/**
	 * Bug: the payment of a retry is refused for good, because the order still stores the failed first payment;
	 * or a second payment replaces a successful one.
	 *
	 * @dataProvider stored_payments
	 */
	public function test_another_payment_id_is_fetched_only_after_the_stored_payment_failed( string $stored_status, int $fetches ) {
		$this->orders['1001']['paymentId']     = str_repeat( 'c', 32 );
		$this->orders['1001']['paymentStatus'] = $stored_status;

		$decision = $this->verifier( self::connected_shop() )->verify( self::body( self::payment_fields( 'SUCCEEDED' ) ), null );

		$this->assertSame( array( $fetches ? 200 : 400, $fetches ), array( $decision['status'], count( $this->fetches ) ) );
	}

	public function stored_payments(): array {
		return array(
			'stored FAILED: a retry'          => array( 'FAILED', 1 ),
			'stored SUCCEEDED: not replaced'  => array( 'SUCCEEDED', 0 ),
		);
	}

	/**
	 * Bug: a failed fetch answers 200 or settles from the body, so MONEI never retries and the order stays open.
	 */
	public function test_a_failed_fetch_answers_503() {
		$this->fetch_fails = true;

		$decision = $this->verifier( self::connected_shop() )->verify( self::body( $this->remote ), null );

		$this->assertSame( array( 503, null ), array( $decision['status'], $decision['payment'] ) );
	}

	/**
	 * Bug: a body that cannot be a callback of this shop makes the shop call MONEI (a fetch amplifier).
	 *
	 * @dataProvider bodies_refused_before_a_fetch
	 */
	public function test_a_failed_pre_check_answers_400_with_no_fetch( array $fields, ?string $signature, array $order ) {
		$this->orders['1001'] = $order + $this->orders['1001'];

		$decision = $this->verifier( self::connected_shop() )->verify( self::body( $fields ), $signature );

		$this->assertSame( 400, $decision['status'] );
		$this->assertSame( array(), $this->fetches );
	}

	public function bodies_refused_before_a_fetch(): array {
		return array(
			'malformed id'                 => array( self::payment_fields( 'SUCCEEDED', array( 'id' => '../payments' ) ), null, array() ),
			'a header that does not parse' => array( self::payment_fields( 'SUCCEEDED' ), 'v1=abc', array() ),
		);
	}

	/**
	 * Bug: one order, or the whole shop, makes unbounded fetches; or the quota never frees up. A refusal answers
	 * 503, so MONEI retries a real callback that unsigned requests crowded out.
	 */
	public function test_the_order_quota_refuses_the_6th_attempt_in_a_minute_and_frees_after_61_s() {
		$store    = self::connected_shop();
		$body     = self::body( $this->remote );
		$statuses = array();
		for ( $i = 0; $i < 6; $i++ ) {
			$statuses[] = $this->verifier( $store )->verify( $body, null )['status'];
		}
		$this->now += 61;
		$statuses[] = $this->verifier( $store )->verify( $body, null )['status'];

		$this->assertSame( array( 200, 200, 200, 200, 200, 503, 200 ), $statuses );
		$this->assertCount( 6, $this->fetches );
	}

	/**
	 * Bug: many orders together make unbounded fetches.
	 */
	public function test_the_shop_quota_refuses_the_61st_attempt_in_a_minute() {
		$store = self::connected_shop();
		for ( $order = 1; $order <= 13; $order++ ) {
			$this->orders[ "o$order" ] = array(
				'paymentId'     => '',
				'paymentStatus' => '',
				'paid'          => false,
			);
		}
		$last = 0;
		for ( $attempt = 0; $attempt < 61; $attempt++ ) {
			$order = 'o' . ( intdiv( $attempt, 5 ) + 1 );
			$last  = $this->verifier( $store )->verify( self::body( self::payment_fields( 'SUCCEEDED', array( 'orderId' => $order ) ) ), null )['status'];
		}

		$this->assertSame( 503, $last );
		$this->assertCount( 60, $this->fetches );
	}

	/**
	 * Bug: a manual pk_ shop loses its v1 check (a forged body settles an order), or starts to fetch.
	 */
	public function test_a_manual_pk_shop_verifies_v1_as_today() {
		$store    = new MemoryOptionStore(
			array(
				'monei_apikey_mode' => 'test',
				'monei_test_apikey' => 'pk_test_manual',
			)
		);
		$body     = self::body( $this->remote );
		$verifier = $this->verifier( $store );

		$valid  = $verifier->verify( $body, 't=1700000000,' . self::sign( $body, 'v1', 'pk_test_manual' ) );
		$forged = $verifier->verify( $body, 't=1700000000,' . self::sign( $body, 'v1', 'pk_test_other' ) );
		$absent = $verifier->verify( $body, null );

		$this->assertSame( array( 200, 401, 401 ), array( $valid['status'], $forged['status'], $absent['status'] ) );
		$this->assertSame( 'SUCCEEDED', $valid['payment']['status'] );
		$this->assertSame( array(), $this->fetches );
	}

	/**
	 * Bug: a mode without a key accepts a v1 made with an empty key, which anyone can compute.
	 */
	public function test_a_mode_without_a_key_refuses_a_v1_of_the_empty_key() {
		$body = self::body( $this->remote );

		$decision = $this->verifier( new MemoryOptionStore() )->verify( $body, 't=1700000000,' . self::sign( $body, 'v1', '' ) );

		$this->assertSame( 401, $decision['status'] );
	}

	/**
	 * Bug: a callback of the other mode is checked with the selected mode's key, so Live callbacks of a shop in
	 * test mode fail. Each mode verifies with its own key.
	 */
	public function test_livemode_picks_the_key_of_its_mode() {
		$store = new MemoryOptionStore(
			array(
				'monei_apikey_mode' => 'test',
				'monei_test_apikey' => 'pk_test_manual',
				'monei_live_apikey' => 'pk_live_manual',
			)
		);
		$body  = self::body( self::payment_fields( 'SUCCEEDED', array( 'livemode' => true ) ) );

		$live = $this->verifier( $store )->verify( $body, 't=1700000000,' . self::sign( $body, 'v1', 'pk_live_manual' ) );
		$test = $this->verifier( $store )->verify( $body, 't=1700000000,' . self::sign( $body, 'v1', 'pk_test_manual' ) );

		$this->assertSame( array( 200, 401 ), array( $live['status'], $test['status'] ) );
	}

	/**
	 * Bug: the IPN refuses a callback without MONEI-Signature on a shop where only one mode is connected.
	 */
	public function test_any_mode_connected_counts_either_mode() {
		$live_only = new MemoryOptionStore(
			array(
				'monei_live_apikey' => 'mk_live_key',
				'monei_test_apikey' => 'pk_test_manual',
			)
		);
		$manual    = new MemoryOptionStore(
			array(
				'monei_live_apikey' => 'pk_live_manual',
				'monei_test_apikey' => 'pk_test_manual',
			)
		);

		$this->assertSame( array( true, false ), array( $this->verifier( $live_only )->any_mode_connected(), $this->verifier( $manual )->any_mode_connected() ) );
	}
}
