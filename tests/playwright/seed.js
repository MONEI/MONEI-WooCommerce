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
		// The seed is the first thing CI runs; an unanswered request here would
		// hang the job rather than fail it.
		signal: AbortSignal.timeout( 30000 ),
	} );

	// A gateway error page or a WAF block is not JSON, and letting the parser
	// throw would report a syntax error instead of the refusal that caused it.
	let body = {};

	try {
		body = await response.json();
	} catch ( parseError ) {
		throw new Error(
			`${ ALLOWED_PAYMENT_METHODS_URL } answered ${ response.status } with a ` +
				'body that is not JSON.'
		);
	}

	if ( ! response.ok || ! body.accountId ) {
		// Describe the key without printing it. A stored secret pasted with a
		// trailing newline is the usual cause of a 401 here, and it is invisible
		// unless the length is reported.
		const shape =
			`${ apiKey.length } chars, starts "${ apiKey.slice( 0, 8 ) }"` +
			( apiKey !== apiKey.trim() ? ', HAS SURROUNDING WHITESPACE' : '' );

		throw new Error(
			`MONEI_TEST_API_KEY was refused by ${ ALLOWED_PAYMENT_METHODS_URL }: ` +
				`${
					body.message || response.status
				}. The key is ${ shape }. ` +
				'A MONEI test key is 40 characters and starts "pk_test_".'
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
 * Move the store's order ids past every id the MONEI account has already paid.
 *
 * 🚨 Without this the suite fails on a fresh site with `The order "14" has already
 * been paid` — MONEI's own refusal, not WooCommerce's. MONEI keeps `orderId` unique
 * per account, the plugin sends the WooCommerce order id unchanged, and a fresh
 * wp-env starts its ids at 1. Those low ids are exactly the ones every earlier run
 * on the same test account has already paid for, so a new site collides with its own
 * history. A long-lived store never notices, which is why this only ever bites CI.
 *
 * The wall clock is the only thing machines that never talk to each other can both
 * read and never repeat, so the range starts at the current millisecond.
 *
 * Order ids come off the `wp_posts` sequence, and off the orders table as well on a
 * site running HPOS, so both are moved where they exist.
 * @return {number} First order id this run can use
 */
const reserveOrderIdRange = () => {
	const start = Date.now();

	const next = php(
		'global $wpdb;' +
			`$wpdb->query( "ALTER TABLE {$wpdb->posts} AUTO_INCREMENT = ${ start }" );` +
			"$orders = $wpdb->prefix . 'wc_orders';" +
			"if ( $orders === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders ) ) ) {" +
			`$wpdb->query( "ALTER TABLE {$orders} AUTO_INCREMENT = ${ start }" );` +
			'}' +
			'echo (int) $wpdb->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES' +
			" WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$wpdb->posts}'\" );"
	);

	if ( Number( next ) < start ) {
		throw new Error(
			`The store's next order id is ${ next }, not ${ start }: the seed could not ` +
				'move the sequence, so this run would pay with order ids the MONEI ' +
				'account has already been paid for and every payment would be refused.'
		);
	}

	return start;
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

	// The API call stays even when the id is supplied, because it is what proves
	// the key is real and, more importantly, that it is not a live mode key.
	const resolvedAccountId = await fetchAccountId( apiKey );
	const declaredAccountId = (
		process.env.MONEI_TEST_ACCOUNT_ID || ''
	).trim();

	if ( declaredAccountId && declaredAccountId !== resolvedAccountId ) {
		throw new Error(
			`MONEI_TEST_ACCOUNT_ID is ${ declaredAccountId } but MONEI_TEST_API_KEY ` +
				`belongs to ${ resolvedAccountId }. Paying with one account while ` +
				'configuring the store for another would make every result meaningless.'
		);
	}

	const accountId = declaredAccountId || resolvedAccountId;

	wpCli( [ 'plugin', 'activate', 'woocommerce' ] );
	wpCli( [ 'rewrite', 'structure', '/%postname%/', '--hard' ] );

	Object.entries( STORE_OPTIONS ).forEach( ( [ name, value ] ) =>
		wpCli( [ 'option', 'update', name, value ] )
	);

	removeShippingMethods();

	const product = ensureProduct();
	const classicCheckout = ensureClassicCheckoutPage();

	// After the fixtures, so the product and the page keep readable ids.
	const firstOrderId = reserveOrderIdRange();

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
			`  classic checkout page ${ classicCheckout.id } at ${ classicCheckout.path }\n` +
			`  orders start at ${ firstOrderId }\n`
	);
};

main().catch( ( error ) => {
	process.stderr.write( `${ error.message }\n` );

	// `execFileSync` puts only "Command failed: …" in the message and leaves
	// WP-CLI's actual explanation on stderr, so without this a CI failure says
	// that something broke without saying what.
	if ( error.stderr ) {
		process.stderr.write( `${ error.stderr }\n` );
	}

	process.exitCode = 1;
} );
