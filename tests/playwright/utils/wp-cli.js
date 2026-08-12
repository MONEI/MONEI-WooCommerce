const { execFileSync } = require( 'child_process' );

/**
 * Directory holding the docker-compose stack that serves the test site.
 */
const WP_DIR =
	process.env.MONEI_E2E_WP_DIR || '/Users/dmitriy/Work/woocommerce';

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

module.exports = {
	wpCli,
	setCardFieldLayout,
	getCardFieldLayout,
	getCheckoutPageId,
	setCheckoutPageId,
	ensureCoupon,
};
