( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const paypalData = wc.wcSettings.getSetting( 'monei_paypal_data' );

	const paypalLabel = () => {
		return (
			<div className="monei-label-container">
				<span className="monei-text">
					{ __( paypalData.title, 'monei' ) }
				</span>
				{ paypalData?.logo && (
					<div className="monei-logo">
						<img src={ paypalData.logo } alt="" />
					</div>
				) }
			</div>
		);
	};

	const MoneiPayPalPaymentMethod = {
		name: 'monei_paypal',
		label: <div> {paypalLabel()} </div>,
		ariaLabel: __(paypalData.title, 'monei'),
		content: <div> {__(paypalData.description, 'monei')}</div>,
		edit: <div> {__(paypalData.title, 'monei')}</div>,
		canMakePayment: () => true,
		supports: paypalData.supports,
	};
	registerPaymentMethod(MoneiPayPalPaymentMethod );
} )();
