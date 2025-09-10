import { MoneiAppleGoogleContent, createAppleGoogleLabel } from './components/monei-apple-google-component';

( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const moneiData = wc.wcSettings.getSetting( 'monei_apple_google_data' );

	/**
	 * MONEI Apple/Google Payment Method
	 */
	const AppleGooglePaymentMethod = {
		name: 'monei_apple_google',
		paymentMethodId: 'monei_apple_google',
		label: createAppleGoogleLabel( moneiData ),
		ariaLabel: __( 'Apple/Google Pay Payment Gateway', 'monei' ),
		content: <MoneiAppleGoogleContent />,
		edit: <div>{ __( 'MONEI Payment Form (Edit Mode)', 'monei' ) }</div>,
		canMakePayment: () => true,
		supports: {
			features: [ 'products' ]
		}
	};

	registerPaymentMethod( AppleGooglePaymentMethod );
} )();