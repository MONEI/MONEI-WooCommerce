<?php
/**
 * ConnectService: the single-use state, the test call of each new key, and the swap of credentials.
 *
 * Every test names the bug it catches.
 *
 * @package Monei
 */

namespace Monei\Tests;

use Monei\ConnectException;
use Monei\Services\ConnectService;
use PHPUnit\Framework\TestCase;

class ConnectServiceTest extends TestCase {

	const NOW      = 1700000000;
	const STATE    = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
	const REDIRECT = 'https://shop.example/wp-admin/admin-post.php?action=monei_connect';
	const BROKER   = 'https://oauth.example';

	/** @var array[] */
	private $exchanges = array();
	/** @var string[] */
	private $probed = array();
	/** @var array|ConnectException */
	private $exchange_answer = array();
	/** @var array<string, int> */
	private $statuses = array();

	private function service( MemoryOptionStore $store ): ConnectService {
		return new ConnectService(
			$store,
			function () {
				return self::NOW;
			},
			function ( ...$args ) {
				$this->exchanges[] = $args;
				if ( $this->exchange_answer instanceof ConnectException ) {
					throw $this->exchange_answer;
				}
				return $this->exchange_answer;
			},
			function ( string $api_key ) {
				$this->probed[] = $api_key;
				return $this->statuses[ $api_key ] ?? 200;
			},
			self::BROKER
		);
	}

	/**
	 * A shop with manual pk_ credentials in both modes, in live mode, and a fresh state entry.
	 */
	private static function manual_shop( array $extra = array() ): MemoryOptionStore {
		return new MemoryOptionStore(
			$extra + array(
				'monei_apikey_mode'                        => 'live',
				'monei_live_accountid'                     => 'acc-live',
				'monei_live_apikey'                        => 'pk_live_old',
				'monei_test_accountid'                     => 'acc-test-old',
				'monei_test_apikey'                        => 'pk_test_old',
				ConnectService::STATE_PREFIX . self::STATE => array(
					'verifier'    => 'the-verifier',
					'redirectUri' => self::REDIRECT,
					'createdAt'   => self::NOW - 60,
				),
			)
		);
	}

	private static function keys( string $mode, string $account ): array {
		return array(
			'accountId'     => $account,
			'apiKey'        => "mk_{$mode}_new",
			'signingSecret' => "mksec_{$mode}_new",
		);
	}

	private static function credentials( MemoryOptionStore $store ): array {
		$credentials = array();
		foreach ( array( 'apikey_mode', 'live_accountid', 'live_apikey', 'live_signing_secret', 'test_accountid', 'test_apikey', 'test_signing_secret' ) as $field ) {
			$credentials[ $field ] = $store->get( "monei_$field", null );
		}
		return $credentials;
	}

	private static function codes( array $result ): array {
		return array_map(
			function ( $problem ) {
				return array( $problem['mode'], $problem['code'] );
			},
			$result['problems']
		);
	}

	/**
	 * Bug: a callback code is accepted without the stored state, after 20 min, as a second callback that lost the
	 * delete race, or through a state that is not ours (the shape check guards the option name).
	 *
	 * @dataProvider refused_states
	 */
	public function test_a_state_that_is_not_stored_and_fresh_reaches_no_exchange( string $state, array $extra, array $lost_deletes, string $code ) {
		$store               = self::manual_shop( $extra );
		$store->lost_deletes = $lost_deletes;
		$before              = self::credentials( $store );

		$result = $this->service( $store )->complete( $state, 'the-code' );

		$this->assertSame( array(), $this->exchanges );
		$this->assertSame( array( array( null, $code ) ), self::codes( $result ) );
		$this->assertSame( $before, self::credentials( $store ) );
	}

	public function refused_states(): array {
		$entry = array(
			'verifier'    => 'the-verifier',
			'redirectUri' => self::REDIRECT,
			'createdAt'   => self::NOW - 60,
		);
		return array(
			'unknown'        => array( str_repeat( 'b', 43 ), array(), array(), 'unknownState' ),
			'not our shape'  => array( 'x', array( ConnectService::STATE_PREFIX . 'x' => $entry ), array(), 'unknownState' ),
			'20 min old'     => array(
				self::STATE,
				array( ConnectService::STATE_PREFIX . self::STATE => array( 'createdAt' => self::NOW - 1200 ) + $entry ),
				array(),
				'expiredState',
			),
			'lost the race'  => array( self::STATE, array(), array( ConnectService::STATE_PREFIX . self::STATE ), 'unknownState' ),
		);
	}

	/**
	 * Bug: a state is accepted twice, so a replayed callback URL exchanges again. Also pins the exchange arguments:
	 * another verifier or redirect URI than the start sent is always invalid_grant.
	 */
	public function test_a_state_is_single_use() {
		$store                 = self::manual_shop();
		$this->exchange_answer = array( 'test' => self::keys( 'test', 'acc-test-old' ) );
		$service               = $this->service( $store );

		$service->complete( self::STATE, 'the-code' );
		$second = $service->complete( self::STATE, 'the-code' );

		$this->assertSame( array( array( 'the-code', 'the-verifier', 'woocommerce', self::REDIRECT, self::BROKER ) ), $this->exchanges );
		$this->assertSame( array( array( null, 'unknownState' ) ), self::codes( $second ) );
		$this->assertArrayNotHasKey( ConnectService::STATE_PREFIX . self::STATE, $store->options );
	}

	/**
	 * Bug: a request without code spends the merchant's state, so the real callback that follows is refused.
	 */
	public function test_a_callback_without_code_keeps_the_state_and_changes_nothing() {
		$store  = self::manual_shop();
		$before = $store->options;

		$result = $this->service( $store )->complete( self::STATE, '' );

		$this->assertSame( array(), $this->exchanges );
		$this->assertSame( array( array( null, 'missingCode' ) ), self::codes( $result ) );
		$this->assertSame( $before, $store->options );
	}

	/**
	 * Bug: abandoned starts leave state rows behind for ever, or a start deletes the entry of a connect still in
	 * progress in another tab. Entries are not autoloaded: they would load on every request.
	 */
	public function test_a_start_deletes_entries_older_than_20_minutes_and_keeps_a_younger_one() {
		$old   = ConnectService::STATE_PREFIX . str_repeat( 'o', 43 );
		$young = ConnectService::STATE_PREFIX . str_repeat( 'y', 43 );
		$store = new MemoryOptionStore(
			array(
				$old   => array( 'createdAt' => self::NOW - 1200 ),
				$young => array( 'createdAt' => self::NOW - 1199 ),
			)
		);

		$url = $this->service( $store )->begin( self::REDIRECT );

		$this->assertArrayNotHasKey( $old, $store->options );
		$this->assertArrayHasKey( $young, $store->options );
		parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $query );
		$this->assertStringStartsWith( 'https://oauth.example/integrations/authorize?', $url );
		$name  = ConnectService::STATE_PREFIX . $query['state'];
		$entry = $store->options[ $name ];
		$this->assertSame( self::REDIRECT, $entry['redirectUri'] );
		$this->assertSame( self::NOW, $entry['createdAt'] );
		$this->assertSame( 64, strlen( $entry['verifier'] ) );
		$this->assertFalse( $store->autoload[ $name ] );
	}

	/**
	 * Bug: a failed exchange wipes or half-writes working manual credentials.
	 */
	public function test_a_failed_exchange_changes_nothing() {
		$store                 = self::manual_shop();
		$before                = self::credentials( $store );
		$this->exchange_answer = new ConnectException( 'access_denied', 403 );

		$result = $this->service( $store )->complete( self::STATE, 'the-code' );

		$this->assertSame( array( array( null, 'access_denied' ) ), self::codes( $result ) );
		$this->assertSame( $before, self::credentials( $store ) );
	}

	/**
	 * Bug: a 200 that refuses both modes (cap, not_approved) changes credentials or the mode, or hides a refusal
	 * code. The shop has no Live key, so a mode switch without a switched key shows here.
	 * The texts of the codes are pinned in ConnectWiringTest: ConnectHandler holds the translations.
	 */
	public function test_a_200_with_only_errors_changes_nothing_and_reports_each_code() {
		$store = self::manual_shop();
		$store->delete( 'monei_live_accountid' );
		$store->delete( 'monei_live_apikey' );
		$before                = self::credentials( $store );
		$this->exchange_answer = array(
			'errors' => array(
				'live' => 'not_approved',
				'test' => 'cap',
			),
		);

		$result = $this->service( $store )->complete( self::STATE, 'the-code' );

		$this->assertSame( array( array( 'live', 'not_approved' ), array( 'test', 'cap' ) ), self::codes( $result ) );
		$this->assertSame( array(), $result['switched'] );
		$this->assertSame( $before, self::credentials( $store ) );
	}

	/**
	 * Bug: the test call goes out with the stored key (or a cached client), so a broken new key replaces a
	 * working one.
	 */
	public function test_the_test_call_uses_each_new_key() {
		$this->exchange_answer = array(
			'live' => self::keys( 'live', 'acc-live' ),
			'test' => self::keys( 'test', 'acc-test-new' ),
		);

		$this->service( self::manual_shop() )->complete( self::STATE, 'the-code' );

		$this->assertSame( array( 'mk_live_new', 'mk_test_new' ), $this->probed );
	}

	/**
	 * Bug: a key the edge refuses replaces working credentials, a MONEI outage (5xx, 429, no answer) is taken for
	 * proof, or a 400 (a mode with no enabled provider) is taken for a refused key and blocks the connect.
	 *
	 * @dataProvider call_answers
	 */
	public function test_the_test_call_answer_decides_the_switch( int $status, array $expected_test, array $expected_codes ) {
		$store                 = self::manual_shop();
		$this->exchange_answer = array( 'test' => self::keys( 'test', 'acc-test-new' ) );
		$this->statuses        = array( 'mk_test_new' => $status );

		$result = $this->service( $store )->complete( self::STATE, 'the-code' );

		$credentials = self::credentials( $store );
		$this->assertSame( $expected_test, array( $credentials['test_accountid'], $credentials['test_apikey'], $credentials['test_signing_secret'] ) );
		$this->assertSame( $expected_codes, self::codes( $result ) );
	}

	public function call_answers(): array {
		$new         = array( 'acc-test-new', 'mk_test_new', 'mksec_test_new' );
		$kept        = array( 'acc-test-old', 'pk_test_old', null );
		$unavailable = array( array( 'test', 'temporarily_unavailable' ) );
		return array(
			'200 proves the key' => array( 200, $new, array() ),
			'400 proves the key' => array( 400, $new, array() ),
			'401'                => array( 401, $kept, array( array( 'test', 'keyRejected' ) ) ),
			'403'                => array( 403, $kept, array( array( 'test', 'keyRejected' ) ) ),
			'429'                => array( 429, $kept, $unavailable ),
			'500'                => array( 500, $kept, $unavailable ),
			'no answer'          => array( 0, $kept, $unavailable ),
		);
	}

	/**
	 * Bug: one refused mode drops the other, or flips the shop's mode.
	 */
	public function test_test_proven_and_live_refused_switches_test_only() {
		$store                 = self::manual_shop();
		$this->exchange_answer = array(
			'live' => self::keys( 'live', 'acc-live' ),
			'test' => self::keys( 'test', 'acc-test-new' ),
		);
		$this->statuses        = array( 'mk_live_new' => 401 );

		$result = $this->service( $store )->complete( self::STATE, 'the-code' );

		$this->assertSame(
			array(
				'apikey_mode'         => 'live',
				'live_accountid'      => 'acc-live',
				'live_apikey'         => 'pk_live_old',
				'live_signing_secret' => null,
				'test_accountid'      => 'acc-test-new',
				'test_apikey'         => 'mk_test_new',
				'test_signing_secret' => 'mksec_test_new',
			),
			self::credentials( $store )
		);
		$this->assertSame( array( array( 'live', 'keyRejected' ) ), self::codes( $result ) );
	}

	/**
	 * Bug: a live shop is switched to test mode, or loses its manual Live keys, when only Test comes back.
	 */
	public function test_only_test_on_a_shop_with_manual_live_keeps_live_and_the_mode() {
		$store                 = self::manual_shop();
		$this->exchange_answer = array( 'test' => self::keys( 'test', 'acc-test-new' ) );

		$this->service( $store )->complete( self::STATE, 'the-code' );

		$credentials = self::credentials( $store );
		$this->assertSame( array( 'live', 'acc-live', 'pk_live_old' ), array( $credentials['apikey_mode'], $credentials['live_accountid'], $credentials['live_apikey'] ) );
		$this->assertSame( 'mk_test_new', $credentials['test_apikey'] );
	}

	/**
	 * Bug: a shop with no Live credentials stays in live mode after it gets only a Test key (not_approved), so
	 * every payment fails.
	 */
	public function test_only_test_on_a_shop_without_live_turns_test_mode_on() {
		$store = new MemoryOptionStore(
			array(
				'monei_apikey_mode'                        => 'live',
				ConnectService::STATE_PREFIX . self::STATE => array(
					'verifier'    => 'the-verifier',
					'redirectUri' => self::REDIRECT,
					'createdAt'   => self::NOW - 60,
				),
			)
		);
		$this->exchange_answer = array(
			'test'   => self::keys( 'test', 'acc-test-new' ),
			'errors' => array( 'live' => 'not_approved' ),
		);

		$this->service( $store )->complete( self::STATE, 'the-code' );

		$this->assertSame( 'test', $store->get( 'monei_apikey_mode' ) );
		$this->assertSame( 'mk_test_new', $store->get( 'monei_test_apikey' ) );
	}

	/**
	 * Bug: a shop is split across two MONEI accounts: open orders of the old Live account lose their key. The
	 * Test result is refused too, even proven. A manual shop has no Disconnect button, so it must not be told to
	 * disconnect.
	 *
	 * @dataProvider stored_live_keys
	 */
	public function test_another_live_account_switches_neither_mode( string $live_key, string $code ) {
		$store                 = self::manual_shop( array( 'monei_live_apikey' => $live_key ) );
		$before                = self::credentials( $store );
		$this->exchange_answer = array(
			'live' => self::keys( 'live', 'acc-other' ),
			'test' => self::keys( 'test', 'acc-test-new' ),
		);

		$result = $this->service( $store )->complete( self::STATE, 'the-code' );

		$this->assertSame( $before, self::credentials( $store ) );
		$this->assertSame( array( array( null, $code ) ), self::codes( $result ) );
		$this->assertSame( array(), $this->probed );
	}

	public function stored_live_keys(): array {
		return array(
			'manual pk_ key' => array( 'pk_live_old', 'otherAccountManual' ),
			'connected'      => array( 'mk_live_old', 'otherAccount' ),
		);
	}

	/**
	 * Bug: a new Test account id is refused. The 2026-10-22 repair gave many live accounts a new test account,
	 * and the token answer does not say which live account a test account belongs to.
	 */
	public function test_only_test_with_a_new_test_account_switches_test() {
		$store                 = self::manual_shop();
		$this->exchange_answer = array( 'test' => self::keys( 'test', 'acc-test-new' ) );

		$result = $this->service( $store )->complete( self::STATE, 'the-code' );

		$this->assertSame( array( 'test' ), array_keys( $result['switched'] ) );
		$this->assertSame( 'acc-test-new', $store->get( 'monei_test_accountid' ) );
	}

	/**
	 * Bug: after a manual key change, v2 callbacks still verify with the secret of the replaced key; or a save
	 * that leaves the key alone drops the secret of a connected mode.
	 */
	public function test_a_save_clears_the_secret_of_a_changed_key_only() {
		$store   = new MemoryOptionStore(
			array(
				'monei_live_apikey'         => 'mk_live_a',
				'monei_live_signing_secret' => 'mksec_live_a',
				'monei_test_apikey'         => 'mk_test_a',
				'monei_test_signing_secret' => 'mksec_test_a',
			)
		);
		$service = $this->service( $store );
		$before  = $service->api_keys();
		$store->set( 'monei_test_apikey', 'pk_test_manual' );

		$service->forget_secrets_of_changed_keys( $before );

		$this->assertSame( 'mksec_live_a', $store->get( 'monei_live_signing_secret', null ) );
		$this->assertNull( $store->get( 'monei_test_signing_secret', null ) );
	}

	/**
	 * Bug: a save from a tab rendered before a connect in another tab writes the old keys back over the new ones.
	 */
	public function test_a_key_changed_after_the_render_is_reported_for_its_mode_only() {
		$store        = self::manual_shop();
		$service      = $this->service( $store );
		$fingerprints = $service->key_fingerprints();
		$store->set( 'monei_test_apikey', 'mk_test_new' );

		$this->assertSame( array( 'test' ), $service->modes_changed_since( $fingerprints ) );
		$this->assertSame( array(), $service->modes_changed_since( $service->key_fingerprints() ) );
		$this->assertSame( array(), $service->modes_changed_since( array() ), 'a form without fingerprints saves as before' );
	}

	/**
	 * Bug: Disconnect deletes a manual pk_ key of the other mode, and its checkout stops; or, after a disconnect,
	 * ApiKeyService's legacy migration copies an old pk_ key back into the empty modes on the next request.
	 */
	public function test_disconnect_clears_only_the_connected_mode_and_the_legacy_keys() {
		$store = self::manual_shop(
			array(
				'monei_live_apikey'          => 'mk_live_connected',
				'monei_live_signing_secret'  => 'mksec_live',
				'monei_apikey'               => 'pk_live_legacy',
				'monei_accountid'            => 'acc-legacy',
				'woocommerce_monei_settings' => array(
					'apikey'    => 'pk_live_legacy',
					'accountid' => 'acc-legacy',
					'orderdo'   => 'completed',
				),
			)
		);

		$this->service( $store )->disconnect();

		$this->assertSame( array( 'orderdo' => 'completed' ), $store->get( 'woocommerce_monei_settings' ) );
		foreach ( array( 'monei_apikey', 'monei_accountid', 'monei_live_accountid', 'monei_live_apikey', 'monei_live_signing_secret' ) as $name ) {
			$this->assertArrayNotHasKey( $name, $store->options, $name );
		}
		$this->assertSame( array( 'acc-test-old', 'pk_test_old' ), array( $store->get( 'monei_test_accountid' ), $store->get( 'monei_test_apikey' ) ) );
	}
}
