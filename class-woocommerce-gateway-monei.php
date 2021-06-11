<?php
/**
 * Installation related functions and actions.
 *
 * @author   Manuel Rodriguez
 * @category Core
 * @package  Woocommerce_Gateway_Monei
 * @version  5.0
 */
if ( ! class_exists( 'Woocommerce_Gateway_Monei' ) ) :

	final class Woocommerce_Gateway_Monei {

		/**
		 * Woocommerce_Gateway_Monei version.
		 *
		 * @var string
		 */
		public $version = '5.0';

		/**
		 * The single instance of the class.
		 *
		 * @var Woocommerce_Gateway_Monei
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * @var bool
		 */
		protected static $_initialized = false;

		/**
		 * Main Woocommerce_Gateway_Monei Instance.
		 *
		 * Ensures only one instance of Woocommerce_Gateway_Monei is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return Woocommerce_Gateway_Monei - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
				self::$_instance->initalize_plugin();
			}
			return self::$_instance;
		}

		/**
		 * Woocommerce_Gateway_Monei Initializer.
		 */
		public function initalize_plugin() {
			if ( self::$_initialized ) {
				_doing_it_wrong( __FUNCTION__, esc_html__( 'Use Singleton.', 'monei' ), '1.0.0' );
				return;
			}

			self::$_initialized = true;
			add_action( 'plugins_loaded', array( $this, 'continue_init' ), -1 );
		}

		/**
		 * Plugin initialization.
		 */
		public function continue_init() {
			if ( ! $this->check_dependencies() ) {
				add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
				return;
			}

			if ( ! $this->get_installed_version() ) {
				add_action( 'admin_notices', array( $this, 'admin_new_install_notice' ) );
			}

			$this->define_constants();
			$this->includes();
			$this->init_hooks();

			do_action( 'woocommerce_gateway_monei_loaded' );
		}

		/**
		 * WC_Monei Constants.
		 */
		private function define_constants() {
			$this->define( 'MONEI_GATEWAY_ID', 'monei' );
			$this->define( 'MONEI_VERSION', $this->version );
			$this->define( 'MONEI_SIGNUP', 'https://dashboard.monei.net/?action=signUp' );
			$this->define( 'MONEI_WEB', 'https://monei.net/' );
			$this->define( 'MONEI_REVIEW', 'https://wordpress.org/support/plugin/monei/reviews/?rate=5#new-post' );
			$this->define( 'MONEI_SUPPORT', 'https://support.monei.net/' );
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		private function includes() {

			include_once 'includes/woocommerce-gateway-monei-template-functions.php';
			include_once 'includes/woocommerce-gateway-monei-core-functions.php';
			include_once 'includes/class-wc-monei-ipn.php';

			if ( $this->is_request( 'admin' ) ) {

			}

			if ( $this->is_request( 'frontend' ) ) {
				include_once 'includes/class-wc-monei-logger.php';
				include_once 'includes/class-wc-monei-api.php';
				include_once 'includes/class-wc-monei-redirect.php';
			}
		}

		/**
		 * Hook into actions and filters.
		 * @since  1.0.0
		 */
		private function init_hooks() {
			add_action( 'init', array( $this, 'init' ), 0 );
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 0 );
		}

		/**
		 * Prints notice when it is a new install. It will save the current install version on Dismiss.
		 *
		 * @return void
		 */
		function admin_new_install_notice() {
			/**
			 * If Dismissed, we save the versions installed.
			 */
			if ( isset( $_GET['monei-hide-new-version'] ) && 'hide-new-version-monei' === $_GET['monei-hide-new-version'] ) {
				if ( wp_verify_nonce( $_GET['_monei_hide_new_version_nonce'], 'monei_hide_new_version_nonce' ) ) {
					update_option( 'hide-new-version-monei-notice', MONEI_VERSION );
				}
				return;
			}
			woocommerce_gateway_monei_get_template( 'notice-admin-new-install.php' );
		}

		/**
		 * Prints notice about requirements not met.
		 *
		 * @return void
		 */
		public function dependency_notice() {
			woocommerce_gateway_monei_get_template( 'notice-admin-dependency.php' );
		}

		/**
		 * Returns true if Woo is activated.
		 *
		 * @return bool
		 */
		public function check_dependencies() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Define constant if not already set.
		 *
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * @param $type
		 *
		 * @return bool
		 */
		private function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}

		/**
		 * Init Woocommerce_Gateway_Monei when WordPress Initialises.
		 */
		public function init() {
			// Before init
			do_action( 'before_woocommerce_gateway_monei_init' );

			// todo: not translation yet.
			//$this->load_plugin_textdomain();

			// Init action.
			do_action( 'woocommerce_gateway_monei_init' );
		}

		/**
		 * Hooks when plugin_loaded
		 */
		public function plugins_loaded() {
			$this->include_payment_methods();
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
		}

		/**
		 * Include Payment Methods.
		 */
		private function include_payment_methods() {
			// Including abstract.
			include_once 'includes/abstracts/abstract-wc-monei-payment-gateway.php';
			// Including hosted payments.
			include_once 'includes/payment-methods/class-wc-gateway-monei-hosted.php';
		}

		/**
		 * Add Monei Gateways.
		 *
		 * @param $methods
		 *
		 * @return array
		 */
		public function add_gateways( $methods ) {
			$methods[] = 'WC_Gateway_monei';
			return $methods;
		}

		/**private function load_plugin_textdomain() {
		}**/

		/**
		 * Get installed version. For retro compat we keep "hide-new-version-monei-notice"
		 *
		 * @return false|string
		 */
		private function get_installed_version() {
			return get_option( 'hide-new-version-monei-notice' );
		}

		/**
		 * Get IPN url.
		 *
		 * @return string
		 */
		public function get_ipn_url() {
			return WC()->api_request_url( 'monei_ipn' );
		}

		/**
		 * Get the plugin url.
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugins_url( '/', __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Get the template path.
		 *
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'woocommerce_gateway_monei_template', 'woocommerce-gateway-monei/' );
		}

		/**
		 * Get Image URL.
		 *
		 * @param string $image
		 *
		 * @return string
		 */
		public function image_url( $image = '' ) {
			return $this->plugin_url() . '/assets/images/' . $image;
		}

		/**
		 * Get Ajax URL.
		 * @return string
		 */
		public function ajax_url() {
			return admin_url( 'admin-ajax.php', 'relative' );
		}
	}

endif;

