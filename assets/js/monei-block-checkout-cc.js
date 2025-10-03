import {
	MoneiCCContent,
	CreditCardLabel,
} from './components/monei-cc-component';

( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const moneiData = wc.wcSettings.getSetting( 'monei_data' );

	/**
	 * Register MONEI Credit Card Payment Method
	 */
	const MoneiPaymentMethod = {
		name: 'monei',
		label: <CreditCardLabel { ...moneiData } />,
		ariaLabel: __( 'MONEI Payment Gateway', 'monei' ),
		content: <MoneiCCContent />,
		edit: <div>{ __( 'MONEI Payment Form (Edit Mode)', 'monei' ) }</div>,
		canMakePayment: () => true,
		supports: moneiData.supports || {
			features: [ 'products' ],
			savePaymentMethod: true,
		},
	};

	registerPaymentMethod( MoneiPaymentMethod );
} )();
