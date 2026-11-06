<?php

namespace Monei\Services;

use Monei\ApiException;
use Monei\Services\sdk\MoneiSdkClientFactory;
use WC_Monei_Logger;
use Throwable;
use UnexpectedValueException;

/**
 * Links WP users to MONEI customers.
 *
 * Behavior is set by the cross-platform contract (docs/plans/20261105-monei-customers-api-contract.md).
 * No customer error may stop a payment or a delete, so every path catches \Throwable.
 */
class CustomerService {

	const MONEI_TOKEN_GATEWAYS = array( 'monei', 'monei_apple_google' );

	const LOG_PREFIX = '[MONEI Customers] ';

	const META_PREFIX = '_monei_customer_';

	const DELETING_OPTION_PREFIX = 'monei_customer_deleting_';

	private MoneiSdkClientFactory $sdkClientFactory;

	private ApiKeyService $apiKeyService;

	public function __construct( MoneiSdkClientFactory $sdkClientFactory, ApiKeyService $apiKeyService ) {
		$this->sdkClientFactory = $sdkClientFactory;
		$this->apiKeyService    = $apiKeyService;
	}

	/**
	 * MONEI customer ID of the user for the current account and mode. Creates the customer when there is no mapping.
	 *
	 * @return string|null Null when the user is not linked; the payment then goes ahead without customerId.
	 */
	public function get_or_create_customer_id( int $user_id, string $email, string $name, ?string $phone ): ?string {
		$new_id = null;
		try {
			$key = $this->meta_key();
			if ( null === $key ) {
				WC_Monei_Logger::logDebug( self::LOG_PREFIX . 'No MONEI account ID is configured. The payment is not linked to a MONEI customer.' );
				return null;
			}

			$stored = get_user_meta( $user_id, $key, true );
			if ( is_string( $stored ) && '' !== $stored ) {
				return $stored;
			}

			$new_id = $this->create_customer( $user_id, $email, $name, $phone );
			return $this->save_mapping( $user_id, $key, $new_id );
		} catch ( Throwable $e ) {
			WC_Monei_Logger::logError( self::LOG_PREFIX . sprintf( 'Could not link user %d to a MONEI customer: %s', $user_id, $e->getMessage() ) );
			if ( null !== $new_id ) {
				$this->discard_customer( $new_id );
			}
			return null;
		}
	}

	/**
	 * Adds the MONEI customer of the user to a create-payment payload. Does nothing for a guest or an unknown user.
	 *
	 * @param string|null $fallback_phone Used when the user has no billing phone, for example the order billing phone.
	 */
	public function add_customer_id( array $payload, int $user_id, ?string $fallback_phone = null ): array {
		try {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return $payload;
			}

			$name = trim( get_user_meta( $user_id, 'billing_first_name', true ) . ' ' . get_user_meta( $user_id, 'billing_last_name', true ) );
			if ( '' === $name ) {
				$name = (string) $user->display_name;
			}
			$phone = (string) get_user_meta( $user_id, 'billing_phone', true );
			if ( '' === $phone ) {
				$phone = $fallback_phone;
			}

			$customer_id = $this->get_or_create_customer_id( $user_id, (string) $user->user_email, $name, $phone );
			if ( null !== $customer_id ) {
				$payload['customerId'] = $customer_id;
			}
		} catch ( Throwable $e ) {
			WC_Monei_Logger::logError( self::LOG_PREFIX . sprintf( 'Could not add the MONEI customer of user %d to the payment: %s', $user_id, $e->getMessage() ) );
		}
		return $payload;
	}

	/**
	 * Calls $create( $payload ). When MONEI no longer knows the payload's customer, forgets the mapping
	 * and calls $create once more without customerId. Any other error is rethrown unchanged.
	 *
	 * @param callable $create  Creates the payment from the payload.
	 * @param array    $payload By reference, so the caller logs and reports the payload that was sent.
	 *
	 * @return mixed What $create returns.
	 * @throws ApiException
	 */
	public function create_payment( callable $create, array &$payload, int $user_id ) {
		try {
			return $create( $payload );
		} catch ( ApiException $e ) {
			if ( ! isset( $payload['customerId'] ) || ! $this->is_stale_customer_error( $e ) ) {
				throw $e;
			}
		}

		$stale_id = (string) $payload['customerId'];
		WC_Monei_Logger::logWarning( self::LOG_PREFIX . sprintf( 'MONEI customer %s of user %d no longer exists. The payment is retried without it.', $stale_id, $user_id ) );
		$this->forget_customer_id( $user_id, $stale_id );
		unset( $payload['customerId'] );
		return $create( $payload );
	}

	/**
	 * Removes the mapping of the current account and mode only while it still holds $stale_id.
	 */
	public function forget_customer_id( int $user_id, string $stale_id ): void {
		try {
			$key = $this->meta_key();
			if ( null !== $key ) {
				delete_user_meta( $user_id, $key, $stale_id );
			}
		} catch ( Throwable $e ) {
			WC_Monei_Logger::logError( self::LOG_PREFIX . sprintf( 'Could not remove the mapping of user %d to MONEI customer %s: %s', $user_id, $stale_id, $e->getMessage() ) );
		}
	}

	/**
	 * Deletes the MONEI customer of every mapping of the user, in every account and mode.
	 *
	 * The caller catches: a failure to read the mappings escapes. A failing row is logged and kept, and the next row still runs.
	 */
	public function delete_customer( int $user_id ): void {
		foreach ( $this->mapping_rows( $user_id ) as $row ) {
			try {
				$this->delete_row( $user_id, $row );
			} catch ( Throwable $e ) {
				WC_Monei_Logger::logError( self::LOG_PREFIX . $this->row_message( $row, 'could not be deleted: ' . $e->getMessage() ) );
			}
		}
	}

	/**
	 * Every mapping value of the user. A key can hold several values: the unique check in add_metadata is not atomic.
	 *
	 * @return array<int, array{key: string, account_id: string, mode: string, customer_id: string}>
	 */
	private function mapping_rows( int $user_id ): array {
		$all_meta = get_user_meta( $user_id );
		if ( ! is_array( $all_meta ) ) {
			return array();
		}

		$rows = array();
		foreach ( $all_meta as $key => $values ) {
			if ( ! preg_match( '/^' . self::META_PREFIX . '(.+)_(test|live)$/', (string) $key, $match ) ) {
				continue;
			}
			foreach ( (array) $values as $customer_id ) {
				$rows[] = array(
					'key'         => (string) $key,
					'account_id'  => $match[1],
					'mode'        => $match[2],
					'customer_id' => (string) $customer_id,
				);
			}
		}
		return $rows;
	}

	/**
	 * @param array{key: string, account_id: string, mode: string, customer_id: string} $row
	 */
	private function delete_row( int $user_id, array $row ): void {
		// Test and live often share one account ID, so the mode comes from the row, never from the account ID.
		if ( $this->apiKeyService->get_account_id_for_mode( $row['mode'] ) !== $row['account_id'] || '' === $this->apiKeyService->get_api_key_for_mode( $row['mode'] ) ) {
			WC_Monei_Logger::logError( self::LOG_PREFIX . $this->row_message( $row, 'was not deleted: no API key is configured for its account and mode.' ) );
			return;
		}

		try {
			$this->sdkClientFactory->get_client_for_mode( $row['mode'] )->customers->delete( $row['customer_id'] );
		} catch ( ApiException $e ) {
			if ( 404 !== $e->getCode() ) {
				throw $e;
			}
			WC_Monei_Logger::logDebug( self::LOG_PREFIX . $this->row_message( $row, 'was already deleted at MONEI.' ) );
		}
		delete_user_meta( $user_id, $row['key'], $row['customer_id'] );
	}

	/**
	 * @param array{key: string, account_id: string, mode: string, customer_id: string} $row
	 */
	private function row_message( array $row, string $text ): string {
		return sprintf( 'MONEI customer %s (account %s, mode %s) %s', $row['customer_id'], $row['account_id'], $row['mode'], $text );
	}

	/**
	 * @return string|null Null when no MONEI account ID is configured for the current mode.
	 */
	private function meta_key(): ?string {
		$account_id = $this->apiKeyService->get_account_id();
		if ( '' === $account_id ) {
			return null;
		}
		return self::META_PREFIX . $account_id . '_' . $this->current_mode();
	}

	/**
	 * Sandbox check (c): MONEI rejects a deleted customer at create with 404 and this message. Other 404s also exist.
	 */
	private function is_stale_customer_error( ApiException $e ): bool {
		if ( 404 !== $e->getCode() ) {
			return false;
		}
		$body = $e->getResponseBody();
		if ( is_string( $body ) ) {
			$body = json_decode( $body );
		}
		return is_object( $body ) && isset( $body->message ) && 'Customer not found' === $body->message;
	}

	private function current_mode(): string {
		return $this->apiKeyService->is_test_mode() ? 'test' : 'live';
	}

	private function create_customer( int $user_id, string $email, string $name, ?string $phone ): string {
		// The SDK setters throw on null, so empty values are left out.
		$request = array_filter(
			array(
				'email' => $email,
				'name'  => $name,
				'phone' => $phone,
			),
			function ( $value ) {
				return null !== $value && '' !== $value;
			}
		);

		$request['metadata'] = array(
			'platform'           => 'woocommerce',
			'platformCustomerId' => (string) $user_id,
		);

		$customer_id = $this->sdkClientFactory->get_client()->customers->create( $request )->getId();
		if ( ! is_string( $customer_id ) || '' === $customer_id ) {
			throw new UnexpectedValueException( 'MONEI returned no customer ID.' );
		}
		return $customer_id;
	}

	/**
	 * @return string|null The ID to use: ours, or the one another request stored first. Null when nothing could be stored.
	 */
	private function save_mapping( int $user_id, string $key, string $new_id ): ?string {
		add_user_meta( $user_id, $key, $new_id, true );
		// A failed unique add does not clear the meta cache, which still holds the empty value read before the create.
		wp_cache_delete( $user_id, 'user_meta' );
		$stored = get_user_meta( $user_id, $key, true );

		if ( ! is_string( $stored ) || '' === $stored ) {
			$this->discard_customer( $new_id );
			return null;
		}

		if ( $stored !== $new_id ) {
			// The unique check in add_metadata is not atomic, so our row can exist next to the winner's.
			delete_user_meta( $user_id, $key, $new_id );
			$this->discard_customer( $new_id );
			return $stored;
		}

		// A user delete that ran while the create waited for MONEI read no mapping, so nothing else deletes this customer.
		if ( $this->user_is_being_deleted( $user_id ) ) {
			delete_user_meta( $user_id, $key, $new_id );
			$this->discard_customer( $new_id );
			return null;
		}

		return $new_id;
	}

	private function user_is_being_deleted( int $user_id ): bool {
		global $wpdb;

		$option = self::DELETING_OPTION_PREFIX . $user_id;
		wp_cache_delete( $option, 'options' );
		if ( false !== get_option( $option ) ) {
			return true;
		}

		return null === $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID = %d", $user_id ) );
	}

	/**
	 * Best-effort delete of a MONEI customer that has no mapping.
	 */
	private function discard_customer( string $customer_id ): void {
		try {
			$this->sdkClientFactory->get_client()->customers->delete( $customer_id );
		} catch ( Throwable $e ) {
			WC_Monei_Logger::logError(
				self::LOG_PREFIX . sprintf(
					'Could not delete unmapped MONEI customer %s (account %s, mode %s): %s',
					$customer_id,
					$this->apiKeyService->get_account_id(),
					$this->current_mode(),
					$e->getMessage()
				)
			);
		}
	}
}
