<?php

namespace Monei\Settings;

use Monei\ApiException;
use Monei\Connect;
use Monei\Core\ContainerProvider;
use Monei\Repositories\PaymentMethodsRepository;
use Monei\Services\ConnectService;
use Monei\Services\MoneiApplePayVerificationService;
use Monei\Services\sdk\MoneiSdkClientFactory;
use Monei\Services\WpOptionStore;
use WC_Monei_Logger;
use InvalidArgumentException;
use Throwable;

/**
 * The admin-post routes of "Connect with MONEI": start, callback and disconnect.
 *
 * Each route is an admin_post_ hook only. With no admin_post_nopriv_ hook, admin-post.php refuses a request
 * without a session before it reaches these handlers.
 */
class ConnectHandler {

	/** Messages of the last connect or disconnect, shown once on the settings tab. */
	public const MESSAGES_OPTION    = 'monei_connect_messages';
	public const DISCONNECT_FORM_ID = 'monei-disconnect-form';
	public const DASHBOARD_KEYS_URL = 'https://dashboard.monei.com/settings/api';

	/**
	 * The callback URL. The broker pins its query to exactly ?action=monei_connect, so it cannot carry a nonce.
	 */
	public static function callback_url(): string {
		return admin_url( 'admin-post.php' ) . '?action=monei_connect';
	}

	/**
	 * The redirect URI that the start sends, or null when the broker would refuse the callback URL.
	 *
	 * The settings tab and the start handler both use it, so the tab shows the button only for a URI the start sends.
	 */
	public static function redirect_uri(): ?string {
		try {
			$uri = Connect::canonicalRedirectUri( self::callback_url() );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}
		return null === Connect::callbackProblem( ConnectService::CLIENT, $uri ) ? $uri : null;
	}

	/**
	 * The broker. MONEI_CONNECT_BASE_URL (a constant, else an environment variable) points it at a dev broker.
	 */
	public static function broker_url(): string {
		$url = defined( 'MONEI_CONNECT_BASE_URL' ) ? constant( 'MONEI_CONNECT_BASE_URL' ) : getenv( 'MONEI_CONNECT_BASE_URL' );
		return is_string( $url ) && '' !== $url ? $url : Connect::BASE_URL;
	}

	public static function connect_service(): ConnectService {
		return new ConnectService(
			new WpOptionStore(),
			'time',
			array( Connect::class, 'exchange' ),
			function ( string $api_key ): int {
				try {
					self::client_factory()->client_for_key( $api_key )->paymentMethods->getAllowed();
					return 200;
				} catch ( ApiException $e ) {
					return (int) $e->getCode();
				} catch ( Throwable $e ) {
					return 0;
				}
			},
			self::broker_url()
		);
	}

	public static function settings_url(): string {
		return admin_url( 'admin.php?page=wc-settings&tab=monei_settings' );
	}

	/**
	 * admin-post.php?action=monei_connect_start: store a state and redirect to the broker.
	 */
	public function start(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to connect MONEI.', 'monei' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'monei_connect_start' );
		$redirect_uri = self::redirect_uri();
		if ( null === $redirect_uri ) {
			wp_die( esc_html__( 'Connect with MONEI is not available for this shop. Use manual keys.', 'monei' ), '', array( 'response' => 400 ) );
		}
		$url = self::connect_service()->begin( $redirect_uri );
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- the broker is another host.
		wp_redirect( $url );
		exit;
	}

	/**
	 * admin-post.php?action=monei_connect: the broker sends the merchant back here with code and state.
	 */
	public function callback(): void {
		// The code is in this URL: it must never leave in a Referer.
		header( 'Referrer-Policy: no-referrer' );
		nocache_headers();
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to connect MONEI.', 'monei' ), '', array( 'response' => 403 ) );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the single-use state is the CSRF check.
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		try {
			$result = self::connect_service()->complete( $state, $code );
		} catch ( Throwable $e ) {
			WC_Monei_Logger::logError( '[MONEI] Connect failed: ' . get_class( $e ) );
			$result = array(
				'switched' => array(),
				'problems' => array(
					array(
						'mode' => null,
						'code' => 'temporarily_unavailable',
					),
				),
			);
		}
		if ( $result['switched'] ) {
			$this->after_switch( $result['switched'] );
		}

		$messages = array();
		if ( $result['switched'] ) {
			$messages[] = __( 'MONEI connected.', 'monei' );
		}
		foreach ( $result['problems'] as $problem ) {
			$messages[] = self::problem_message( $problem['mode'], $problem['code'] );
		}
		$refused = ! $result['switched'] && in_array( $result['problems'][0]['code'] ?? '', array( 'missingCode', 'unknownState', 'expiredState' ), true );
		if ( ! $refused ) {
			update_option( self::MESSAGES_OPTION, $messages, false );
		}
		$html = '<p>' . implode( '</p><p>', array_map( 'esc_html', $messages ) ) . '</p>';
		if ( ! $refused ) {
			$html .= '<p>' . esc_html__( 'You can close this tab.', 'monei' ) . '</p>';
		}
		wp_die( wp_kses_post( $html ), esc_html__( 'Connect with MONEI', 'monei' ), array( 'response' => $refused ? 400 : 200 ) );
	}

	/**
	 * admin-post.php?action=monei_disconnect: clear the credentials of both modes.
	 */
	public function disconnect(): void {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			wp_die( esc_html__( 'Disconnect needs a POST request.', 'monei' ), '', array( 'response' => 405 ) );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to disconnect MONEI.', 'monei' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'monei_disconnect', '_monei_disconnect_nonce' );
		self::connect_service()->disconnect();
		update_option(
			self::MESSAGES_OPTION,
			array( __( 'MONEI disconnected. The keys stay active until you revoke them in the MONEI Dashboard.', 'monei' ) ),
			false
		);
		wp_safe_redirect( self::settings_url() );
		exit;
	}

	/**
	 * The text of a connect problem.
	 *
	 * @param string|null $mode 'live', 'test', or null for the whole connect.
	 * @param string      $code A ConnectService problem, an OAuth error code, or a broker code of the mode.
	 */
	public static function problem_message( ?string $mode, string $code ): string {
		$messages = array(
			'missingCode'             => __( 'MONEI did not send a code. Nothing was changed.', 'monei' ),
			'unknownState'            => __( 'This connect link is not valid or was already used. Start again from the MONEI settings.', 'monei' ),
			'expiredState'            => __( 'This connect link expired. Start again from the MONEI settings.', 'monei' ),
			'otherAccount'            => __( 'This shop uses another MONEI account. Disconnect first.', 'monei' ),
			'access_denied'           => __( 'MONEI refused the connect. Nothing was changed.', 'monei' ),
			'invalid_grant'           => __( 'The connect code is not valid or expired. Start again from the MONEI settings.', 'monei' ),
			'temporarily_unavailable' => __( 'MONEI is not available now. Try again later.', 'monei' ),
			'keyRejected'             => __( 'The new key did not work, so the current credentials stay in use.', 'monei' ),
			'cap'                     => __( 'Your MONEI account has too many API keys. Revoke unused keys in the MONEI Dashboard, then connect again.', 'monei' ),
			'not_approved'            => __( 'Live keys come after MONEI approves your account. The shop stays in test mode.', 'monei' ),
			'blocked'                 => __( 'MONEI refused a key for this account.', 'monei' ),
			'ineligible'              => __( 'This MONEI account cannot get a key for this mode.', 'monei' ),
		);
		$message  = $messages[ $code ] ?? __( 'Connect with MONEI failed. Nothing was changed.', 'monei' );
		if ( 'live' === $mode ) {
			/* translators: %s: the reason a mode got no new key. */
			return sprintf( __( 'Live: %s', 'monei' ), $message );
		}
		if ( 'test' === $mode ) {
			/* translators: %s: the reason a mode got no new key. */
			return sprintf( __( 'Test: %s', 'monei' ), $message );
		}
		return $message;
	}

	/**
	 * @param array<string, array{accountId: string, apiKey: string, signingSecret: string}> $switched The new keys of each switched mode.
	 */
	private function after_switch( array $switched ): void {
		// A 401 of the old key holds off the payment methods for up to a day.
		delete_option( PaymentMethodsRepository::BACKOFF_OPTION );
		$apple_google = get_option( 'woocommerce_monei_apple_google_settings', array() );
		foreach ( $switched as $mode => $keys ) {
			PaymentMethodsRepository::forget( $keys['accountId'] );
			if ( ! is_array( $apple_google ) || 'yes' !== ( $apple_google['enabled'] ?? '' ) ) {
				continue;
			}
			try {
				MoneiApplePayVerificationService::register_domain( self::client_factory()->client_for_key( $keys['apiKey'] ) );
			} catch ( Throwable $e ) {
				// The new key works: an Apple Pay error keeps the switch.
				WC_Monei_Logger::logError( '[MONEI] Apple Pay domain registration after connect failed (' . $mode . '): ' . $e->getMessage() );
			}
		}
	}

	private static function client_factory(): MoneiSdkClientFactory {
		return ContainerProvider::getContainer()->get( MoneiSdkClientFactory::class );
	}
}
