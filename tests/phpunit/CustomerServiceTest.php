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

	/**
	 * A WooCommerce Subscriptions or YITH subscription: the plugin reads its status and its MONEI sequence.
	 */
	class MCS_Fake_Subscription {
		private $status;
		private $sequence_id;

		public function __construct( string $status, string $sequence_id ) {
			$this->status      = $status;
			$this->sequence_id = $sequence_id;
		}

		public function has_status( $statuses ) {
			return in_array( $this->status, (array) $statuses, true );
		}

		public function get_meta( $key, $single = false ) {
			return '_monei_sequence_id' === $key ? $this->sequence_id : '';
		}

		public function get( $prop ) {
			return '_monei_sequence_id' === $prop ? $this->sequence_id : '';
		}
	}

	// The subscription plugins are global functions, and CustomerService checks them with function_exists.

	function wcs_get_users_subscriptions( $user_id ) {
		if ( $GLOBALS['mcs_subscriptions_throw'] ) {
			throw new \Error( 'subscriptions failed' );
		}
		return $GLOBALS['mcs_wcs_subscriptions'][ $user_id ] ?? array();
	}

	function YITH_WC_Subscription() {
		return new class() {
			public function get_user_subscriptions( $user_id ) {
				return isset( $GLOBALS['mcs_yith_subscriptions'][ $user_id ] ) ? array_keys( $GLOBALS['mcs_yith_subscriptions'][ $user_id ] ) : 0;
			}
		};
	}

	function ywsbs_get_subscription( $subscription_id ) {
		foreach ( $GLOBALS['mcs_yith_subscriptions'] as $subscriptions ) {
			if ( isset( $subscriptions[ $subscription_id ] ) ) {
				return $subscriptions[ $subscription_id ];
			}
		}
		return false;
	}

	if ( ! class_exists( 'WC_Payment_Token' ) ) {
		class WC_Payment_Token {
			private $gateway_id;
			private $user_id;
			private $token;

			public function __construct( $gateway_id = '', $user_id = 0, $token = '' ) {
				$this->gateway_id = $gateway_id;
				$this->user_id    = $user_id;
				$this->token      = $token;
			}

			public function get_gateway_id() {
				return $this->gateway_id;
			}

			public function get_user_id() {
				return $this->user_id;
			}

			public function get_token() {
				return $this->token;
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

	function update_option( $key, $value, $autoload = null ) {
		$GLOBALS['mcs_options'][ $key ]          = $value;
		$GLOBALS['mcs_option_autoload'][ $key ] = $autoload;
		return true;
	}

	function delete_option( $key ) {
		unset( $GLOBALS['mcs_options'][ $key ] );
		return true;
	}

	// add_action and add_filter record their arguments for the wiring tests.
	function add_action( ...$args ) {
		$GLOBALS['mcs_hooks'][] = $args;
		return true;
	}

	function add_filter( ...$args ) {
		$GLOBALS['mcs_hooks'][] = $args;
		return true;
	}

	function is_multisite() {
		return $GLOBALS['mcs_multisite'];
	}

	function get_current_user_id() {
		return $GLOBALS['mcs_current_user'];
	}

	function __( $text, $domain = 'default' ) {
		return $text;
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

	function get_user_by( $field, $value ) {
		if ( $GLOBALS['mcs_get_user_by_throws'] ) {
			throw new \Error( 'get_user_by failed' );
		}
		foreach ( $GLOBALS['mcs_users'] as $user ) {
			if ( 'email' === $field && $user->user_email === $value ) {
				return $user;
			}
		}
		return false;
	}

	function wp_cache_delete( $key, $group = '' ) {
		if ( 'user_meta' === $group ) {
			unset( $GLOBALS['mcs_meta_cache'][ (int) $key ] );
		}
		return true;
	}
}

namespace Monei\Services\sdk {

	// For the real MoneiSdkClientFactory in test 9.

	function WC_Monei() {
		return new class() {
			public $version = '0.0.0';

			public function plugin_path() {
				return dirname( __DIR__, 2 );
			}
		};
	}

	function WC() {
		return (object) array( 'version' => '0.0.0' );
	}

	function get_bloginfo( $show ) {
		return '0.0';
	}
}

namespace Monei\Tests {

	use Monei\Api\CustomersApi;
	use Monei\ApiException;
	use Monei\Configuration;
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

		use SourceCode;

		const USER_ID = 7;

		const ADMIN_ID = 1;

		/** @var CustomersApi&MockObject */
		private $customers;

		/** @var array<string, CustomersApi&MockObject> The customers API of the client that get_client_for_mode returns. */
		private $mode_customers;

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
			$GLOBALS['mcs_get_user_by_throws'] = false;
			$GLOBALS['mcs_option_autoload']  = array();
			$GLOBALS['mcs_multisite']        = false;
			$GLOBALS['mcs_current_user']     = self::ADMIN_ID;
			$GLOBALS['mcs_wcs_subscriptions']  = array();
			$GLOBALS['mcs_yith_subscriptions'] = array();
			$GLOBALS['mcs_subscriptions_throw'] = false;
			$GLOBALS['mcs_users']           = array(
				self::USER_ID => (object) array(
					'ID'           => self::USER_ID,
					'user_email'   => 'buyer@example.com',
					'display_name' => 'buyer',
				),
				self::ADMIN_ID => (object) array(
					'ID'           => self::ADMIN_ID,
					'user_email'   => 'admin@example.com',
					'display_name' => 'admin',
				),
			);
			$GLOBALS['wpdb']                = new FakeWpdb();
			WC_Monei_Logger::$records       = array();

			$this->customers      = $this->createMock( CustomersApi::class );
			$this->mode_customers = array(
				'test' => $this->createMock( CustomersApi::class ),
				'live' => $this->createMock( CustomersApi::class ),
			);
		}

		protected function tearDown(): void {
			Configuration::setDefaultConfiguration( new Configuration() );
		}

		private function client( CustomersApi $customers ): MoneiClient {
			$client            = $this->createMock( MoneiClient::class );
			$client->customers = $customers;
			return $client;
		}

		private function service(): CustomerService {
			$factory = $this->createMock( MoneiSdkClientFactory::class );
			$factory->method( 'get_client' )->willReturn( $this->client( $this->customers ) );
			$factory->method( 'get_client_for_mode' )->willReturnCallback(
				function ( string $mode ) {
					return $this->client( $this->mode_customers[ $mode ] );
				}
			);
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
		 * An API error as the SDK throws it: the body is the raw JSON string MONEI sent (sandbox check c).
		 */
		private function api_error( string $message, int $status = 404 ): ApiException {
			$body = json_encode(
				array(
					'statusCode' => $status,
					'message'    => $message,
				)
			);
			return new ApiException( "[$status] Error connecting to the API", $status, array(), $body );
		}

		/**
		 * Runs CustomerService::create_payment with a $create that records each payload and
		 * answers from $results in order (an exception is thrown).
		 */
		private function create_with( array &$payload, array $results, array &$sent ) {
			return $this->service()->create_payment(
				function ( array $sent_payload ) use ( &$results, &$sent ) {
					$sent[] = $sent_payload;
					$result = array_shift( $results );
					if ( $result instanceof \Throwable ) {
						throw $result;
					}
					return $result;
				},
				$payload,
				self::USER_ID
			);
		}

		/**
		 * Contract test 6, stale customer. Bug: a buyer deleted in the MONEI dashboard can never
		 * pay again, or the caller logs a payload that was not sent.
		 */
		public function test_stale_customer_is_forgotten_and_the_payment_retried_once_without_it() {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_stale' );
			$payload = array(
				'amount'     => 100,
				'customerId' => 'cus_stale',
			);
			$sent    = array();

			$result = $this->create_with( $payload, array( $this->api_error( 'Customer not found' ), 'payment' ), $sent );

			$this->assertSame( 'payment', $result );
			$this->assertSame( array( array( 'amount' => 100, 'customerId' => 'cus_stale' ), array( 'amount' => 100 ) ), $sent );
			$this->assertSame( array( 'amount' => 100 ), $payload );
			$this->assertSame( array(), $this->stored() );
		}

		/**
		 * Contract test 6, newer mapping. Bug: the retry deletes the ID that another request
		 * stored meanwhile, and the next payment creates a second MONEI customer.
		 */
		public function test_stale_customer_retry_keeps_a_newer_mapping() {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_newer' );
			$payload = array( 'customerId' => 'cus_stale' );
			$sent    = array();

			$this->create_with( $payload, array( $this->api_error( 'Customer not found' ), 'payment' ), $sent );

			$this->assertSame( array( 'cus_newer' ), $this->stored() );
		}

		/**
		 * Contract test 6, failed retry. Bug: a retry loop, or a swallowed payment error.
		 */
		public function test_failure_of_the_retry_is_rethrown() {
			$payload = array( 'customerId' => 'cus_stale' );
			$sent    = array();
			$second  = $this->api_error( 'Customer not found' );

			try {
				$this->create_with( $payload, array( $this->api_error( 'Customer not found' ), $second, 'payment' ), $sent );
				$this->fail( 'the failure of the retry must be rethrown' );
			} catch ( ApiException $e ) {
				$this->assertSame( $second, $e );
			}
			$this->assertCount( 2, $sent );
		}

		/**
		 * Contract test 6, other errors. Bug: a retry on a 404 that is not about the customer
		 * (status alone is not enough), or a retry of a payload that has no customer to drop.
		 *
		 * @dataProvider errors_without_retry
		 */
		public function test_other_errors_are_rethrown_without_retry( array $payload, string $message, int $status ) {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_known' );
			$sent  = array();
			$error = $this->api_error( $message, $status );

			try {
				$this->create_with( $payload, array( $error, 'payment' ), $sent );
				$this->fail( 'the error must be rethrown' );
			} catch ( ApiException $e ) {
				$this->assertSame( $error, $e );
			}
			$this->assertSame( array( $payload ), $sent );
			$this->assertSame( array( 'cus_known' ), $this->stored() );
		}

		public function errors_without_retry(): array {
			return array(
				'same status, other message' => array( array( 'customerId' => 'cus_known' ), 'Payment method not found', 404 ),
				'same message, other status' => array( array( 'customerId' => 'cus_known' ), 'Customer not found', 400 ),
				'no customerId in payload'   => array( array( 'amount' => 100 ), 'Customer not found', 404 ),
			);
		}

		/**
		 * Contract test 5, deleteCustomer 404. Bug: a customer already deleted at MONEI logs an
		 * error and keeps a row that no delete can ever remove.
		 */
		public function test_delete_customer_logs_a_404_at_debug_level_and_removes_the_row() {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_gone' );
			$this->mode_customers['test']->method( 'delete' )->willThrowException( $this->api_error( 'Customer not found' ) );

			$this->service()->delete_customer( self::USER_ID );

			$this->assertSame( array(), $this->stored() );
			$this->assertSame( array( WC_Monei_Logger::LEVEL_INFO ), array_column( WC_Monei_Logger::$records, 0 ) );
		}

		/**
		 * Contract test 5, deleteCustomer 500. Bug: an outage drops the row and leaves the
		 * customer at MONEI with no record of its ID.
		 */
		public function test_delete_customer_keeps_the_row_and_logs_an_error_on_a_500() {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_kept' );
			$this->mode_customers['test']->method( 'delete' )->willThrowException( $this->api_error( 'Internal error', 500 ) );

			$this->service()->delete_customer( self::USER_ID );

			$this->assertSame( array( 'cus_kept' ), $this->stored() );
			$this->assertCount( 1, $this->errors() );
			$this->assertStringContainsString( 'MONEI customer cus_kept (account acc_1, mode test)', $this->errors()[0][1] );
		}

		/**
		 * Contract test 7, rows of both modes. Bug: a live row deleted with the test key gets
		 * 404, the row is removed, and the live customer stays at MONEI.
		 */
		public function test_delete_customer_deletes_each_row_with_the_client_of_its_own_mode() {
			$GLOBALS['mcs_meta'][ self::USER_ID ] = array(
				$this->key( 'test' ) => array( 'cus_test' ),
				$this->key( 'live' ) => array( 'cus_live' ),
			);
			$this->mode_customers['test']->expects( $this->once() )->method( 'delete' )->with( 'cus_test' );
			$this->mode_customers['live']->expects( $this->once() )->method( 'delete' )->with( 'cus_live' );

			$this->service()->delete_customer( self::USER_ID );

			$this->assertSame( array(), $GLOBALS['mcs_meta'][ self::USER_ID ] );
		}

		/**
		 * Contract test 7, rows with no matching key. Bug: a row matched by account ID alone is
		 * deleted with the key of another account or an empty key, gets an error, and is lost silently.
		 */
		public function test_delete_customer_keeps_rows_with_no_configured_key() {
			$GLOBALS['mcs_options']['monei_live_apikey'] = '';
			$GLOBALS['mcs_meta'][ self::USER_ID ]        = array(
				'_monei_customer_acc_other_test' => array( 'cus_other' ),
				$this->key( 'live' )             => array( 'cus_live' ),
			);
			$this->mode_customers['test']->expects( $this->never() )->method( 'delete' );
			$this->mode_customers['live']->expects( $this->never() )->method( 'delete' );

			$this->service()->delete_customer( self::USER_ID );

			$this->assertSame( array( 'cus_other' ), $GLOBALS['mcs_meta'][ self::USER_ID ]['_monei_customer_acc_other_test'] );
			$this->assertSame( array( 'cus_live' ), $this->stored( 'live' ) );
			$this->assertCount( 2, $this->errors() );
			$this->assertStringContainsString( 'MONEI customer cus_other (account acc_other, mode test)', $this->errors()[0][1] );
			$this->assertStringContainsString( 'MONEI customer cus_live (account acc_1, mode live)', $this->errors()[1][1] );
		}

		/**
		 * Contract test 7, a row that throws an \Error. Bug: one failing row stops the loop, and
		 * the other customers are lost with the user delete.
		 */
		public function test_delete_customer_goes_on_after_a_row_that_throws_an_error() {
			$GLOBALS['mcs_meta'][ self::USER_ID ] = array(
				$this->key( 'test' ) => array( 'cus_test' ),
				$this->key( 'live' ) => array( 'cus_live' ),
			);
			$this->mode_customers['test']->method( 'delete' )->willThrowException( new \Error( 'boom' ) );
			$this->mode_customers['live']->expects( $this->once() )->method( 'delete' )->with( 'cus_live' );

			$this->service()->delete_customer( self::USER_ID );

			$this->assertSame( array( 'cus_test' ), $this->stored( 'test' ) );
			$this->assertSame( array(), $this->stored( 'live' ) );
			$this->assertCount( 1, $this->errors() );
			$this->assertStringContainsString( 'MONEI customer cus_test (account acc_1, mode test)', $this->errors()[0][1] );
		}

		/**
		 * Bug: reading only the first value of a key leaves a MONEI customer with PII; deleting
		 * without the value removes the failed row too.
		 */
		public function test_delete_customer_handles_each_value_of_a_key_as_its_own_row() {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_a', 'cus_b' );
			$deleted = array();
			$this->mode_customers['test']->method( 'delete' )->willReturnCallback(
				function ( string $id ) use ( &$deleted ) {
					$deleted[] = $id;
					if ( 'cus_b' === $id ) {
						throw $this->api_error( 'Internal error', 500 );
					}
				}
			);

			$this->service()->delete_customer( self::USER_ID );

			$this->assertSame( array( 'cus_a', 'cus_b' ), $deleted );
			$this->assertSame( array( 'cus_b' ), $this->stored() );
		}

		/**
		 * Contract test 9. Bug: the client for the other mode writes its key into the shared
		 * default Configuration, so the main client then sends the other mode's key; or it
		 * replaces the cached main client.
		 */
		public function test_client_for_the_other_mode_does_not_change_the_main_client_key() {
			$factory = new MoneiSdkClientFactory( new ApiKeyService() );

			$factory->get_client();
			$live = $factory->get_client_for_mode( 'live' );
			$main = $factory->get_client();

			$this->assertSame( 'pk_test_key', $main->getConfig()->getApiKey( 'Authorization' ) );
			$this->assertSame( 'pk_live_key', $live->getConfig()->getApiKey( 'Authorization' ) );
		}

		/**
		 * The callback that init() bound to a hook, with its priority and accepted args.
		 *
		 * @return array{0: callable, 1: int, 2: int}|null
		 */
		private function hook( CustomerService $service, string $name ): ?array {
			$before = count( $GLOBALS['mcs_hooks'] );
			$service->init();
			foreach ( array_slice( $GLOBALS['mcs_hooks'], $before ) as $hook ) {
				if ( $hook[0] === $name ) {
					return array( $hook[1], $hook[2] ?? 10, $hook[3] ?? 1 );
				}
			}
			return null;
		}

		/**
		 * Contract test 11. Bug: every card delete of an unlinked buyer calls MONEI with an
		 * empty customer ID and logs an error. The live mapping shows that only the
		 * current-mode lookup stops the call.
		 */
		public function test_delete_payment_method_without_mapping_makes_no_call() {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key( 'live' ) ] = array( 'cus_live' );
			$this->customers->expects( $this->never() )->method( 'deletePaymentMethod' );

			$this->service()->delete_payment_method( self::USER_ID, 'tok_1' );

			$this->assertSame( array(), WC_Monei_Logger::$records );
		}

		/**
		 * Contract test 5 (deletePaymentMethod) and the 409 and other-error rows. Bug: a card
		 * saved before the link (404) logs an error or drops the mapping, and the next payment
		 * creates a second MONEI customer; a real failure is not logged with the IDs.
		 *
		 * @dataProvider payment_method_errors
		 */
		public function test_delete_payment_method_error_is_logged_at_its_level_and_keeps_the_mapping( int $status, int $level ) {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ] = array( 'cus_known' );
			$this->customers->method( 'deletePaymentMethod' )->willThrowException( $this->api_error( 'Some error', $status ) );

			$this->service()->delete_payment_method( self::USER_ID, 'tok_1' );

			$this->assertSame( array( 'cus_known' ), $this->stored() );
			$this->assertCount( 1, WC_Monei_Logger::$records );
			$this->assertSame( $level, WC_Monei_Logger::$records[0][0] );
			$this->assertStringContainsString( 'MONEI customer cus_known (account acc_1, mode test)', WC_Monei_Logger::$records[0][1] );
		}

		public function payment_method_errors(): array {
			return array(
				'404' => array( 404, WC_Monei_Logger::LEVEL_INFO ),
				'409' => array( 409, WC_Monei_Logger::LEVEL_WARNING ),
				'500' => array( 500, WC_Monei_Logger::LEVEL_ERROR ),
			);
		}

		/**
		 * Bug: the WC row ID ($token_id) is sent instead of the token string, so every card
		 * delete gets a 404 that logs at debug level, and no card is ever deleted at MONEI; or
		 * the current user (an admin) is used instead of the token owner; or a token of
		 * another gateway is sent to MONEI.
		 *
		 * @dataProvider token_gateways
		 */
		public function test_token_hook_deletes_the_owners_token_for_monei_gateways_only( string $gateway, bool $calls ) {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ]  = array( 'cus_owner' );
			$GLOBALS['mcs_meta'][ self::ADMIN_ID ][ $this->key() ] = array( 'cus_admin' );
			$this->customers->expects( $calls ? $this->once() : $this->never() )->method( 'deletePaymentMethod' )->with( 'cus_owner', 'tok_string' );
			$service = $this->service();

			call_user_func( $this->hook( $service, 'woocommerce_payment_token_deleted' )[0], 55, new \WC_Payment_Token( $gateway, self::USER_ID, 'tok_string' ) );
		}

		public function token_gateways(): array {
			return array(
				'card'          => array( 'monei', true ),
				'apple, google' => array( 'monei_apple_google', true ),
				'other gateway' => array( 'stripe', false ),
			);
		}

		/**
		 * Bug: a callback that deletes for the current user (an admin) deletes the admin's own customer.
		 *
		 * @dataProvider user_delete_hooks
		 */
		public function test_user_delete_hooks_delete_the_deleted_users_customer( string $hook_name ) {
			$GLOBALS['mcs_meta'][ self::USER_ID ][ $this->key() ]  = array( 'cus_owner' );
			$GLOBALS['mcs_meta'][ self::ADMIN_ID ][ $this->key() ] = array( 'cus_admin' );
			$this->mode_customers['test']->expects( $this->once() )->method( 'delete' )->with( 'cus_owner' );
			$service = $this->service();

			call_user_func( $this->hook( $service, $hook_name )[0], self::USER_ID );

			$this->assertSame( array( 'cus_admin' ), $GLOBALS['mcs_meta'][ self::ADMIN_ID ][ $this->key() ] );
		}

		public function user_delete_hooks(): array {
			return array(
				'delete_user'      => array( 'delete_user' ),
				'wpmu_delete_user' => array( 'wpmu_delete_user' ),
			);
		}

		/**
		 * Bug: an escaping error breaks a My Account card delete. The token stub throws in code
		 * that runs outside the service methods, so only the hook-level catch can stop it.
		 */
		public function test_token_hook_lets_no_error_escape() {
			$token = $this->createMock( \WC_Payment_Token::class );
			$token->method( 'get_gateway_id' )->willThrowException( new \Error( 'broken token' ) );

			call_user_func( $this->hook( $this->service(), 'woocommerce_payment_token_deleted' )[0], 55, $token );

			$this->assertCount( 1, $this->errors() );
		}

		/**
		 * Contract v8 marker. Bug: an escaping error stops wp_delete_user before
		 * wc_delete_user_data runs; a marker written after the meta read misses a create that
		 * saved its mapping in between. The all-meta read throws, which no per-row catch can
		 * stop, and the marker must already be stored.
		 */
		public function test_user_delete_hook_writes_the_marker_first_and_lets_no_error_escape() {
			$GLOBALS['mcs_all_meta_throws'] = true;

			call_user_func( $this->hook( $this->service(), 'delete_user' )[0], self::USER_ID );

			$this->assertSame( '1', $GLOBALS['mcs_options'][ 'monei_customer_deleting_' . self::USER_ID ] );
			$this->assertFalse( $GLOBALS['mcs_option_autoload'][ 'monei_customer_deleting_' . self::USER_ID ] );
			$this->assertCount( 1, $this->errors() );
		}

		/**
		 * Bug: every user delete leaves its marker option behind forever.
		 */
		public function test_deleted_user_hook_removes_the_marker() {
			$GLOBALS['mcs_options'][ 'monei_customer_deleting_' . self::USER_ID ] = '1';

			call_user_func( $this->hook( $this->service(), 'deleted_user' )[0], self::USER_ID );

			$this->assertArrayNotHasKey( 'monei_customer_deleting_' . self::USER_ID, $GLOBALS['mcs_options'] );
		}

		/**
		 * The eraser through the callback that the registered filter returns.
		 */
		private function erase( string $email ): array {
			$erasers = call_user_func( $this->hook( $this->service(), 'wp_privacy_personal_data_erasers' )[0], array() );
			return call_user_func( $erasers['monei-customer']['callback'], $email, 1 );
		}

		/**
		 * Bug: WordPress fails the whole erasure request when a response lacks one of the four
		 * keys, or reports a complete erasure while the PII stays at MONEI.
		 *
		 * @dataProvider erasures
		 */
		public function test_eraser_reports_what_was_removed_and_retained( string $email, ?int $second_status, bool $removed, bool $retained, int $messages ) {
			$GLOBALS['mcs_meta'][ self::USER_ID ] = array(
				$this->key( 'test' ) => array( 'cus_test' ),
				$this->key( 'live' ) => array( 'cus_live' ),
			);
			if ( null !== $second_status ) {
				$this->mode_customers['live']->method( 'delete' )->willThrowException( $this->api_error( 'Conflict', $second_status ) );
			}

			$this->assertSame(
				array(
					'items_removed'  => $removed,
					'items_retained' => $retained,
					'messages'       => array_fill( 0, $messages, 'A MONEI customer could not be deleted. The MONEI log has its ID.' ),
					'done'           => true,
				),
				$this->erase( $email )
			);
			$this->assertCount( $retained ? 1 : 0, $this->errors() );
		}

		public function erasures(): array {
			return array(
				'no user for the email' => array( 'nobody@example.com', null, false, false, 0 ),
				'all rows deleted'      => array( 'buyer@example.com', null, true, false, 0 ),
				'one row kept on a 409' => array( 'buyer@example.com', 409, true, true, 1 ),
			);
		}

		/**
		 * Bug: get_user_by throws outside every delete_customer catch, and the error escapes or
		 * the erasure is reported as complete while the PII stays at MONEI.
		 */
		public function test_eraser_reports_retained_data_when_it_fails_before_the_delete() {
			$GLOBALS['mcs_get_user_by_throws'] = true;

			$result = $this->erase( 'buyer@example.com' );

			$this->assertFalse( $result['items_removed'] );
			$this->assertTrue( $result['items_retained'] );
			$this->assertNotEmpty( $result['messages'] );
			$this->assertTrue( $result['done'] );
		}

		/**
		 * Bugs: without accepted_args 2 every card delete throws ArgumentCountError; at the
		 * default priority WooCommerce deletes every token first, one MONEI call each; on
		 * multisite, delete_user only removes the user from one site, so binding it deletes the
		 * MONEI customer of a user who still exists; an eraser without a name or callback fails
		 * every erasure request.
		 */
		public function test_init_binds_the_hooks_with_their_arguments() {
			$service = $this->service();

			$this->assertSame( 2, $this->hook( $service, 'woocommerce_payment_token_deleted' )[2] );
			$this->assertSame( 9, $this->hook( $service, 'delete_user' )[1] );

			$erasers = call_user_func( $this->hook( $service, 'wp_privacy_personal_data_erasers' )[0], array( 'other' => array( 'x' ) ) );
			$this->assertSame( array( 'other', 'monei-customer' ), array_keys( $erasers ) );
			$this->assertNotEmpty( $erasers['monei-customer']['eraser_friendly_name'] );
			$this->assertIsCallable( $erasers['monei-customer']['callback'] );

			$GLOBALS['mcs_multisite'] = true;
			$this->assertNull( $this->hook( $service, 'delete_user' ) );
			$this->assertNotNull( $this->hook( $service, 'wpmu_delete_user' ) );
		}

		/**
		 * Bug: the container is lazy, so without this call no delete hook is bound and every
		 * delete at MONEI stops without any error.
		 */
		public function test_plugin_init_binds_the_customer_hooks() {
			$this->assertStringContainsString( 'ContainerProvider::getContainer()->get(CustomerService::class)->init();', $this->code( 'class-woocommerce-gateway-monei.php' ) );
		}

		private function seed_both_modes(): void {
			$GLOBALS['mcs_meta'][ self::USER_ID ] = array(
				$this->key( 'test' ) => array( 'cus_test' ),
				$this->key( 'live' ) => array( 'cus_live' ),
			);
		}

		/**
		 * Contract test 13. Bug: deleting or erasing a subscriber makes MONEI fail every later
		 * renewal (E999); or a cancelled subscriber, or one paid with another gateway, is never deleted.
		 *
		 * @dataProvider subscriptions
		 */
		public function test_delete_customer_keeps_the_customer_of_an_active_monei_subscriber( array $wcs, array $yith, bool $throws, bool $kept ) {
			$this->seed_both_modes();
			$GLOBALS['mcs_wcs_subscriptions'][ self::USER_ID ]  = $wcs;
			$GLOBALS['mcs_yith_subscriptions'][ self::USER_ID ] = $yith;
			$GLOBALS['mcs_subscriptions_throw']                 = $throws;
			$this->mode_customers['test']->expects( $kept ? $this->never() : $this->once() )->method( 'delete' )->with( 'cus_test' );
			$this->mode_customers['live']->expects( $kept ? $this->never() : $this->once() )->method( 'delete' )->with( 'cus_live' );

			$this->service()->delete_customer( self::USER_ID );

			$this->assertSame( $kept ? array( 'cus_test' ) : array(), $this->stored( 'test' ) );
			$this->assertSame( $kept ? array( 'cus_live' ) : array(), $this->stored( 'live' ) );
			$logged = implode( "\n", array_column( $this->errors(), 1 ) );
			if ( $kept ) {
				$this->assertStringContainsString( 'MONEI customer cus_test (account acc_1, mode test)', $logged );
				$this->assertStringContainsString( 'MONEI customer cus_live (account acc_1, mode live)', $logged );
			} else {
				$this->assertSame( '', $logged );
			}
		}

		public function subscriptions(): array {
			return array(
				'active, MONEI sequence'                  => array( array( new \MCS_Fake_Subscription( 'active', 'seq_1' ) ), array(), false, true ),
				'cancelled and expired, MONEI sequence'   => array(
					array( new \MCS_Fake_Subscription( 'cancelled', 'seq_1' ), new \MCS_Fake_Subscription( 'expired', 'seq_2' ) ),
					array(),
					false,
					false,
				),
				'active, other gateway'                   => array( array( new \MCS_Fake_Subscription( 'active', '' ) ), array(), false, false ),
				'subscription check fails'                => array( array(), array(), true, true ),
				'YITH overdue, MONEI sequence'            => array( array(), array( 11 => new \MCS_Fake_Subscription( 'overdue', 'seq_1' ) ), false, true ),
				'YITH cancelled, MONEI sequence'          => array( array(), array( 11 => new \MCS_Fake_Subscription( 'cancelled', 'seq_1' ) ), false, false ),
			);
		}

		/**
		 * Bug: the eraser reports a complete erasure, or gives the admin no reason, while the
		 * MONEI customer of an active subscriber is kept.
		 */
		public function test_eraser_says_why_the_customer_of_an_active_subscriber_is_kept() {
			$this->seed_both_modes();
			$GLOBALS['mcs_wcs_subscriptions'][ self::USER_ID ] = array( new \MCS_Fake_Subscription( 'active', 'seq_1' ) );

			$this->assertSame(
				array(
					'items_removed'  => false,
					'items_retained' => true,
					'messages'       => array( 'The MONEI customer is kept because the user has an active subscription that MONEI charges.' ),
					'done'           => true,
				),
				$this->erase( 'buyer@example.com' )
			);
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
		 * Contract test 14. The create mock runs the user delete while the create waits for
		 * MONEI. Bug: that delete read no mapping, so the new MONEI customer with PII outlives
		 * it with no mapping and no log; or the hook and the re-check use different markers.
		 *
		 * @dataProvider deletes_during_create
		 */
		public function test_user_deleted_during_create_deletes_the_new_customer( bool $delete_finishes ) {
			$service = $this->service();
			$this->customers->method( 'create' )->willReturnCallback(
				function () use ( $service, $delete_finishes ) {
					call_user_func( $this->hook( $service, 'delete_user' )[0], self::USER_ID );
					if ( $delete_finishes ) {
						unset( $GLOBALS['mcs_users'][ self::USER_ID ] );
						call_user_func( $this->hook( $service, 'deleted_user' )[0], self::USER_ID );
					}
					return $this->customer( 'cus_new' );
				}
			);
			$this->customers->expects( $this->once() )->method( 'delete' )->with( 'cus_new' );

			$this->assertNull( $service->get_or_create_customer_id( self::USER_ID, 'buyer@example.com', 'Ana Buyer', null ) );
			$this->assertSame( array(), $this->stored() );
		}

		public function deletes_during_create(): array {
			return array(
				'delete hook is running' => array( false ),
				'delete has finished'    => array( true ),
			);
		}
	}
}
