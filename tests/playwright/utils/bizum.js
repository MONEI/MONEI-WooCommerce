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

module.exports = { isBizumOfferedHere };
