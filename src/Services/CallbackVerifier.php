<?php

namespace Monei\Services;

use Monei\ApiException;
use Monei\Configuration;
use Monei\MoneiClient;
use Monei\ObjectSerializer;
use Throwable;
use InvalidArgumentException;

/**
 * Decides which payment a MONEI callback may process.
 *
 * - A v2 signature that verifies with the stored signing secret: the body's payment.
 * - Else, on a mode with a scoped key (mk_): the body gives only the payment id. The payment is fetched with the
 *   shop's key and checked against the order. So callbacks of orders made before the connect, after a rotation,
 *   or without v2 still settle, and a forged body changes nothing.
 * - Else (a manual pk_ key): v1 with that key.
 *
 * Pure: the options, the clock, the order lookup and the fetch are injected. It calls no WordPress function.
 */
class CallbackVerifier {

	public const QUOTA_OPTION = 'monei_callback_fetch_attempts';

	private const ORDER_QUOTA = 5;
	private const SHOP_QUOTA  = 60;
	private const WINDOW      = 60;
	/** The statuses MONEI sends callbacks for (payments-service handleCallback.ts). */
	private const PROCESSED = array( 'AUTHORIZED', 'SUCCEEDED', 'CANCELED', 'FAILED', 'PAID_OUT' );

	private OptionStore $options;
	/** @var callable(): int */
	private $clock;
	/** @var callable(string): (array{paymentId: string, paymentStatus: string, paid: bool, monei: bool}|null) The order of an orderId, its stored payment, and whether a MONEI method pays it. */
	private $find_order;
	/** @var callable(string, string): \Monei\Model\Payment Fetch a payment with the key of a mode. */
	private $fetch;

	public function __construct( OptionStore $options, callable $clock, callable $find_order, callable $fetch ) {
		$this->options    = $options;
		$this->clock      = $clock;
		$this->find_order = $find_order;
		$this->fetch      = $fetch;
	}

	public function any_mode_connected(): bool {
		return self::is_scoped_key( (string) $this->options->get( 'monei_live_apikey', '' ) )
			|| self::is_scoped_key( (string) $this->options->get( 'monei_test_apikey', '' ) );
	}

	/**
	 * @param string      $body      Raw request body.
	 * @param string|null $signature MONEI-Signature header, null when absent.
	 * @return array{status: int, payment: array|null, reason: string} The payment to process, in the body's shape,
	 *     or null with the HTTP status to answer: 400 a failed pre-check, 401 a bad signature, 503 a failed fetch or
	 *     a used-up quota (MONEI retries both), 200 a callback that must not change an order.
	 */
	public function verify( string $body, ?string $signature ): array {
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			return self::refused( 400, 'body' );
		}
		$mode   = $this->mode_of( $data );
		$key    = (string) $this->options->get( "monei_{$mode}_apikey", '' );
		$secret = (string) $this->options->get( "monei_{$mode}_signing_secret", '' );

		if ( '' !== $secret && null !== $signature && self::signature_valid( $body, $signature, '', $secret ) ) {
			return self::accepted( (array) json_decode( $body ) );
		}
		if ( self::is_scoped_key( $key ) ) {
			return $this->fetched( $data, $signature, $mode );
		}
		// An empty key gives an HMAC that anyone can make.
		if ( '' === $key || null === $signature || ! self::signature_valid( $body, $signature, $key, null ) ) {
			return self::refused( 401, 'signature' );
		}
		return self::accepted( (array) json_decode( $body ) );
	}

	private function fetched( array $data, ?string $signature, string $mode ): array {
		$id       = $data['id'] ?? null;
		$order_id = $data['orderId'] ?? null;
		if ( ( null !== $signature && ! self::header_parses( $signature ) )
			|| ! is_string( $id ) || ! preg_match( '/^[0-9a-f]{32,40}\z/i', $id )
			|| ! ( is_string( $order_id ) || is_int( $order_id ) ) ) {
			return self::refused( 400, 'format' );
		}
		$order_id = (string) $order_id;
		$order    = ( $this->find_order )( $order_id );
		// A card tokenization callback has no order, and an order of another gateway is not ours. A retry changes neither.
		if ( null === $order || ! $order['monei'] ) {
			return self::refused( 200, 'order' );
		}
		// Only an order that holds a live payment refuses another one; an abandoned attempt gives way to a retry.
		if ( '' !== $order['paymentId'] && $order['paymentId'] !== $id
			&& in_array( $order['paymentStatus'], array( 'AUTHORIZED', 'SUCCEEDED', 'PAID_OUT' ), true ) ) {
			return self::refused( 400, 'paymentId' );
		}
		if ( ! $this->take_quota( $order_id ) ) {
			return self::refused( 503, 'quota' );
		}
		try {
			$payment = ( $this->fetch )( $mode, $id );
		} catch ( InvalidArgumentException $e ) {
			// The SDK refuses a value it does not know, such as a new status. A retry gets the same answer.
			return self::refused( 200, 'unknownValue' );
		} catch ( Throwable $e ) {
			return self::refused( 503, 'fetch' );
		}
		// The model keeps its data in a protected snake_case container; this gives the camelCase body shape.
		$fetched = (array) ObjectSerializer::sanitizeForSerialization( $payment );
		if ( ( $fetched['id'] ?? null ) !== $id ) {
			return self::refused( 200, 'id' );
		}
		if ( (string) ( $fetched['orderId'] ?? '' ) !== $order_id ) {
			return self::refused( 200, 'orderId' );
		}
		// An amount mismatch goes on to handle_valid_ipn, which puts the order on hold as for a signed body.
		$status = $fetched['status'] ?? null;
		if ( ! in_array( $status, self::PROCESSED, true ) ) {
			return self::refused( 200, 'status' );
		}
		if ( $order['paid'] && in_array( $status, array( 'FAILED', 'CANCELED' ), true ) ) {
			return self::refused( 200, 'paid' );
		}
		return self::accepted( $fetched );
	}

	/**
	 * The mode the callback names. Each mode verifies with its own key or secret, so the body cannot pick a weaker check.
	 */
	private function mode_of( array $data ): string {
		if ( isset( $data['livemode'] ) && is_bool( $data['livemode'] ) ) {
			return $data['livemode'] ? 'live' : 'test';
		}
		return 'live' === $this->options->get( 'monei_apikey_mode', 'test' ) ? 'live' : 'test';
	}

	/**
	 * Count a fetch attempt: at most 5 a minute per order and 60 a minute per shop.
	 */
	private function take_quota( string $order_id ): bool {
		$now      = ( $this->clock )();
		$recent   = function ( $times ) use ( $now ): array {
			return array_values(
				array_filter(
					(array) $times,
					function ( $time ) use ( $now ) {
						return is_int( $time ) && $now - $time < self::WINDOW;
					}
				)
			);
		};
		$attempts = (array) $this->options->get( self::QUOTA_OPTION, array() );
		$shop     = $recent( $attempts['shop'] ?? array() );
		$orders   = array();
		foreach ( (array) ( $attempts['orders'] ?? array() ) as $id => $times ) {
			$times = $recent( $times );
			if ( $times ) {
				$orders[ $id ] = $times;
			}
		}
		$order = $orders[ $order_id ] ?? array();
		if ( count( $order ) >= self::ORDER_QUOTA || count( $shop ) >= self::SHOP_QUOTA ) {
			return false;
		}
		$shop[]              = $now;
		$order[]             = $now;
		$orders[ $order_id ] = $order;
		$this->options->set(
			self::QUOTA_OPTION,
			array(
				'shop'   => $shop,
				'orders' => $orders,
			),
			false
		);
		return true;
	}

	private static function signature_valid( string $body, string $signature, string $key, ?string $secret ): bool {
		try {
			( new MoneiClient( $key, new Configuration() ) )->verifySignature( $body, $signature, $secret );
			return true;
		} catch ( ApiException $e ) {
			return false;
		}
	}

	/**
	 * The SDK's reading of the header: comma-separated key=value elements, with a t.
	 */
	private static function header_parses( string $signature ): bool {
		$has_timestamp = false;
		foreach ( explode( ',', $signature ) as $element ) {
			$pair = explode( '=', $element, 2 );
			if ( 2 !== count( $pair ) ) {
				return false;
			}
			$has_timestamp = $has_timestamp || ( 't' === $pair[0] && '' !== $pair[1] );
		}
		return $has_timestamp;
	}

	private static function is_scoped_key( string $key ): bool {
		return 0 === strpos( $key, 'mk_' );
	}

	private static function accepted( array $payment ): array {
		return array(
			'status'  => 200,
			'payment' => $payment,
			'reason'  => 'ok',
		);
	}

	private static function refused( int $status, string $reason ): array {
		return array(
			'status'  => $status,
			'payment' => null,
			'reason'  => $reason,
		);
	}
}
