const { execFileSync } = require( 'child_process' );
const { requireEnv } = require( './env' );

/**
 * Directory holding the docker-compose stack that serves the test site.
 */
const WP_DIR = requireEnv(
	'MONEI_E2E_WP_DIR',
	'It must be the docker-compose directory of the same site MONEI_E2E_BASE_URL points at.'
);

/**
 * Run a WP-CLI command against the docker test site.
 *
 * Throws on a non zero exit so a broken fixture fails the test instead of
 * silently leaving the site in the wrong state.
 * @param {string[]} args - WP-CLI arguments
 * @return {string} Trimmed stdout
 */
const wpCli = ( args ) =>
	execFileSync(
		'docker-compose',
		[ 'run', '--rm', '--entrypoint', 'wp', 'wp-cli', ...args ],
		{ cwd: WP_DIR, encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'pipe' ] }
	).trim();

/**
 * Set the MONEI credit card field layout.
 * @param {'single'|'split'} layout - Layout to activate
 */
const setCardFieldLayout = ( layout ) =>
	wpCli( [
		'option',
		'patch',
		'update',
		'woocommerce_monei_settings',
		'card_field_layout',
		layout,
	] );

/**
 * Read the current MONEI credit card field layout.
 * @return {string} Current layout
 */
const getCardFieldLayout = () => {
	const settings = JSON.parse(
		wpCli( [
			'option',
			'get',
			'woocommerce_monei_settings',
			'--format=json',
		] )
	);
	return settings.card_field_layout || 'single';
};

/**
 * Read the WooCommerce checkout page id.
 * @return {string} Page id
 */
const getCheckoutPageId = () =>
	wpCli( [ 'option', 'get', 'woocommerce_checkout_page_id' ] );

/**
 * Point WooCommerce at a checkout page.
 *
 * The classic checkout only enqueues the MONEI card scripts while it is the
 * configured checkout page, because the gateway gates on `is_checkout()`.
 * @param {string|number} pageId - Page id to make the checkout page
 */
const setCheckoutPageId = ( pageId ) =>
	wpCli( [
		'option',
		'update',
		'woocommerce_checkout_page_id',
		String( pageId ),
	] );

/**
 * Make sure a percentage coupon exists, so a test can move the cart total.
 * @param {string} code    - Coupon code
 * @param {number} percent - Discount percentage
 */
const ensureCoupon = ( code, percent ) => {
	const existing = wpCli( [
		'wc',
		'shop_coupon',
		'list',
		'--user=1',
		`--code=${ code }`,
		'--field=id',
	] );
	if ( existing ) {
		return;
	}
	wpCli( [
		'wc',
		'shop_coupon',
		'create',
		'--user=1',
		`--code=${ code }`,
		`--amount=${ percent }`,
		'--discount_type=percent',
		'--porcelain',
	] );
};

/**
 * Read the WooCommerce status of an order.
 *
 * The thank you page only says the browser landed somewhere; the order status is
 * what says money moved, so a payment test has to read it from the store itself.
 * @param {string|number} orderId - Order id
 * @return {string} Order status, without the `wc-` prefix
 */
const getOrderStatus = ( orderId ) =>
	wpCli( [
		'wc',
		'shop_order',
		'get',
		String( orderId ),
		'--user=1',
		'--field=status',
	] );

/**
 * Read the express checkout settings of a MONEI gateway.
 *
 * Returned as the pair of keys a test has to put back, not the whole settings
 * blob: writing a whole blob back would undo anything else that changed while
 * the run was in progress.
 * @param {string} option - Gateway settings option name
 * @return {Object} `{ express_enabled, express_locations }`
 */
const getExpressSettings = ( option ) => {
	const settings = JSON.parse(
		wpCli( [ 'option', 'get', option, '--format=json' ] )
	);

	return {
		express_enabled: settings.express_enabled || 'no',
		express_locations: settings.express_locations || [],
	};
};

/**
 * Write express checkout settings into a MONEI gateway.
 *
 * `option patch` refuses a key the option does not have yet, and express keys
 * are absent until a merchant saves the settings screen once, so the option is
 * merged in PHP instead.
 * @param {string} option - Gateway settings option name
 * @param {Object} values - Keys to merge in
 */
const setExpressSettings = ( option, values ) =>
	wpCli( [
		'eval',
		`$s = (array) get_option( '${ option }', array() );` +
			`$s = array_merge( $s, json_decode( '${ JSON.stringify(
				values
			) }', true ) );` +
			`update_option( '${ option }', $s );`,
	] );

module.exports = {
	wpCli,
	getExpressSettings,
	setExpressSettings,
	setCardFieldLayout,
	getCardFieldLayout,
	getCheckoutPageId,
	setCheckoutPageId,
	ensureCoupon,
	getOrderStatus,
};
