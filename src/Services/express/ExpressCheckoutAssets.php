<?php
/**
 * Express checkout asset loading and classic markup.
 *
 * @package Monei
 */

namespace Monei\Services\express;

use Monei\Core\ContainerProvider;
use Monei\Gateways\Abstracts\WCMoneiPaymentGateway;
use Monei\Gateways\PaymentMethods\WCGatewayMoneiAppleGoogle;
use WC_AJAX;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the express checkout button assets and prints the mount containers on the
 * classic (non-block) product, cart and checkout pages.
 *
 * The express surfaces span three pages, while `WCGatewayMoneiAppleGoogle::monei_scripts()`
 * deliberately only loads on checkout. Widening that method would change when the
 * regular Apple/Google Pay checkout assets load, so express gets its own service
 * instead — the same shape as ExpressCheckoutAjaxHandler, bootstrapped from the
 * plugin's `init()` because the container is lazy.
 *
 * Block surfaces load nothing from here: the Cart and Checkout blocks pull their
 * script through the payment method registry, in MoneiAppleGoogleBlocksSupport.
 */
class ExpressCheckoutAssets {

	/**
	 * Handle of the classic express script.
	 */
	const SCRIPT_HANDLE = 'monei-express-checkout';

	/**
	 * Handle of the blocks express registration script.
	 */
	const BLOCKS_SCRIPT_HANDLE = 'wc-monei-express-blocks-integration';

	/**
	 * Name the blocks express method registers under. It must differ from the
	 * `monei_apple_google` entry the regular payment method already occupies, or the
	 * second registration silently replaces the first.
	 */
	const BLOCKS_METHOD_NAME = 'monei_apple_google_express';

	/**
	 * Resolved Apple/Google gateway, or false when it cannot be built.
	 *
	 * @var WCGatewayMoneiAppleGoogle|false|null
	 */
	private $gateway = null;

	/**
	 * Register the frontend hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_classic_assets' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'render_checkout_buttons' ), 5 );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'render_cart_buttons' ), 5 );
	}

	/**
	 * Loads the classic express bundle on the surfaces the merchant enabled.
	 *
	 * @return void
	 */
	public function enqueue_classic_assets() {
		$location = $this->get_classic_location();

		if ( null === $location ) {
			return;
		}

		$gateway = $this->get_gateway();

		if ( ! $gateway instanceof WCGatewayMoneiAppleGoogle ) {
			return;
		}

		wp_register_style(
			'monei-express-checkout',
			plugins_url( 'public/css/monei-express-checkout.css', MONEI_MAIN_FILE ),
			array(),
			MONEI_VERSION,
			'all'
		);
		wp_enqueue_style( 'monei-express-checkout' );

		if ( ! wp_script_is( 'monei', 'registered' ) ) {
			wp_register_script( 'monei', 'https://js.monei.com/v3/monei.js', '', '3.0', true );
		}
		wp_enqueue_script( 'monei' );

		wp_register_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'public/js/monei-express-checkout.min.js', MONEI_MAIN_FILE ),
			array( 'jquery', 'monei' ),
			MONEI_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wc_monei_express_params',
			array_merge(
				self::get_script_data( $gateway ),
				array( 'location' => $location )
			)
		);

		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Express button container above the classic checkout form.
	 *
	 * @return void
	 */
	public function render_checkout_buttons() {
		if ( 'checkout' !== $this->get_classic_location() ) {
			return;
		}

		self::render_container( 'checkout' );
	}

	/**
	 * Express button container above the classic cart's checkout button.
	 *
	 * @return void
	 */
	public function render_cart_buttons() {
		if ( 'cart' !== $this->get_classic_location() ) {
			return;
		}

		self::render_container( 'cart' );
	}

	/**
	 * Prints a mount container. It starts hidden and the script reveals it only once
	 * the wallet reports itself supported, so an unavailable wallet leaves no gap and
	 * no dead control.
	 *
	 * @param string $location One of the express location keys.
	 *
	 * @return void
	 */
	public static function render_container( $location ) {
		?>
		<div class="monei-express-checkout is-loading" data-monei-express-location="<?php echo esc_attr( $location ); ?>">
			<div class="monei-express-checkout__title"><?php esc_html_e( 'Express checkout', 'monei' ); ?></div>
			<div class="monei-express-checkout__button" data-monei-express-method="payment_request"></div>
			<div class="monei-express-checkout__error" role="alert"></div>
		</div>
		<?php
	}

	/**
	 * Everything the express scripts need that is identical on classic and blocks.
	 *
	 * @param WCGatewayMoneiAppleGoogle $gateway Apple/Google gateway.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_script_data( WCGatewayMoneiAppleGoogle $gateway ) {
		$style = $gateway->get_option( 'express_button_style', WCGatewayMoneiAppleGoogle::DEFAULT_EXPRESS_BUTTON_STYLE );

		$locations = array();

		foreach ( array_keys( WCMoneiPaymentGateway::get_express_location_options() ) as $location ) {
			$locations[ $location ] = $gateway->is_express_enabled_at( (string) $location );
		}

		return array(
			// The `%%endpoint%%` placeholder is how WooCommerce core itself hands a
			// wc-ajax URL template to the browser, see wc_cart_fragments_params.
			'ajaxUrl'     => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'accountId'   => $gateway->getAccountId(),
			'currency'    => get_woocommerce_currency(),
			'language'    => locale_iso_639_1_code(),
			'buttonStyle' => json_decode( $style ),
			'locations'   => $locations,
			'i18n'        => array(
				'genericError' => __( 'Express checkout is unavailable right now. Please use the regular checkout.', 'monei' ),
			),
		);
	}

	/**
	 * Express location of the current request, or null when express must not render.
	 *
	 * Block-rendered cart and checkout pages return null: their buttons come from the
	 * payment method registry, and loading the classic bundle there would mount a
	 * second set.
	 *
	 * @return string|null
	 */
	private function get_classic_location() {
		$gateway = $this->get_gateway();

		if ( ! $gateway instanceof WCGatewayMoneiAppleGoogle ) {
			return null;
		}

		if ( is_checkout() && ! is_checkout_pay_page() && ! is_add_payment_method_page() ) {
			if ( self::current_page_has_block( 'woocommerce/checkout' ) ) {
				return null;
			}

			return $gateway->is_express_enabled_at( 'checkout' ) ? 'checkout' : null;
		}

		if ( is_cart() ) {
			if ( self::current_page_has_block( 'woocommerce/cart' ) ) {
				return null;
			}

			return $gateway->is_express_enabled_at( 'cart' ) ? 'cart' : null;
		}

		return null;
	}

	/**
	 * Whether the page being rendered right now contains a block.
	 *
	 * Deliberately not `WC_Blocks_Utils::has_block_in_page( wc_get_page_id( ... ) )`:
	 * that asks about the page configured in WooCommerce settings, so a store whose
	 * configured checkout is a block would answer "block" for a second, shortcode-based
	 * checkout page too, and the classic buttons would never load there.
	 *
	 * @param string $block Block name.
	 *
	 * @return bool
	 */
	private static function current_page_has_block( $block ) {
		$post = get_post();

		return $post instanceof WP_Post && has_block( $block, $post );
	}

	/**
	 * Resolved on demand rather than injected, for the same reason the AJAX handler
	 * does it: this service is built during `init`, before WooCommerce assembles its
	 * payment gateways.
	 *
	 * @return WCGatewayMoneiAppleGoogle|false
	 */
	private function get_gateway() {
		if ( null !== $this->gateway ) {
			return $this->gateway;
		}

		$gateway = ContainerProvider::getContainer()->get( WCGatewayMoneiAppleGoogle::class );

		$this->gateway = $gateway instanceof WCGatewayMoneiAppleGoogle ? $gateway : false;

		return $this->gateway;
	}
}
