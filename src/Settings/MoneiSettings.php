<?php

namespace Monei\Settings;

use Monei\Services\ApiKeyService;
use Psr\Container\ContainerInterface;
use WC_Admin_Settings;
use WC_Settings_Page;

class MoneiSettings extends WC_Settings_Page {

	protected ContainerInterface $container;
	/**
	 * @var ApiKeyService
	 */
	private $apiKeyService;

	public function __construct( ContainerInterface $container ) {
		$this->id            = 'monei_settings';
		$this->label         = __( 'MONEI Settings', 'monei' );
		$this->container     = $container;
		$this->apiKeyService = $container->get( ApiKeyService::class );
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
				'title'    => __( 'API Key Mode', 'monei' ),
				'type'     => 'select',
				'desc'     => __( 'Choose between Test or Live API Key.', 'monei' ),
				'desc_tip' => true,
				'id'       => 'monei_apikey_mode',
				'default'  => 'test',
				'options'  => array(
					'test' => __( 'Test API Key', 'monei' ),
					'live' => __( 'Live API Key', 'monei' ),
				),
			),
			array(
				'title'       => __( 'Test Account ID *', 'monei' ),
				'type'        => 'text',
				'desc'        => __( 'Enter your MONEI Test Account ID here.', 'monei' ),
				'desc_tip'    => true,
				'id'          => 'monei_test_accountid',
				'default'     => '',
				'class'       => 'monei-api-key-field monei-test-api-key-field',
				'placeholder' => '9b1deb4d-3b7d-4bad-9bdd-2b0c11b3dcb6d',
			),
			array(
				'title'       => __( 'Live Account ID *', 'monei' ),
				'type'        => 'text',
				'desc'        => __( 'Enter your MONEI Live Account ID here.', 'monei' ),
				'desc_tip'    => true,
				'id'          => 'monei_live_accountid',
				'default'     => '',
				'class'       => 'monei-api-key-field monei-live-api-key-field',
				'placeholder' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
			),
			array(
				'title'       => __( 'Test API Key *', 'monei' ),
				'type'        => 'text',
				'desc'        => __( 'Enter your MONEI Test API Key here.', 'monei' ),
				'desc_tip'    => true,
				'id'          => 'monei_test_apikey',
				'default'     => '',
				'class'       => 'monei-api-key-field monei-test-api-key-field',
				'placeholder' => 'pk_test_d3m0t3stk3yf0rd3v3l0pm3ntus4g3',
			),
			array(
				'title'       => __( 'Live API Key *', 'monei' ),
				'type'        => 'text',
				'desc'        => __( 'Enter your MONEI Live API Key here.', 'monei' ),
				'desc_tip'    => true,
				'id'          => 'monei_live_apikey',
				'default'     => '',
				'class'       => 'monei-api-key-field monei-live-api-key-field',
				'placeholder' => 'pk_live_7h3m4n1f3st0k3yf0r3x4mpl3purp0s3',
			),
			array(
				'title'       => __( 'What to do after payment?', 'monei' ),
				'type'        => 'select',
				'description' => __( 'Choose what to do after the customer pays the order. This setting applies to all MONEI payment methods.', 'monei' ),
				'default'     => 'processing',
				'id'          => 'monei_orderdo',
				'options'     => array(
					'processing' => __( 'Mark as Processing (default & recommended)', 'monei' ),
					'completed'  => __( 'Mark as Complete', 'monei' ),
				),
			),
			array(
				'title'       => __( 'Pre-Authorize', 'monei' ),
				'type'        => 'checkbox',
				'label'       => __( 'Manually capture payments', 'monei' ),
				'description' => __( 'Place a hold on the funds when the customer authorizes the payment, but don\'t capture the funds until later. Only works with payment methods that support authorization (Credit Card, PayPal).<br>You can capture the payment by changing order status to <strong>Completed</strong> or <strong>Processing</strong>.<br>You can cancel the payment by changing order to <strong>Cancelled</strong> or <strong>Refunded</strong>.', 'monei' ),
				'default'     => 'no',
				'id'          => 'monei_pre_authorize',
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
		$settings = $this->get_settings();
		WC_Admin_Settings::output_fields( $settings );
	}

	public function save() {
		$settings = $this->get_settings();
		WC_Admin_Settings::save_fields( $settings );
		$this->apiKeyService->update_keys();
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
