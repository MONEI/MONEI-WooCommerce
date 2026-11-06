<?php

namespace Monei\Services;

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
	 * @return string|null Null when no MONEI account ID is configured for the current mode.
	 */
	private function meta_key(): ?string {
		$account_id = $this->apiKeyService->get_account_id();
		if ( '' === $account_id ) {
			return null;
		}
		return self::META_PREFIX . $account_id . '_' . $this->current_mode();
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
