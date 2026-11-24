<?php

namespace Monei\Services;

use Monei\Connect;
use Monei\ConnectException;

/**
 * "Connect with MONEI": the start, the single-use state of the callback, the test call of each new key, and the swap.
 *
 * Pure: the options, the clock, the token exchange and the test call are injected. It calls no WordPress function.
 */
class ConnectService {

	public const CLIENT       = 'woocommerce';
	public const STATE_PREFIX = 'monei_connect_state_';
	/** Seconds: the broker keeps its pending row 15 min, plus 60 s for its code. */
	public const STATE_TTL = 1200;

	private const MODES = array( 'live', 'test' );

	private OptionStore $options;
	/** @var callable(): int */
	private $clock;
	/** @var callable(string, string, string, string, string): array The signature of Connect::exchange. */
	private $exchange;
	/** @var callable(string): int HTTP status of the test call with a key, 0 when no answer came. */
	private $probe;
	private string $base_url;

	public function __construct( OptionStore $options, callable $clock, callable $exchange, callable $probe, string $base_url = Connect::BASE_URL ) {
		$this->options  = $options;
		$this->clock    = $clock;
		$this->exchange = $exchange;
		$this->probe    = $probe;
		$this->base_url = $base_url;
	}

	/**
	 * Store a new state and return the broker URL. Entries of abandoned starts are deleted first.
	 */
	public function begin( string $redirect_uri ): string {
		$now = ( $this->clock )();
		foreach ( $this->options->names_with_prefix( self::STATE_PREFIX ) as $name ) {
			if ( ! $this->is_fresh( $this->options->get( $name, null ), $now ) ) {
				$this->options->delete( $name );
			}
		}
		$start = Connect::start( self::CLIENT, $redirect_uri, $this->base_url );
		$this->options->set(
			self::STATE_PREFIX . $start['state'],
			array(
				'verifier'    => $start['verifier'],
				'redirectUri' => $redirect_uri,
				'createdAt'   => $now,
			),
			false
		);
		return $start['url'];
	}

	/**
	 * Handle the callback.
	 *
	 * @return array{switched: array<string, array{accountId: string, apiKey: string, signingSecret: string}>, problems: list<array{mode: string|null, code: string}>}
	 */
	public function complete( string $state, string $code ): array {
		if ( '' === $code ) {
			return self::failed( 'missingCode' );
		}
		$name  = self::STATE_PREFIX . $state;
		$entry = preg_match( '/^[A-Za-z0-9_-]{43}\z/', $state ) ? $this->options->get( $name, null ) : null;
		// Of two callbacks with one state, only the one whose delete removed the entry goes on.
		if ( ! is_array( $entry ) || ! $this->options->delete( $name ) ) {
			return self::failed( 'unknownState' );
		}
		if ( ! $this->is_fresh( $entry, ( $this->clock )() ) ) {
			return self::failed( 'expiredState' );
		}
		try {
			$result = ( $this->exchange )( $code, (string) $entry['verifier'], self::CLIENT, (string) $entry['redirectUri'], $this->base_url );
		} catch ( ConnectException $e ) {
			return self::failed( $e->getError() );
		}
		return $this->swap( $result );
	}

	/**
	 * Clear the stored credentials of both modes. The keys stay active at MONEI until the merchant revokes them.
	 */
	public function disconnect(): void {
		// ApiKeyService copies these legacy keys into empty modes on the next request.
		// They go first: reading the settings can run that migration while the modes still hold keys.
		$settings = $this->options->get( 'woocommerce_monei_settings', array() );
		if ( is_array( $settings ) && ( isset( $settings['apikey'] ) || isset( $settings['accountid'] ) ) ) {
			unset( $settings['apikey'], $settings['accountid'] );
			$this->options->set( 'woocommerce_monei_settings', $settings );
		}
		$this->options->delete( 'monei_apikey' );
		$this->options->delete( 'monei_accountid' );
		foreach ( self::MODES as $mode ) {
			foreach ( array( 'accountid', 'apikey', 'signing_secret' ) as $field ) {
				$this->options->delete( "monei_{$mode}_{$field}" );
			}
		}
	}

	/**
	 * Whether a mode stores a scoped key (mk_) from a connect. Disconnect shows only then.
	 */
	public function is_connected(): bool {
		foreach ( $this->api_keys() as $key ) {
			if ( 0 === strpos( $key, 'mk_' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array{live: string, test: string} The stored API key of each mode.
	 */
	public function api_keys(): array {
		return array(
			'live' => (string) $this->options->get( 'monei_live_apikey', '' ),
			'test' => (string) $this->options->get( 'monei_test_apikey', '' ),
		);
	}

	/**
	 * After a manual save: a mode whose key changed loses its signing secret, which belongs to the old key.
	 *
	 * @param array{live: string, test: string} $keys_before api_keys() before the save.
	 */
	public function forget_secrets_of_changed_keys( array $keys_before ): void {
		foreach ( $this->api_keys() as $mode => $key ) {
			if ( $key !== $keys_before[ $mode ] ) {
				$this->options->delete( "monei_{$mode}_signing_secret" );
			}
		}
	}

	private function swap( array $result ): array {
		$problems = array();
		foreach ( $result['errors'] ?? array() as $mode => $error ) {
			$problems[] = array(
				'mode' => $mode,
				'code' => $error,
			);
		}
		$live_account = (string) $this->options->get( 'monei_live_accountid', '' );
		// Open orders of the old account need its key, and a shop split across two merchants is wrong.
		if ( isset( $result['live'] ) && '' !== $live_account && $result['live']['accountId'] !== $live_account ) {
			// Only a connected shop has a Disconnect button; a manual shop clears its Live fields instead.
			$problems[] = array(
				'mode' => null,
				'code' => $this->is_connected() ? 'otherAccount' : 'otherAccountManual',
			);
			return array(
				'switched' => array(),
				'problems' => $problems,
			);
		}
		$switched = array();
		foreach ( self::MODES as $mode ) {
			if ( ! isset( $result[ $mode ] ) ) {
				continue;
			}
			$keys = $result[ $mode ];
			// /allowed-payment-methods answers 400 for a mode without an enabled provider: any answer but 401 or 403 proves the key.
			$status = ( $this->probe )( $keys['apiKey'] );
			if ( in_array( $status, array( 0, 401, 403 ), true ) ) {
				$problems[] = array(
					'mode' => $mode,
					'code' => 'keyRejected',
				);
				continue;
			}
			$this->options->set( "monei_{$mode}_accountid", $keys['accountId'] );
			$this->options->set( "monei_{$mode}_apikey", $keys['apiKey'] );
			$this->options->set( "monei_{$mode}_signing_secret", $keys['signingSecret'] );
			$switched[ $mode ] = $keys;
		}
		// A live shop is never switched to test mode.
		if ( $switched && '' === (string) $this->options->get( 'monei_live_apikey', '' ) ) {
			$this->options->set( 'monei_apikey_mode', 'test' );
		}
		return array(
			'switched' => $switched,
			'problems' => $problems,
		);
	}

	/**
	 * @param mixed $entry A stored state entry.
	 */
	private function is_fresh( $entry, int $now ): bool {
		return is_array( $entry ) && isset( $entry['createdAt'] ) && is_int( $entry['createdAt'] ) && $now - $entry['createdAt'] < self::STATE_TTL;
	}

	private static function failed( string $code ): array {
		return array(
			'switched' => array(),
			'problems' => array(
				array(
					'mode' => null,
					'code' => $code,
				),
			),
		);
	}
}
