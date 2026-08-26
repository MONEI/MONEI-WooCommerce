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
use Monei\Gateways\PaymentMethods\WCGatewayMoneiPaypal;
use Monei\Services\PaymentMethodsService;
use WC_AJAX;
use WC_Product;
use WP_Post;
use Exception;

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
	 * Handle of the express stylesheet, shared by the classic and blocks paths.
	 */
	const STYLE_HANDLE = 'monei-express-checkout';

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
	 * The wallet component each express gateway renders. These keys are the contract
	 * with the JavaScript: they name the component factory and the mount container.
	 */
	const METHOD_PAYMENT_REQUEST = 'payment_request';
	const METHOD_PAYPAL          = 'paypal';

	/**
	 * Express gateways by wallet component, or null before they are resolved.
	 *
	 * @var array<string, WCMoneiPaymentGateway>|null
	 */
	private static $gateways = null;

	/**
	 * Register the frontend hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_classic_assets' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'render_checkout_buttons' ), 5 );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'render_cart_buttons' ), 5 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'render_product_buttons' ) );
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

		self::enqueue_style();

		if ( ! wp_script_is( 'monei', 'registered' ) ) {
			wp_register_script( 'monei', 'https://js.monei.com/v3/monei.js', '', '3.0', true );
		}
		wp_enqueue_script( 'monei' );

		wp_register_script(
			self::SCRIPT_HANDLE,
			plugins_url( 'public/js/monei-express-checkout.min.js', MONEI_MAIN_FILE ),
			// `jquery-blockui` is WooCommerce's own loading treatment, and the express
			// script uses it to cover the gap between the wallet sheet closing and the
			// redirect. WooCommerce loads it on cart and checkout anyway, but not
			// reliably on a product page, so it is declared rather than assumed.
			array( 'jquery', 'jquery-blockui', 'monei' ),
			MONEI_VERSION,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wc_monei_express_params',
			array_merge(
				self::get_script_data(),
				array(
					'location' => $location,
					'product'  => 'product' === $location ? $this->get_product_context() : null,
				)
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

		$this->render_container( 'checkout' );
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

		$this->render_container( 'cart' );
	}

	/**
	 * Express button container below the add-to-cart form.
	 *
	 * @return void
	 */
	public function render_product_buttons() {
		if ( 'product' !== $this->get_classic_location() ) {
			return;
		}

		$this->render_container( 'product' );
	}

	/**
	 * Prints the mount containers, one per wallet the merchant enabled at this surface.
	 *
	 * Everything starts hidden and the script reveals it only once a wallet reports
	 * itself supported, so an unavailable wallet leaves no gap and no dead control.
	 *
	 * @param string $location One of the express location keys.
	 *
	 * @return void
	 */
	private function render_container( $location ) {
		$methods = array_keys( self::get_enabled_methods( $location ) );

		if ( empty( $methods ) ) {
			return;
		}
		?>
		<div class="monei-express-checkout is-loading" data-monei-express-location="<?php echo esc_attr( $location ); ?>">
			<div class="monei-express-checkout__title"><?php esc_html_e( 'Express checkout', 'monei' ); ?></div>
			<?php foreach ( $methods as $method ) : ?>
				<div class="monei-express-checkout__button" data-monei-express-method="<?php echo esc_attr( $method ); ?>"></div>
			<?php endforeach; ?>
			<div class="monei-express-checkout__error" role="alert"></div>
		</div>
		<?php
	}

	/**
	 * Everything the express scripts need that is identical on classic and blocks.
	 *
	 * Both wallets are described in one payload, and both blocks payment methods carry
	 * the same copy of it, so the express script works whichever gateway happened to
	 * put it on the page.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_script_data() {
		$methods    = array();
		$account_id = '';

		foreach ( self::get_express_gateways() as $method => $gateway ) {
			$locations = array();

			foreach ( array_keys( WCMoneiPaymentGateway::get_express_location_options() ) as $location ) {
				$locations[ $location ] = $gateway->is_express_enabled_at( (string) $location );
			}

			$methods[ $method ] = array(
				'locations' => $locations,
				// PayPal takes different style keys from PaymentRequest — color, layout,
				// size, shape, label — so each wallet carries its own.
				'style'     => json_decode( self::get_button_style( $gateway ) ),
				// Whether the MONEI account offers this wallet at all. The blocks
				// registry reserves a grid column per registered express method before
				// any component mounts, so a wallet the account cannot serve has to be
				// refused at registration; discovering it later through `onLoad` leaves
				// an empty column behind that halves the width of its neighbour.
				'available' => self::account_offers( $method ),
			);

			if ( '' === $account_id ) {
				$account_id = (string) $gateway->getAccountId();
			}
		}

		return array(
			// The `%%endpoint%%` placeholder is how WooCommerce core itself hands a
			// wc-ajax URL template to the browser, see wc_cart_fragments_params.
			'ajaxUrl'   => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'accountId' => $account_id,
			'currency'  => get_woocommerce_currency(),
			'language'  => locale_iso_639_1_code(),
			'methods'   => $methods,
			'i18n'      => array(
				'genericError' => __( 'Express checkout is unavailable right now. Please use the regular checkout.', 'monei' ),
			),
		);
	}

	/**
	 * Registers the express script for the Cart and Checkout blocks.
	 *
	 * Called by both blocks payment methods, so express still loads when only one of
	 * the two gateways is on. Registering an existing handle twice is a no-op, and the
	 * handle is deduplicated when WooCommerce merges it into the block bundle.
	 *
	 * @return string Handle, or an empty string when no wallet has express on a block
	 *                surface.
	 */
	public static function register_blocks_script() {
		if ( empty( self::get_enabled_methods( 'cart' ) ) && empty( self::get_enabled_methods( 'checkout' ) ) ) {
			return '';
		}

		$handle = self::BLOCKS_SCRIPT_HANDLE;

		self::enqueue_style();

		wp_register_script(
			$handle,
			WC_Monei()->plugin_url() . '/public/js/monei-block-express-checkout.min.js',
			array(
				'wc-blocks-checkout',
				'wc-blocks-registry',
				'wc-settings',
				'wp-data',
				'wp-element',
				'wp-i18n',
				'monei',
			),
			WC_Monei()->version,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'monei', WC_Monei()->plugin_path() . '/languages' );
		}

		return $handle;
	}

	/**
	 * Express gateways that are enabled at a surface, keyed by wallet component.
	 *
	 * @param string $location One of the express location keys.
	 *
	 * @return array<string, WCMoneiPaymentGateway>
	 */
	private static function get_enabled_methods( $location ) {
		$enabled = array();

		foreach ( self::get_express_gateways() as $method => $gateway ) {
			if ( $gateway->is_express_enabled_at( $location ) ) {
				$enabled[ $method ] = $gateway;
			}
		}

		return $enabled;
	}

	/**
	 * @param WCMoneiPaymentGateway $gateway Express gateway.
	 *
	 * @return string
	 */
	private static function get_button_style( WCMoneiPaymentGateway $gateway ) {
		return (string) $gateway->get_option( 'express_button_style', $gateway::DEFAULT_EXPRESS_BUTTON_STYLE );
	}

	/**
	 * The gateways that expose express checkout, keyed by the wallet they render.
	 *
	 * Resolved on demand rather than injected, for the same reason the AJAX handler
	 * does it: this service is built during `init`, before WooCommerce assembles its
	 * payment gateways.
	 *
	 * @return array<string, WCMoneiPaymentGateway>
	 */
	/**
	 * Whether the MONEI account can serve the wallet behind an express method.
	 *
	 * ⚠️ Fails open. A false negative hides a wallet the merchant has switched on and
	 * paid to enable; a false positive costs an empty column. So an unreadable answer
	 * — the API down, the account response cached empty — registers the method and
	 * lets `onLoad` sort it out, which is the behaviour this replaced.
	 *
	 * @param string $method Express method key.
	 *
	 * @return bool
	 */
	private static function account_offers( $method ) {
		try {
			$methods = ContainerProvider::getContainer()->get( PaymentMethodsService::class );

			if ( self::METHOD_PAYPAL === $method ) {
				return $methods->isPaypalEnabled();
			}

			return $methods->isGoogleEnabled() || $methods->isAppleEnabled();
		} catch ( Exception $e ) {
			return true;
		}
	}

	/**
	 * Loads the express stylesheet, on whichever surface asked for it.
	 *
	 * ⚠️ Blocks needs this too. It used to be registered only on the classic path,
	 * so a Cart or Checkout block got the express markup with none of its styling —
	 * including `.monei-express-checkout__error`, which is how a failed express
	 * payment tells the shopper what went wrong. Unstyled, that message inherited
	 * whatever the theme did with a bare div, and `:empty` never hid it.
	 *
	 * @return void
	 */
	public static function enqueue_style() {
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			wp_register_style(
				self::STYLE_HANDLE,
				plugins_url( 'public/css/monei-express-checkout.css', MONEI_MAIN_FILE ),
				array(),
				MONEI_VERSION,
				'all'
			);
		}

		wp_enqueue_style( self::STYLE_HANDLE );
	}

	private static function get_express_gateways() {
		if ( null !== self::$gateways ) {
			return self::$gateways;
		}

		$container      = ContainerProvider::getContainer();
		self::$gateways = array();

		$classes = array(
			self::METHOD_PAYMENT_REQUEST => WCGatewayMoneiAppleGoogle::class,
			self::METHOD_PAYPAL          => WCGatewayMoneiPaypal::class,
		);

		foreach ( $classes as $method => $class_name ) {
			$gateway = $container->get( $class_name );

			if ( $gateway instanceof WCMoneiPaymentGateway ) {
				self::$gateways[ $method ] = $gateway;
			}
		}

		return self::$gateways;
	}

	/**
	 * The product the page is showing, for the product page express flow.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_product_context() {
		$product = wc_get_product();

		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		return array(
			'id'          => $product->get_id(),
			'isVariable'  => $product->is_type( 'variable' ),
			'purchasable' => $product->is_purchasable() && $product->is_in_stock(),
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
		if ( is_checkout() && ! is_checkout_pay_page() && ! is_add_payment_method_page() ) {
			if ( self::current_page_has_block( 'woocommerce/checkout' ) ) {
				return null;
			}

			return empty( self::get_enabled_methods( 'checkout' ) ) ? null : 'checkout';
		}

		if ( is_product() ) {
			return empty( self::get_enabled_methods( 'product' ) ) ? null : 'product';
		}

		if ( is_cart() ) {
			if ( self::current_page_has_block( 'woocommerce/cart' ) ) {
				return null;
			}

			return empty( self::get_enabled_methods( 'cart' ) ) ? null : 'cart';
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
}
