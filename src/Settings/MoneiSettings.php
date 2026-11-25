<?php

namespace Monei\Settings;

use Monei\Repositories\PaymentMethodsRepository;
use Monei\Services\ApiKeyService;
use Psr\Container\ContainerInterface;
use WC_Admin_Settings;
use WC_Settings_Page;

class MoneiSettings extends WC_Settings_Page {

	protected ContainerInterface $container;
	/** @var ApiKeyService */
	private $apiKeyService;

	public function __construct( ContainerInterface $container ) {
		$this->id            = 'monei_settings';
		$this->label         = __( 'MONEI Settings', 'monei' );
		$this->container     = $container;
		$this->apiKeyService = $container->get( ApiKeyService::class );
		parent::__construct();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_footer', array( $this, 'print_disconnect_form' ) );
	}

	public function get_settings() {
		$settings = array(
			array(
				'title' => __( 'MONEI Settings', 'monei' ),
				'type'  => 'title',
				'id'    => 'monei_settings_title',
			),
			array(
				'title'   => __( 'API Key Mode', 'monei' ),
				'type'    => 'select',
				'desc'    => __( 'Set to Live to use the production environment.', 'monei' ),
				'id'      => 'monei_apikey_mode',
				'default' => 'test',
				'options' => array(
					'test' => __( 'Test', 'monei' ),
					'live' => __( 'Live', 'monei' ),
				),
			),
			array(
				'title'       => __( 'Test Account ID *', 'monei' ),
				'type'        => 'text',
				'desc'        => __( 'Your MONEI Test Account ID. Available at your MONEI dashboard.', 'monei' ),
				'id'          => 'monei_test_accountid',
				'default'     => '',
				'class'       => 'monei-account-id-field monei-test-account-id-field',
				'placeholder' => '9b1deb4d-3b7d-4bad-9bdd-2b0c11b3dcb6d',
			),
			array(
				'title'       => __( 'Live Account ID *', 'monei' ),
				'type'        => 'text',
				'desc'        => __( 'Your MONEI Account ID. Available at your MONEI dashboard.', 'monei' ),
				'id'          => 'monei_live_accountid',
				'default'     => '',
				'class'       => 'monei-account-id-field monei-live-account-id-field',
				'placeholder' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
			),
			array(
				'title'       => __( 'Test API Key *', 'monei' ),
				'type'        => 'password',
				'desc'        => __( 'Your MONEI Test API Key. Available at your MONEI dashboard.', 'monei' ),
				'id'          => 'monei_test_apikey',
				'default'     => '',
				'class'       => 'monei-api-key-field monei-test-api-key-field',
				'placeholder' => 'pk_test_d3m0t3stk3yf0rd3v3l0pm3ntus4g3',
			),
			array(
				'title'       => __( 'Live API Key *', 'monei' ),
				'type'        => 'password',
				'desc'        => __( 'Your MONEI API Key. Available at your MONEI dashboard.', 'monei' ),
				'id'          => 'monei_live_apikey',
				'default'     => '',
				'class'       => 'monei-api-key-field monei-live-api-key-field',
				'placeholder' => 'pk_live_7h3m4n1f3st0k3yf0r3x4mpl3purp0s3',
			),
			array(
				'title'   => __( 'What to do after payment?', 'monei' ),
				'type'    => 'select',
				'desc'    => __( 'Choose what to do after the customer pays the order. This setting applies to all MONEI payment methods.', 'monei' ),
				'default' => 'processing',
				'id'      => 'monei_orderdo',
				'options' => array(
					'processing' => __( 'Mark as Processing (Recommended)', 'monei' ),
					'completed'  => __( 'Mark as Complete', 'monei' ),
				),
			),
			array(
				'title'   => __( 'Payment Action', 'monei' ),
				'type'    => 'select',
				'desc'    => __( 'Choose payment flow: Immediate charge or Pre-authorization.<br>Pre-authorization is supported for: Card, Apple Pay, Google Pay, PayPal.<br>Not supported for: MBWay, Multibanco.', 'monei' ),
				'default' => 'no',
				'id'      => 'monei_pre_authorize',
				'options' => array(
					'no'  => __( 'Sale (Immediate charge)', 'monei' ),
					'yes' => __( 'Authorization (Pre-authorization)', 'monei' ),
				),
			),
			array(
				'title'   => __( 'Log Level', 'monei' ),
				'type'    => 'select',
				'desc'    => __( 'Set minimum log level. Only messages at or above this level will be logged.<br>Logs are available in WooCommerce > Status > Logs > Select MONEI Logs.<br>WARNING: INFO level may impact performance.', 'monei' ),
				'id'      => 'monei_log_level',
				'default' => '3',
				'options' => array(
					'1' => __( 'INFO - All messages (Debug mode)', 'monei' ),
					'2' => __( 'WARNING - Warnings and errors only', 'monei' ),
					'3' => __( 'ERROR - Errors only (Recommended)', 'monei' ),
					'4' => __( 'NONE - Disable logging', 'monei' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'monei_settings_sectionend',
			),
		);

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	public function output() {
		$data = array(
			'moneiIconUrl'    => WC_Monei()->image_url( 'monei-logo.svg' ),
			'welcomeString'   => __( 'Welcome to MONEI! Enhance your payment processing experience with our seamless integration', 'monei' ),
			'dashboardString' => __( 'Go to Dashboard', 'monei' ),
			'supportString'   => __( 'Support', 'monei' ),
			'reviewString'    => __( 'Leave a review', 'monei' ),
		);

		$templateManager = $this->container->get( 'Monei\Templates\TemplateManager' );
		$template        = $templateManager->getTemplate( 'monei-settings-header' );
		if ( $template ) {
			$template->render( $data );
		}
		$this->render_connect_section();
		$settings = $this->get_settings();
		WC_Admin_Settings::output_fields( $settings );
	}

	public function save() {
		$connect = ConnectHandler::connect_service();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC_Admin_Settings::save() checked the nonce.
		$fingerprints = isset( $_POST['monei_key_fingerprint'] ) && is_array( $_POST['monei_key_fingerprint'] ) ? map_deep( wp_unslash( $_POST['monei_key_fingerprint'] ), 'sanitize_text_field' ) : array();
		$changed      = $connect->modes_changed_since( $fingerprints );
		$keys_before  = $connect->api_keys();
		$settings     = $this->get_settings();
		if ( $changed ) {
			// A connect can also set the mode.
			$skipped = array( 'monei_apikey_mode' );
			foreach ( $changed as $mode ) {
				$skipped[] = "monei_{$mode}_accountid";
				$skipped[] = "monei_{$mode}_apikey";
			}
			$settings = array_filter(
				$settings,
				function ( $field ) use ( $skipped ) {
					return ! in_array( $field['id'] ?? '', $skipped, true );
				}
			);
			WC_Admin_Settings::add_error( __( 'The MONEI keys changed in another tab, so the key fields were not saved. Reload this page.', 'monei' ) );
		}
		WC_Admin_Settings::save_fields( $settings );
		$connect->forget_secrets_of_changed_keys( $keys_before );
		$this->apiKeyService->update_keys();
		// A saved key is the merchant's fix; make the next request try it.
		delete_option( PaymentMethodsRepository::BACKOFF_OPTION );
	}

	/**
	 * Connect or "Use manual keys", Disconnect, and the last 4 characters of the key in use.
	 */
	private function render_connect_section(): void {
		$messages = get_option( ConnectHandler::MESSAGES_OPTION, array() );
		if ( $messages ) {
			delete_option( ConnectHandler::MESSAGES_OPTION );
			foreach ( (array) $messages as $message ) {
				echo '<div class="notice notice-info inline"><p>' . esc_html( $message ) . '</p></div>';
			}
		}
		$api_key     = $this->apiKeyService->get_api_key();
		$can_connect = null !== ConnectHandler::redirect_uri();
		?>
		<?php // Connect is the main flow: monei-settings.js collapses the manual key fields while it is available. ?>
		<div id="monei-connect-section" data-manual-keys="<?php echo $can_connect ? 'closed' : 'open'; ?>">
		<?php // A save compares these with the stored keys: a connect in another tab must not be undone by this form. ?>
		<?php foreach ( ConnectHandler::connect_service()->key_fingerprints() as $mode => $fingerprint ) : ?>
			<input type="hidden" name="monei_key_fingerprint[<?php echo esc_attr( $mode ); ?>]" value="<?php echo esc_attr( $fingerprint ); ?>">
		<?php endforeach; ?>
		<h2><?php esc_html_e( 'Connect with MONEI', 'monei' ); ?></h2>
		<p>
			<?php if ( $can_connect ) : ?>
				<a id="monei-connect" class="button button-primary" target="_blank" rel="noopener"
					href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=monei_connect_start' ), 'monei_connect_start' ) ); ?>"><?php esc_html_e( 'Connect with MONEI', 'monei' ); ?></a>
			<?php else : ?>
				<span id="monei-connect-manual"><?php esc_html_e( 'Use manual keys. Connect with MONEI needs a public HTTPS address for this shop.', 'monei' ); ?></span>
			<?php endif; ?>
			<?php if ( ConnectHandler::connect_service()->is_connected() ) : ?>
				<?php // A submit of the settings form would be its default button: Enter in a key field would disconnect. ?>
				<button type="submit" id="monei-disconnect" class="button" form="<?php echo esc_attr( ConnectHandler::DISCONNECT_FORM_ID ); ?>"><?php esc_html_e( 'Disconnect', 'monei' ); ?></button>
			<?php endif; ?>
		</p>
		<?php if ( '' !== $api_key ) : ?>
			<p id="monei-key-in-use">
				<?php
				printf(
					/* translators: 1: Test or Live, 2: last 4 characters of the API key. */
					esc_html__( 'Key in use (%1$s): ending in %2$s', 'monei' ),
					esc_html( $this->apiKeyService->is_test_mode() ? __( 'Test', 'monei' ) : __( 'Live', 'monei' ) ),
					esc_html( substr( $api_key, -4 ) )
				);
				?>
			</p>
		<?php endif; ?>
		<p><a href="<?php echo esc_url( ConnectHandler::DASHBOARD_KEYS_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Manage and revoke API keys in the MONEI Dashboard', 'monei' ); ?></a></p>
		<?php if ( $can_connect ) : ?>
			<p><a href="#" id="monei-manual-keys-toggle"><?php esc_html_e( 'Enter API keys manually', 'monei' ); ?></a></p>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * The Disconnect form, outside the WooCommerce settings form, on the MONEI tab only.
	 */
	public function print_disconnect_form(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks the page to print on.
		if ( ! isset( $_GET['tab'] ) || 'monei_settings' !== $_GET['tab'] ) {
			return;
		}
		?>
		<form id="<?php echo esc_attr( ConnectHandler::DISCONNECT_FORM_ID ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=monei_disconnect' ) ); ?>">
			<?php wp_nonce_field( 'monei_disconnect', '_monei_disconnect_nonce' ); ?>
		</form>
		<?php
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();

		// Ensure we're on the WooCommerce settings page
		if ( $screen->id !== 'woocommerce_page_wc-settings' ) {
			return;
		}

		$plugin_url = plugin_dir_url( dirname( __DIR__ ) );
		wp_enqueue_style(
			'monei-admin-css',
			$plugin_url . 'public/css/monei-admin.css',
			array(),
			MONEI_VERSION
		);
		wp_register_script(
			'monei-admin-script',
			$plugin_url . 'public/js/monei-settings.min.js',
			array( 'jquery' ),
			WC_Monei()->version,
			true
		);
		wp_enqueue_script(
			'monei-admin-script'
		);
	}
}
