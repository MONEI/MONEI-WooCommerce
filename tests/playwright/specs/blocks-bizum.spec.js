const { test, expect } = require( '@playwright/test' );
const { addProductToCart, fillBlocksBilling } = require( '../utils/checkout' );
const {
	getAccountId,
	getBlocksScriptRegistration,
	getExpressSettings,
	getGatewayEnabled,
	setExpressSettings,
	setGatewayEnabled,
} = require( '../utils/wp-cli' );

/**
 * Bizum on the block checkout, with no other MONEI method to lean on.
 *
 * `monei-block-checkout-bizum.js` calls `monei.Bizum()` and
 * `monei.confirmPayment()`, so `MoneiBizumBlocksSupport` has to bring the SDK in
 * itself. Every other MONEI method also enqueues it, so a store offering Bizum
 * alone is the only configuration where that can be observed at all.
 *
 * ⚠️ Two tests, because the browser alone cannot see the whole of it. The classic
 * Bizum gateway enqueues the SDK from `wp_enqueue_scripts` on anything
 * `is_checkout()` answers true for, which includes a block checkout page, so the
 * SDK reaches the page whenever Bizum is enabled at all — with or without the
 * blocks integration declaring it. The declaration is therefore checked where it
 * lives, in what the integration registers, and the browser test covers what the
 * shopper gets. Together they say the block checkout does not depend on the
 * classic gateway's enqueue, which is what makes the classic hook safe to scope
 * to classic pages later.
 *
 * ⚠️ Express checkout is switched off as well: it enqueues the SDK from the
 * wallet gateways regardless of whether those gateways are enabled.
 *
 * ⚠️ Only the registration test runs everywhere. MONEI offers Bizum by caller IP
 * address, so the browser test runs only where it is actually on offer. See
 * `isBizumOfferedHere()`.
 *
 * ⚠️ No payment is taken. Bizum authorises out of band, in the shopper's bank app
 * on a phone that answers a real phone number, so nothing past the mounted
 * component can be automated.
 */

const BIZUM_OPTION = 'woocommerce_monei_bizum_settings';

const BIZUM_BLOCKS_CLASS = 'Monei\\Gateways\\Blocks\\MoneiBizumBlocksSupport';

const BIZUM_SCRIPT_HANDLE = 'wc-monei-bizum-blocks-integration';

/**
 * Every other MONEI gateway that would enqueue the SDK on a checkout page.
 */
const OTHER_GATEWAY_OPTIONS = [
	'woocommerce_monei_settings',
	'woocommerce_monei_apple_google_settings',
	'woocommerce_monei_paypal_settings',
];

/**
 * The gateways that carry express checkout settings.
 */
const EXPRESS_OPTIONS = [
	'woocommerce_monei_apple_google_settings',
	'woocommerce_monei_paypal_settings',
];

const MOUNT_TIMEOUT = 60000;

/**
 * The call `monei.Bizum()` makes before it decides whether to render itself.
 */
const CLIENT_PAYMENT_METHODS_URL =
	'https://api.monei.com/v1/client-payment-methods';

/**
 * Whether MONEI offers Bizum to the machine this suite runs on.
 *
 * ⚠️ Bizum is a Spanish scheme, and MONEI filters an account's client payment
 * methods by the caller's own IP address, not by the store's country. From
 * outside Spain the account is told it has no Bizum at all, and the component
 * then declines to mount — correctly, and with nothing wrong on the store. The
 * WooCommerce payment method itself is unaffected, because availability there is
 * decided in PHP, which is why the checkout still offers Bizum and only the
 * mounted component goes missing.
 *
 * Asking MONEI the same question the component asks is the only gate that stays
 * honest: it skips exactly where Bizum is genuinely unavailable, and still fails
 * where Bizum is offered and the component does not appear.
 * @param {string} accountId - MONEI account the store pays with
 * @return {Promise<boolean>} Whether Bizum is on offer here
 */
const isBizumOfferedHere = async ( accountId ) => {
	const response = await fetch(
		`${ CLIENT_PAYMENT_METHODS_URL }?accountId=${ accountId }`,
		// An unanswered request would otherwise hold the hook with no upper bound.
		{ signal: AbortSignal.timeout( 30000 ) }
	);

	if ( ! response.ok ) {
		throw new Error(
			`${ CLIENT_PAYMENT_METHODS_URL } answered ${ response.status } for ` +
				`account ${ accountId }, so this spec cannot tell whether Bizum is ` +
				'offered here.'
		);
	}

	const body = await response.json();

	return ( body.paymentMethods || [] ).includes( 'bizum' );
};

const previousEnabled = {};
const previousExpress = {};
let bizumOffered = false;

test.describe( 'Block checkout, Bizum only', () => {
	test.beforeAll( async () => {
		bizumOffered = await isBizumOfferedHere( getAccountId() );

		for ( const option of OTHER_GATEWAY_OPTIONS ) {
			previousEnabled[ option ] = getGatewayEnabled( option );
			setGatewayEnabled( option, 'no' );
		}

		for ( const option of EXPRESS_OPTIONS ) {
			previousExpress[ option ] = getExpressSettings( option );
			setExpressSettings( option, {
				express_enabled: 'no',
				express_locations: [],
			} );
		}

		previousEnabled[ BIZUM_OPTION ] = getGatewayEnabled( BIZUM_OPTION );
		setGatewayEnabled( BIZUM_OPTION, 'yes' );
	} );

	test.afterAll( () => {
		// Runs after a failed test too, and a half-finished `beforeAll` records
		// only some keys, so the store never keeps a value this spec invented.
		for ( const [ option, enabled ] of Object.entries( previousEnabled ) ) {
			setGatewayEnabled( option, enabled );
		}

		for ( const [ option, settings ] of Object.entries(
			previousExpress
		) ) {
			setExpressSettings( option, settings );
		}
	} );

	test( 'declares the MONEI SDK on its own blocks script', () => {
		const registration = getBlocksScriptRegistration( BIZUM_BLOCKS_CLASS );

		expect( registration.handles ).toContain( BIZUM_SCRIPT_HANDLE );

		// Registered, so the handle resolves to the SDK rather than to nothing.
		expect(
			registration.sdk,
			'the blocks integration registered the MONEI SDK'
		).toContain( 'js.monei.com' );

		// And depended on, so WordPress loads it for this script alone instead of
		// leaving it to whichever other MONEI method happens to be enabled.
		expect(
			registration.deps,
			'the blocks script depends on the MONEI SDK'
		).toContain( 'monei' );
	} );

	test( 'renders the Bizum component on a Bizum only checkout', async ( {
		page,
	} ) => {
		test.skip(
			! bizumOffered,
			'MONEI does not offer Bizum to this machine. It is a Spanish scheme ' +
				"and the account's client payment methods are filtered by the " +
				'caller IP address, so the component has nothing to mount. Run the ' +
				'suite from Spain to cover it.'
		);

		const consoleErrors = [];

		page.on(
			'console',
			( message ) =>
				message.type() === 'error' &&
				consoleErrors.push( message.text() )
		);
		page.on( 'pageerror', ( error ) =>
			consoleErrors.push( String( error ) )
		);

		await addProductToCart( page );
		await page.goto( '/checkout/', { waitUntil: 'domcontentloaded' } );

		// Bizum only offers itself to a Spanish billing address, so the address
		// has to be in before the payment method list can contain it.
		await fillBlocksBilling( page );

		const bizum = page.locator(
			'#radio-control-wc-payment-method-options-monei_bizum'
		);
		await expect(
			bizum,
			'Bizum is offered on the block checkout'
		).toBeVisible( { timeout: MOUNT_TIMEOUT } );
		await bizum.check();

		await expect
			.poll( () => page.evaluate( () => typeof window.monei ), {
				timeout: MOUNT_TIMEOUT,
			} )
			.not.toBe( 'undefined' );

		// The SDK reaching the page is only half of it: the component has to have
		// mounted, which it does by rendering the Bizum button into an iframe
		// inside the container the integration renders.
		await expect(
			page.locator( '#bizum-container iframe' ),
			'the Bizum component mounted'
		).toBeVisible( { timeout: MOUNT_TIMEOUT } );

		// The component logs instead of throwing when the SDK is missing, so
		// without this a silent no-op could still pass everything above.
		expect(
			consoleErrors.filter( ( text ) =>
				/ReferenceError|monei is not defined|MONEI SDK is not available/i.test(
					text
				)
			),
			'no missing-SDK errors were logged'
		).toEqual( [] );
	} );
} );
