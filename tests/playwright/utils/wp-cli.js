const { execFileSync } = require( 'child_process' );
const { PLUGIN_ROOT, isWpEnv, requireEnv } = require( './env' );

/**
 * How to reach WP-CLI, which depends on who serves the site.
 *
 * wp-env is the default: it brings its own WordPress, so the suite runs on a
 * machine that has nothing but Docker on it. A `MONEI_E2E_WP_DIR` points at a
 * docker-compose stack instead, which is the setup a developer with an existing
 * local store already has.
 *
 * ⚠️ The wp-env arguments go after a `--`. Everything before it is parsed by
 * wp-env's own option parser, which would otherwise swallow flags like
 * `--format=json` before they ever reach WP-CLI.
 * @return {{command: string, args: string[], cwd: string}} How to invoke WP-CLI
 */
const runner = () =>
	isWpEnv()
		? {
				command: 'pnpm',
				args: [ 'exec', 'wp-env', 'run', 'cli', 'wp', '--' ],
				cwd: PLUGIN_ROOT,
		  }
		: {
				command: 'docker-compose',
				args: [ 'run', '--rm', '--entrypoint', 'wp', 'wp-cli' ],
				cwd: requireEnv(
					'MONEI_E2E_WP_DIR',
					'It must be the docker-compose directory of the same site MONEI_E2E_BASE_URL points at.'
				),
		  };

/**
 * Run a WP-CLI command against the test site.
 *
 * Throws on a non zero exit so a broken fixture fails the test instead of
 * silently leaving the site in the wrong state.
 * @param {string[]} args - WP-CLI arguments
 * @return {string} Trimmed stdout
 */
const wpCli = ( args ) => {
	const { command, args: prefix, cwd } = runner();

	return execFileSync( command, [ ...prefix, ...args ], {
		cwd,
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'pipe' ],
		// A container that accepts the command and never answers would otherwise
		// hold the seed, or a `beforeAll` hook, with no upper bound. A wedged
		// Docker daemon is a real failure mode, not a hypothetical one.
		timeout: 120000,
	} ).trim();
};

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
 * Read the MONEI account id the store pays with.
 * @return {string} Account id
 */
const getAccountId = () => wpCli( [ 'option', 'get', 'monei_test_accountid' ] );

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
 * Read a gateway settings option.
 *
 * The gateway option row only exists once the settings screen has been saved, so
 * a fresh store has no row at all and `wp option get` exits non-zero. That is the
 * documented default state, not a failure.
 * @param {string} option - Gateway settings option name
 * @return {Object} Stored settings, or an empty object
 */
const readSettings = ( option ) => {
	try {
		return (
			JSON.parse(
				wpCli( [ 'option', 'get', option, '--format=json' ] )
			) || {}
		);
	} catch ( error ) {
		return {};
	}
};

/**
 * Merge keys into a gateway settings option.
 *
 * `option patch` refuses a key the option does not have yet, and several keys are
 * absent until a merchant saves the settings screen once, so the option is merged
 * in PHP instead.
 * @param {string} option - Gateway settings option name
 * @param {Object} values - Keys to merge in
 */
const mergeSettings = ( option, values ) =>
	wpCli( [
		'eval',
		`$s = (array) get_option( '${ option }', array() );` +
			`$s = array_merge( $s, json_decode( '${ JSON.stringify(
				values
			) }', true ) );` +
			`update_option( '${ option }', $s );`,
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
	const settings = readSettings( option );

	return {
		express_enabled: settings.express_enabled || 'no',
		express_locations: settings.express_locations || [],
	};
};

/**
 * Write express checkout settings into a MONEI gateway.
 * @param {string} option - Gateway settings option name
 * @param {Object} values - Keys to merge in
 */
const setExpressSettings = ( option, values ) =>
	mergeSettings( option, values );

/**
 * Read what a blocks payment integration registers with WordPress.
 *
 * `get_payment_method_script_handles()` is where an integration declares the
 * assets its React component needs. A browser can only see the effect of that
 * declaration, and any other enabled MONEI method produces the same effect by
 * enqueueing the shared SDK first, so the declaration itself is the only place
 * an integration that forgot it can be told apart from one that did not.
 * @param {string} className - Fully qualified blocks support class
 * @return {Object} `{ handles, deps, sdk }`
 */
const getBlocksScriptRegistration = ( className ) =>
	JSON.parse(
		wpCli( [
			'eval',
			`$support = \\Monei\\Core\\ContainerProvider::getContainer()->get( '${ className }' );` +
				'$handles = (array) $support->get_payment_method_script_handles();' +
				'$scripts = wp_scripts()->registered;' +
				'$deps = array();' +
				'foreach ( $handles as $handle ) {' +
				'if ( isset( $scripts[ $handle ] ) ) { $deps = array_merge( $deps, (array) $scripts[ $handle ]->deps ); }' +
				'}' +
				"echo json_encode( array( 'handles' => array_values( $handles ), 'deps' => array_values( array_unique( $deps ) ), 'sdk' => isset( $scripts['monei'] ) ? $scripts['monei']->src : '' ) );",
		] )
	);

/**
 * Read whether a gateway is enabled.
 *
 * An unsaved gateway has no option row, and WooCommerce treats that as off, so
 * the absent case answers `no` rather than throwing.
 * @param {string} option - Gateway settings option name
 * @return {string} `yes` or `no`
 */
const getGatewayEnabled = ( option ) => readSettings( option ).enabled || 'no';

/**
 * Turn a gateway on or off.
 * @param {string} option  - Gateway settings option name
 * @param {string} enabled - `yes` or `no`
 */
const setGatewayEnabled = ( option, enabled ) =>
	mergeSettings( option, { enabled } );

module.exports = {
	wpCli,
	getAccountId,
	getExpressSettings,
	setExpressSettings,
	getBlocksScriptRegistration,
	getGatewayEnabled,
	setGatewayEnabled,
	setCardFieldLayout,
	getCardFieldLayout,
	getCheckoutPageId,
	setCheckoutPageId,
	ensureCoupon,
	getOrderStatus,
};
