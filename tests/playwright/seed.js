#!/usr/bin/env node

/**
 * Puts the store the E2E suite expects onto whatever site WP-CLI points at.
 *
 * The suite used to assume a hand-built store: a specific product id, a
 * shortcode checkout page, a currency, a country, no shipping methods and a
 * MONEI key already saved. None of that exists on a fresh wp-env instance, and
 * an assumption nobody wrote down is one nobody can reproduce, so every one of
 * them is created here instead.
 *
 * Idempotent: running it again on a seeded site changes nothing.
 */

const { wpCli } = require( './utils/wp-cli' );
const { requireEnv } = require( './utils/env' );
const { writeFixtures } = require( './utils/fixtures' );

/**
 * The one call that answers "which MONEI account is this key for" without
 * already knowing the answer. The account id is not a secret — every checkout
 * page carries it — but it is account-specific, and deriving it from the key
 * keeps the whole setup down to a single secret.
 */
const ALLOWED_PAYMENT_METHODS_URL =
	'https://api.monei.com/v1/allowed-payment-methods';

const SKU = 'MONEI-E2E-TSHIRT';
const CLASSIC_CHECKOUT_SLUG = 'classic-checkout';

/**
 * Ask MONEI which account an API key belongs to.
 * @param {string} apiKey - MONEI test mode API key
 * @return {Promise<string>} Account id
 */
const fetchAccountId = async ( apiKey ) => {
	const response = await fetch( ALLOWED_PAYMENT_METHODS_URL, {
		headers: {
			Authorization: apiKey,
			// The API refuses requests without one when the caller is not an SDK.
			'User-Agent': 'MONEI-WooCommerce-E2E',
		},
	} );

	const body = await response.json();

	if ( ! response.ok || ! body.accountId ) {
		throw new Error(
			`MONEI_TEST_API_KEY was refused by ${ ALLOWED_PAYMENT_METHODS_URL }: ` +
				`${ body.message || response.status }`
		);
	}

	if ( body.livemode ) {
		throw new Error(
			'MONEI_TEST_API_KEY is a live mode key. The suite takes real ' +
				'payments, so it must only ever hold a test mode key.'
		);
	}

	return body.accountId;
};

/**
 * Run inline PHP inside the site.
 * @param {string} code - PHP to evaluate
 * @return {string} Trimmed stdout
 */
const php = ( code ) => wpCli( [ 'eval', code ] );

/**
 * Merge keys into a serialized option, creating the option when it is absent.
 *
 * `option patch update` refuses a key the option does not have yet, and the
 * gateway options do not exist until a merchant saves the settings screen once.
 * @param {string} option - Option name
 * @param {Object} values - Keys to merge in
 */
const mergeOption = ( option, values ) =>
	php(
		`$s = (array) get_option( '${ option }', array() );` +
			`update_option( '${ option }', array_merge( $s, json_decode( '${ JSON.stringify(
				values
			) }', true ) ) );`
	);

/**
 * Store settings the specs depend on.
 *
 * ⚠️ `woocommerce_default_country` and `woocommerce_currency` are load bearing:
 * the specs pay in EUR from a Madrid address, and a store in another currency
 * would move every amount the express endpoints verify.
 */
const STORE_OPTIONS = {
	woocommerce_currency: 'EUR',
	woocommerce_default_country: 'ES:M',
	woocommerce_store_country: 'ES',
	// A tax rate would put the cart total out of step with the fixed amounts the
	// express specs assert on.
	woocommerce_calc_taxes: 'no',
	woocommerce_enable_coupons: 'yes',
	woocommerce_enable_guest_checkout: 'yes',
	woocommerce_enable_checkout_login_reminder: 'no',
};

/**
 * Take every shipping method off the store.
 *
 * ⚠️ The suite has always assumed a store with no shipping methods, and never
 * said so. With none, `WC()->cart->needs_shipping()` is false, so the checkout
 * asks for no shipping address and express skips its shipping callbacks. Adding
 * one changes every total and every express flow, so the assumption is enforced
 * here rather than left to whoever built the site.
 */
const removeShippingMethods = () => {
	const remaining = php(
		"foreach ( WC_Shipping_Zones::get_zones() as $zone ) { WC_Shipping_Zones::delete_zone( $zone['zone_id'] ); }" +
			'$rest = new WC_Shipping_Zone( 0 );' +
			'foreach ( array_keys( $rest->get_shipping_methods( false ) ) as $instance_id ) { $rest->delete_shipping_method( $instance_id ); }' +
			"WC_Cache_Helper::get_transient_version( 'shipping', true );" +
			'echo wc_get_shipping_method_count( true );'
	);

	if ( '0' !== remaining ) {
		throw new Error(
			`The store still has ${ remaining } shipping methods after the seed removed them.`
		);
	}
};

/**
 * The product every spec puts in the cart.
 * @return {{id: string, path: string}} Product id and relative permalink
 */
const ensureProduct = () => {
	const [ id, path ] = php(
		`$id = wc_get_product_id_by_sku( '${ SKU }' );` +
			'if ( ! $id ) {' +
			'$product = new WC_Product_Simple();' +
			"$product->set_name( 'T-Shirt with Logo' );" +
			`$product->set_sku( '${ SKU }' );` +
			"$product->set_regular_price( '18.00' );" +
			"$product->set_status( 'publish' );" +
			"$product->set_catalog_visibility( 'visible' );" +
			'$id = $product->save();' +
			'}' +
			'echo $id . "\\n" . wp_make_link_relative( get_permalink( $id ) );'
	).split( '\n' );

	return { id: id.trim(), path: path.trim() };
};

/**
 * The shortcode checkout page the classic checkout spec drives.
 *
 * WooCommerce's own checkout page carries the Checkout block on a modern
 * install, so the classic form needs a page of its own.
 * @return {{id: string, path: string}} Page id and relative permalink
 */
const ensureClassicCheckoutPage = () => {
	const [ id, path ] = php(
		`$page = get_page_by_path( '${ CLASSIC_CHECKOUT_SLUG }' );` +
			'$id = $page ? $page->ID : wp_insert_post( array(' +
			"'post_title' => 'Classic checkout'," +
			`'post_name' => '${ CLASSIC_CHECKOUT_SLUG }',` +
			"'post_status' => 'publish'," +
			"'post_type' => 'page'," +
			"'post_content' => '[woocommerce_checkout]'," +
			') );' +
			'echo $id . "\\n" . wp_make_link_relative( get_permalink( $id ) );'
	).split( '\n' );

	return { id: id.trim(), path: path.trim() };
};

const main = async () => {
	const apiKey = requireEnv(
		'MONEI_TEST_API_KEY',
		'It must be the test mode API key of the MONEI account the suite pays with.'
	);

	const accountId = await fetchAccountId( apiKey );

	wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
	wpCli( [ 'rewrite', 'structure', '/%postname%/', '--hard' ] );

	Object.entries( STORE_OPTIONS ).forEach( ( [ name, value ] ) =>
		wpCli( [ 'option', 'update', name, value ] )
	);

	removeShippingMethods();

	const product = ensureProduct();
	const classicCheckout = ensureClassicCheckoutPage();

	wpCli( [ 'option', 'update', 'monei_test_apikey', apiKey ] );
	wpCli( [ 'option', 'update', 'monei_test_accountid', accountId ] );
	wpCli( [ 'option', 'update', 'monei_apikey_mode', 'test' ] );

	// `card_field_layout` is written by `wp option patch update`, which refuses a
	// key the option does not have yet, so the key has to exist before a spec
	// switches it.
	mergeOption( 'woocommerce_monei_settings', {
		enabled: 'yes',
		card_field_layout: 'single',
	} );
	mergeOption( 'woocommerce_monei_apple_google_settings', {
		enabled: 'yes',
	} );

	writeFixtures( {
		productId: product.id,
		productPath: product.path,
		classicCheckoutPageId: String( classicCheckout.id ),
		classicCheckoutPath: classicCheckout.path,
	} );

	process.stdout.write(
		`Seeded MONEI account ${ accountId }\n` +
			`  product ${ product.id } at ${ product.path }\n` +
			`  classic checkout page ${ classicCheckout.id } at ${ classicCheckout.path }\n`
	);
};

main().catch( ( error ) => {
	process.stderr.write( `${ error.message }\n` );
	process.exitCode = 1;
} );
