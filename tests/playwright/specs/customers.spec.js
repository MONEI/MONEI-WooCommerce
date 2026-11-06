const { test, expect } = require( '@playwright/test' );
const {
	CARDS,
	SHOPPER,
	addProductToCart,
	completeThreeDsChallengeIfShown,
	expectOrderReceived,
	fillBlocksBilling,
	fillCard,
	fillCardholderName,
	gotoCheckout,
	loginAsShopper,
} = require( '../utils/checkout' );
const {
	getCardFieldLayout,
	getOrderMeta,
	setCardFieldLayout,
	wpCli,
} = require( '../utils/wp-cli' );
const { THREE_DS_SKIP_REASON, supportsThreeDs } = require( '../utils/env' );

const LAYOUT = 'single';

/**
 * PHP that finds the seeded shopper, the MONEI customer mapping and a test mode
 * SDK client. Every snippet below starts with it.
 */
const SHOPPER_CONTEXT =
	`$user = get_user_by( 'login', '${ SHOPPER.username }' );` +
	// The key the plugin writes for the seeded account in test mode, so a
	// mapping left by another account or mode is never read or deleted.
	"$key = '_monei_customer_' . get_option( 'monei_test_accountid' ) . '_test';" +
	'$mapped = (string) get_user_meta( $user->ID, $key, true );' +
	"$client = \\Monei\\Core\\ContainerProvider::getContainer()->get( \\Monei\\Services\\sdk\\MoneiSdkClientFactory::class )->get_client_for_mode( 'test' );";

/**
 * What the store and MONEI hold for the shopper, and the customer of one payment.
 * @param {string} paymentId - MONEI payment id
 * @return {{mapped: string, paymentCustomerId: string, customerEmail: string}} State
 */
const customerState = ( paymentId ) =>
	JSON.parse(
		wpCli( [
			'eval',
			SHOPPER_CONTEXT +
				`$payment = $client->payments->get( '${ paymentId }' );` +
				"$email = '';" +
				'if ( $mapped ) { try { $email = (string) $client->customers->get( $mapped )->getEmail(); } catch ( \\Monei\\ApiException $e ) { $email = "HTTP " . $e->getCode(); } }' +
				"echo json_encode( array( 'mapped' => $mapped, 'paymentCustomerId' => (string) $payment->getCustomerId(), 'customerEmail' => $email ) );",
		] )
	);

/**
 * Delete the shopper's MONEI customer at MONEI only. The store keeps the
 * mapping, which is the state a merchant creates by deleting the customer in the
 * MONEI dashboard.
 * @return {string} The deleted customer id
 */
const deleteCustomerAtMoneiOnly = () =>
	wpCli( [
		'eval',
		SHOPPER_CONTEXT +
			'$client->customers->delete( $mapped ); echo $mapped;',
	] );

/**
 * Start from a shopper with no MONEI customer and no saved card. A saved card
 * preselects itself and hides the new card form.
 */
const resetShopper = () =>
	wpCli( [
		'eval',
		SHOPPER_CONTEXT +
			'if ( $mapped ) { try { $client->customers->delete( $mapped ); } catch ( \\Monei\\ApiException $e ) {} }' +
			'delete_user_meta( $user->ID, $key );' +
			'foreach ( WC_Payment_Tokens::get_customer_tokens( $user->ID ) as $t ) { WC_Payment_Tokens::delete( $t->get_id() ); }',
	] );

/**
 * Pay for the test product with a new card as the logged in shopper.
 * @param {import('@playwright/test').Page} page - Page under test
 * @return {Promise<string>} MONEI payment id of the order
 */
const payWithNewCard = async ( page ) => {
	await addProductToCart( page );
	await gotoCheckout( page, '/checkout/', LAYOUT );

	// A shopper with an address from an earlier order sees it collapsed into a
	// card, which hides the fields `fillBlocksBilling` fills.
	const editAddress = page.getByRole( 'button', {
		name: /edit billing address/i,
	} );
	if ( await editAddress.isVisible() ) {
		await editAddress.click();
	}
	await fillBlocksBilling( page );
	await fillCardholderName( page );
	await fillCard( page, LAYOUT, CARDS.visaFrictionless );

	await page.getByRole( 'button', { name: /place order/i } ).click();
	await completeThreeDsChallengeIfShown( page );

	const orderId = await expectOrderReceived( page );
	const paymentId = getOrderMeta( orderId, '_payment_order_number_monei' );
	expect( paymentId, 'the order carries its MONEI payment id' ).toBeTruthy();
	return paymentId;
};

let previousLayout;

test.describe( 'MONEI customers, logged in card payments', () => {
	// Every case pays with a card, and this account challenges the test cards.
	test.skip( ! supportsThreeDs(), THREE_DS_SKIP_REASON );

	test.describe.configure( { mode: 'serial' } );

	test.beforeAll( () => {
		previousLayout = getCardFieldLayout();
		setCardFieldLayout( LAYOUT );
	} );

	test.beforeEach( () => resetShopper() );

	test.afterAll( () => {
		resetShopper();
		// A failed beforeAll leaves this unset, and writing an empty layout
		// back would take the setting off the site.
		if ( previousLayout ) {
			setCardFieldLayout( previousLayout );
		}
	} );

	test( 'links the payment to a MONEI customer made from the WordPress account', async ( {
		page,
	} ) => {
		await loginAsShopper( page );
		const paymentId = await payWithNewCard( page );

		const state = customerState( paymentId );
		expect( state.mapped, 'the store saved a MONEI customer' ).toBeTruthy();
		expect( state.paymentCustomerId ).toBe( state.mapped );
		// The account email, not the order billing email: the customer is the
		// WordPress user, whatever address one order was billed to.
		expect( state.customerEmail ).toBe( SHOPPER.email );
	} );

	test( 'pays without a customer MONEI no longer knows, then links a new one', async ( {
		page,
	} ) => {
		// Three card payments, each with a possible 3DS round trip.
		test.slow();
		await loginAsShopper( page );
		await payWithNewCard( page );
		const deleted = deleteCustomerAtMoneiOnly();
		expect( deleted, 'the first payment linked a customer' ).toBeTruthy();

		// MONEI refuses the payment for the deleted customer. The store must
		// retry once without it, with the same one-time card token, and the
		// shopper must not see the refusal.
		const retried = customerState( await payWithNewCard( page ) );
		expect( retried.paymentCustomerId ).toBe( '' );
		expect( retried.mapped, 'the stale mapping was removed' ).toBe( '' );

		const relinked = customerState( await payWithNewCard( page ) );
		expect(
			relinked.mapped,
			'the next payment made a new customer'
		).toBeTruthy();
		expect( relinked.mapped ).not.toBe( deleted );
		expect( relinked.paymentCustomerId ).toBe( relinked.mapped );
	} );
} );
