<?php

class MoneiSettings extends WC_Settings_Page {

	public function __construct() {
		$this->id    = 'monei_settings';
		$this->label = __( 'MONEI Settings', 'monei' );
		parent::__construct();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function get_settings() {
		$settings = array(
			array(
				'title' => __( 'MONEI Settings', 'monei' ),
				'type'  => 'title',
				'id'    => 'monei_settings_title',
			),
			array(
				'title'    => __( 'Account ID *', 'monei' ),
				'type'     => 'text',
				'desc'     => __( 'Enter your MONEI Account ID here.', 'monei' ),
				'desc_tip' => true,
				'id'       => 'monei_accountid',
				'default'  => '',
			),
			array(
				'title'    => __( 'API Key *', 'monei' ),
				'type'     => 'text',
				'desc'     => wp_kses_post(
					__(
						'You can find your API key in <a href="https://dashboard.monei.com/settings/api" target="_blank">MONEI Dashboard</a>.<br/>Account ID and API key for the test mode are different from the live mode and can only be used for testing purposes.',
						'monei'
					)
				),
				'desc_tip' => __( 'Your MONEI API Key. It can be found in your MONEI Dashboard.', 'monei' ),
				'id'       => 'monei_apikey',
				'default'  => '',
			),
			array(
				'title'   => __( 'Test mode', 'monei' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable test mode', 'monei' ),
				'desc'    => __( 'Place the payment gateway in test mode using test API key.', 'monei' ),
				'id'      => 'monei_testmode',
				'default' => 'no',
			),
			array(
				'title'    => __( 'Debug Log', 'monei' ),
				'type'     => 'checkbox',
				'label'    => __( 'Enable logging', 'monei' ),
				'default'  => 'no',
				'desc'     => __( 'Log MONEI events inside WooCommerce > Status > Logs > Select MONEI Logs.', 'monei' ),
				'desc_tip' => __( 'Enable logging to track events such as notifications requests.', 'monei' ),
				'id'       => 'monei_debug',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'monei_settings_sectionend',
			),
		);

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	public function output() {
		$moneiIconUrl    = WC_Monei()->image_url( 'monei-logo.svg' );
		$welcomeString   = __( 'Welcome to MONEI! Enhance your payment processing experience with our seamless integration.', 'monei' );
		$dashboardString = __( 'Go to Dashboard', 'monei' );
		$supportString   = __( 'Support', 'monei' );
		$plugin_dir      = WC_Monei()->plugin_path();
		$template_path   = $plugin_dir . '/templates/html-monei-settings-header.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
		$settings = $this->get_settings();
		WC_Admin_Settings::output_fields( $settings );
	}

	public function save() {
		$settings = $this->get_settings();
		WC_Admin_Settings::save_fields( $settings );
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

		// Check if our settings tab is active
        //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_GET['tab'] ) && $_GET['tab'] === $this->id ) {
			$plugin_url = plugin_dir_url( dirname( __DIR__ ) );
			wp_enqueue_style(
				'monei-admin-css',
				$plugin_url . 'public/css/monei-admin.css',
				array(),
				'1.0.0'
			);
		}
	}
}
