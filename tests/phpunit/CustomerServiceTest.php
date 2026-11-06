<?php
/**
 * CustomerService: the mapping of WP users to MONEI customers, and the deletes at MONEI.
 *
 * The numbered tests are the contract tests (docs/plans/20261105-monei-customers-api-contract.md).
 * Every test names the bug it catches.
 *
 * The file needs several namespaces, so every block is bracketed: PHP does not allow
 * bracketed and unbracketed blocks in one file.
 *
 * @package Monei
 */

namespace {

	if ( ! class_exists( 'WC_Monei_Logger' ) ) {
		/**
		 * Records each log line with its level, so a test can tell an error from a debug line.
		 */
		class WC_Monei_Logger {
			const LEVEL_INFO    = 1;
			const LEVEL_WARNING = 2;
			const LEVEL_ERROR   = 3;
			const LEVEL_NONE    = 4;

			/** @var array<int, array{0: int, 1: string}> */
			public static $records = array();

			public static function log( $message, $severity = self::LEVEL_INFO ) {
				self::$records[] = array( (int) $severity, (string) $message );
			}

			public static function logDebug( $message ) {
				self::log( $message, self::LEVEL_INFO );
			}

			public static function logWarning( $message ) {
				self::log( $message, self::LEVEL_WARNING );
			}

			public static function logError( $message ) {
				self::log( $message, self::LEVEL_ERROR );
			}
		}
	}
}

namespace Monei\Services {

	// WordPress is not loaded. PHP resolves an unqualified call in the current namespace
	// first, so these shims answer CustomerService and ApiKeyService. They follow the
	// WordPress semantics that the service depends on.

	function get_option( $key, $default = false ) {
		return $GLOBALS['mcs_options'][ $key ] ?? $default;
	}

	function add_action( ...$args ) {
		$GLOBALS['mcs_hooks'][] = $args;
		return true;
	}

	// The backing store is $GLOBALS['mcs_meta'] (user ID => key => list of values), the
	// per-request cache is $GLOBALS['mcs_meta_cache']. Another PHP process writes only
	// the backing store.
	function get_user_meta( $user_id, $key = '', $single = false ) {
		$user_id = abs( (int) $user_id );
		if ( 0 === $user_id ) {
			return false;
		}
		if ( '' === $key && $GLOBALS['mcs_all_meta_throws'] ) {
			throw new \Error( 'all-meta read failed' );
		}
		if ( ! isset( $GLOBALS['mcs_meta_cache'][ $user_id ] ) ) {
			$GLOBALS['mcs_meta_cache'][ $user_id ] = $GLOBALS['mcs_meta'][ $user_id ] ?? array();
		}
		$meta = $GLOBALS['mcs_meta_cache'][ $user_id ];
		if ( '' === $key ) {
			return $meta;
		}
		if ( isset( $meta[ $key ] ) ) {
			return $single ? $meta[ $key ][0] : $meta[ $key ];
		}
		return $single ? '' : array();
	}

	function add_user_meta( $user_id, $key, $value, $unique = false ) {
		$user_id = abs( (int) $user_id );
		$result  = false;
		// The unique check reads the database, never the cache (add_metadata).
		$taken = $unique && ! $GLOBALS['mcs_ignore_unique'] && ! empty( $GLOBALS['mcs_meta'][ $user_id ][ $key ] );
		if ( ! $GLOBALS['mcs_add_fails'] && ! $taken ) {
			$GLOBALS['mcs_meta'][ $user_id ][ $key ][] = $value;
			unset( $GLOBALS['mcs_meta_cache'][ $user_id ] );
			$result = 1;
		}
		$GLOBALS['mcs_add_results'][] = $result;
		return $result;
	}

	function delete_user_meta( $user_id, $key, $value = '' ) {
		$user_id = abs( (int) $user_id );
		$values  = $GLOBALS['mcs_meta'][ $user_id ][ $key ] ?? array();
		$kept    = '' === $value ? array() : array_values(
			array_filter(
				$values,
				function ( $stored ) use ( $value ) {
					return $stored !== $value;
				}
			)
		);
		if ( count( $kept ) === count( $values ) ) {
			return false;
		}
		if ( $kept ) {
			$GLOBALS['mcs_meta'][ $user_id ][ $key ] = $kept;
		} else {
			unset( $GLOBALS['mcs_meta'][ $user_id ][ $key ] );
		}
		unset( $GLOBALS['mcs_meta_cache'][ $user_id ] );
		return true;
	}

	function get_userdata( $user_id ) {
		return $GLOBALS['mcs_users'][ (int) $user_id ] ?? false;
	}

	function wp_cache_delete( $key, $group = '' ) {
		if ( 'user_meta' === $group ) {
			unset( $GLOBALS['mcs_meta_cache'][ (int) $key ] );
		}
		return true;
	}
}

namespace Monei\Tests {

	use Monei\Api\CustomersApi;
	use Monei\Model\Customer;
	use Monei\MoneiClient;
	use Monei\Services\ApiKeyService;
	use Monei\Services\CustomerService;
	use Monei\Services\sdk\MoneiSdkClientFactory;
	use PHPUnit\Framework\MockObject\MockObject;
	use PHPUnit\Framework\TestCase;
	use WC_Monei_Logger;

	/**
	 * Answers the one query CustomerService runs: does the user row still exist.
	 */
	class FakeWpdb {
		public $users = 'wp_users';

		public function prepare( $query, ...$args ) {
			return vsprintf( $query, $args );
		}

		public function get_var( $query ) {
			if ( preg_match( '/^SELECT ID FROM wp_users WHERE ID = (\d+)$/', $query, $match ) && isset( $GLOBALS['mcs_users'][ (int) $match[1] ] ) ) {
				return $match[1];
			}
			return null;
		}
	}

	class CustomerServiceTest extends TestCase {

		const USER_ID = 7;

		/** @var CustomersApi&MockObject */
		private $customers;

		protected function setUp(): void {
			$GLOBALS['mcs_options']         = array(
				'monei_apikey_mode'    => 'test',
				'monei_test_apikey'    => 'pk_test_key',
				'monei_live_apikey'    => 'pk_live_key',
				'monei_test_accountid' => 'acc_1',
				'monei_live_accountid' => 'acc_1',
			);
			$GLOBALS['mcs_hooks']           = array();
			$GLOBALS['mcs_meta']            = array();
			$GLOBALS['mcs_meta_cache']      = array();
			$GLOBALS['mcs_add_results']     = array();
			$GLOBALS['mcs_ignore_unique']   = false;
			$GLOBALS['mcs_add_fails']       = false;
			$GLOBALS['mcs_all_meta_throws'] = false;
			$GLOBALS['mcs_users']           = array(
				self::USER_ID => (object) array(
					'ID'           => self::USER_ID,
					'user_email'   => 'buyer@example.com',
					'display_name' => 'buyer',
				),
			);
			$GLOBALS['wpdb']                = new FakeWpdb();
			WC_Monei_Logger::$records       = array();

			$this->customers = $this->createMock( CustomersApi::class );
		}

		private function service(): CustomerService {
			$client            = $this->createMock( MoneiClient::class );
			$client->customers = $this->customers;
			$factory           = $this->createMock( MoneiSdkClientFactory::class );
			$factory->method( 'get_client' )->willReturn( $client );
			return new CustomerService( $factory, new ApiKeyService() );
		}

		private function key( string $mode = 'test' ): string {
			return '_monei_customer_acc_1_' . $mode;
		}

		private function stored( string $mode = 'test' ): array {
			return $GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key( $mode ) ] ?? array();
		}

		private function customer( string $id ): Customer {
			return new Customer( array( 'id' => $id ) );
		}

		private function link(): ?string {
			return $this->service()->get_or_create_customer_id( self::USER_ID, 'buyer@example.com', 'Ana Buyer', '600123456' );
		}

		private function errors(): array {
			return array_values(
				array_filter(
					WC_Monei_Logger::$records,
					function ( $record ) {
						return WC_Monei_Logger::LEVEL_ERROR === $record[0];
					}
				)
			);
		}

		private function add( int $user_id, ?string $fallback_phone = null ): array {
			return $this->service()->add_customer_id( array( 'amount' => 100 ), $user_id, $fallback_phone );
		}

		/**
		 * Contract test 1. Bug: orphan MONEI customers for guests, or a mapping read for a
		 * user ID that has no WP user. The unknown user has a mapping, so only the user check stops it.
		 */
		public function test_guest_and_unknown_user_get_no_customer_id_and_no_create_call() {
			$GLOBALS['mcs_meta'][99][ $this->key() ] = array( 'cus_orphan' );
			$this->customers->expects( $this->never() )->method( 'create' );

			$this->assertSame( array( 'amount' => 100 ), $this->add( 0 ) );
			$this->assertSame( array( 'amount' => 100 ), $this->add( 99 ) );
			// PHPUnit turns a property read on false into an exception that the service catches, so only the log shows a missing check.
			$this->assertSame( array(), $this->errors() );
		}

		/**
		 * Bug: swapped or wrong buyer fields fail at MONEI, fail open, and no buyer is ever linked.
		 *
		 * @dataProvider buyers
		 */
		public function test_create_request_takes_the_buyer_data_from_the_wp_user( array $meta, array $expected ) {
			$GLOBALS['mcs_meta'][ self::USER_ID ] = $meta;
			$this->customers->expects( $this->once() )->method( 'create' )
				->with(
					$expected + array(
						'metadata' => array(
							'platform'           => 'woocommerce',
							'platformCustomerId' => '7',
						),
					)
				)
				->willReturn( $this->customer( 'cus_new' ) );

			$this->assertSame( 'cus_new', $this->add( self::USER_ID, '+34 699 000 000' )['customerId'] );
		}

		public function buyers(): array {
			return array(
				'billing name and phone win over display name and fallback' => array(
					array(
						'billing_first_name' => array( 'Ana' ),
						'billing_last_name'  => array( 'Buyer' ),
						'billing_phone'      => array( '600123456' ),
					),
					array(
						'email' => 'buyer@example.com',
						'name'  => 'Ana Buyer',
						'phone' => '600123456',
					),
				),
				'no billing data'                                           => array(
					array(),
					array(
						'email' => 'buyer@example.com',
						'name'  => 'buyer',
						'phone' => '+34 699 000 000',
					),
				),
				'first name only'                                           => array(
					array( 'billing_first_name' => array( 'Ana' ) ),
					array(
						'email' => 'buyer@example.com',
						'name'  => 'Ana',
						'phone' => '+34 699 000 000',
					),
				),
			);
		}

		/**
		 * Contract test 3. Bug: a customer API outage blocks checkout (an \Error is not an
		 * \Exception), or an empty ID is stored and reused.
		 */
		public function test_create_error_gives_a_payload_without_customer_id_and_no_mapping() {
			$this->customers->method( 'create' )->willThrowException( new \Error( 'outage' ) );

			$this->assertSame( array( 'amount' => 100 ), $this->add( self::USER_ID ) );
			$this->assertSame( array(), $this->stored() );
			$this->assertCount( 1, $this->errors() );
		}

		/**
		 * Contract test 2. Bug: a new MONEI customer on every payment.
		 */
		public function test_existing_mapping_is_reused_without_a_create_call() {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_known' );
			$this->customers->expects( $this->never() )->method( 'create' );

			$this->assertSame( 'cus_known', $this->link() );
		}

		/**
		 * Contract test 4. Bug: a key without the mode lets live payments send a test customer ID.
		 */
		public function test_a_test_mapping_is_never_returned_in_live_mode() {
			$GLOBALS['mcs_options']['monei_apikey_mode']          = 'live';
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_test' );
			$this->customers->expects( $this->once() )->method( 'create' )->willReturn( $this->customer( 'cus_live' ) );

			$this->assertSame( 'cus_live', $this->link() );
			$this->assertSame( array( 'cus_live' ), $this->stored( 'live' ) );
			$this->assertSame( array( 'cus_test' ), $this->stored( 'test' ) );
		}

		/**
		 * Contract test 8. Bug: the SDK throws on a null phone, so buyers with no phone are never linked.
		 */
		public function test_buyer_without_phone_is_created_without_a_phone_key_and_linked() {
			$this->customers->expects( $this->once() )->method( 'create' )
				->with(
					array(
						'email'    => 'buyer@example.com',
						'name'     => 'Ana Buyer',
						'metadata' => array(
							'platform'           => 'woocommerce',
							'platformCustomerId' => '7',
						),
					)
				)
				->willReturn( $this->customer( 'cus_new' ) );

			$id = $this->service()->get_or_create_customer_id( self::USER_ID, 'buyer@example.com', 'Ana Buyer', null );

			$this->assertSame( 'cus_new', $id );
			$this->assertSame( array( 'cus_new' ), $this->stored() );
		}

		/**
		 * Contract test 10, lost race. Bug: without the cache clear, the re-read returns the
		 * stale empty value and a lost race is handled as a failed save (no customerId, and
		 * the winner's customer is never used).
		 */
		public function test_concurrent_create_returns_the_stored_id_and_deletes_the_new_customer() {
			$cached = null;
			$this->customers->method( 'create' )->willReturnCallback(
				function () use ( &$cached ) {
					$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_winner' );
					$cached = \Monei\Services\get_user_meta( self::USER_ID, $this->key(), true );
					return $this->customer( 'cus_new' );
				}
			);
			$this->customers->expects( $this->once() )->method( 'delete' )->with( 'cus_new' );

			$this->assertSame( 'cus_winner', $this->link() );
			$this->assertSame( '', $cached, 'the cache must still hold the value read before the create' );
			$this->assertSame( array( false ), $GLOBALS['mcs_add_results'], 'the failed unique add path must run' );
			$this->assertSame( array( 'cus_winner' ), $this->stored() );
		}

		/**
		 * Contract test 10, non-atomic unique check: both rows land. Bug: our duplicate row
		 * stays and maps the user to a customer that was deleted at MONEI, or the winner's row is deleted.
		 */
		public function test_concurrent_create_removes_our_duplicate_row_and_keeps_the_winner() {
			$GLOBALS['mcs_ignore_unique'] = true;
			$this->customers->method( 'create' )->willReturnCallback(
				function () {
					$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_winner' );
					return $this->customer( 'cus_new' );
				}
			);
			$this->customers->expects( $this->once() )->method( 'delete' )->with( 'cus_new' );

			$this->assertSame( 'cus_winner', $this->link() );
			$this->assertSame( array( 'cus_winner' ), $this->stored() );
		}

		/**
		 * Contract test 10, failed delete of the new customer. Bug: an unmapped MONEI customer
		 * with PII and no record of its ID.
		 */
		public function test_concurrent_create_logs_the_new_id_when_its_delete_fails() {
			$this->customers->method( 'create' )->willReturnCallback(
				function () {
					$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_winner' );
					return $this->customer( 'cus_new' );
				}
			);
			$this->customers->method( 'delete' )->willThrowException( new \RuntimeException( 'timeout' ) );

			$this->assertSame( 'cus_winner', $this->link() );
			$this->assertCount( 1, $this->errors() );
			$this->assertStringContainsString( 'cus_new', $this->errors()[0][1] );
		}

		/**
		 * Contract test 10, nothing stored. Bug: an empty customerId sent on payments, or an
		 * unmapped MONEI customer that erasure never reaches.
		 */
		public function test_mapping_save_that_stores_nothing_returns_null_and_deletes_the_new_customer() {
			$GLOBALS['mcs_add_fails'] = true;
			$this->customers->method( 'create' )->willReturn( $this->customer( 'cus_new' ) );
			$this->customers->expects( $this->once() )->method( 'delete' )->with( 'cus_new' );

			$this->assertNull( $this->link() );
			$this->assertSame( array(), $this->stored() );
		}

		/**
		 * Contract test 12. Bug: a mapping stored under an empty account that no delete can resolve.
		 */
		public function test_empty_account_id_makes_no_create_call_and_stores_nothing() {
			$GLOBALS['mcs_options']['monei_test_accountid'] = '';
			$this->customers->expects( $this->never() )->method( 'create' );

			$this->assertNull( $this->link() );
			$this->assertSame( array(), $GLOBALS['mcs_meta'] );
		}

		/**
		 * Contract test 14. Bug: a user delete that ran while the create waited for MONEI read
		 * no mapping, so the new MONEI customer with PII outlives the delete with no mapping and no log.
		 *
		 * @dataProvider deletes_during_create
		 */
		public function test_user_deleted_during_create_deletes_the_new_customer( callable $delete_starts ) {
			$this->customers->method( 'create' )->willReturnCallback(
				function () use ( $delete_starts ) {
					$delete_starts();
					return $this->customer( 'cus_new' );
				}
			);
			$this->customers->expects( $this->once() )->method( 'delete' )->with( 'cus_new' );

			$this->assertNull( $this->link() );
			$this->assertSame( array(), $this->stored() );
		}

		public function deletes_during_create(): array {
			return array(
				'delete hook wrote the marker' => array(
					function () {
						$GLOBALS['mcs_options'][ 'monei_customer_deleting_' . self::USER_ID ] = '1';
					},
				),
				'user row is gone'             => array(
					function () {
						unset( $GLOBALS['mcs_users'][ self::USER_ID ] );
					},
				),
			);
		}
	}
}
