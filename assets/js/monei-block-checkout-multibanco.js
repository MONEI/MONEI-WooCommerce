( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const multibancoData = wc.wcSettings.getSetting( 'monei_multibanco_data' );

	const multibancoLabel = () => {
		return (
			<div className="monei-label-container">
				<span className="monei-text">
					{ __( multibancoData.title, 'monei' ) }
				</span>
				{ multibancoData?.logo && (
					<div className="monei-logo">
						<img src={ multibancoData.logo } alt="" />
					</div>
				) }
			</div>
		);
	};

	const MoneiMultibancoPaymentMethod = {
		name: 'monei_multibanco',
		label: multibancoLabel(),
		ariaLabel: __( multibancoData.title, 'monei' ),
		content: <div> { __( multibancoData.description, 'monei' ) }</div>,
		edit: <div> { __( multibancoData.title, 'monei' ) }</div>,
		canMakePayment: ( { billingData } ) => {
			return billingData.country === 'PT';
		},
		supports: multibancoData.supports,
	};
	registerPaymentMethod( MoneiMultibancoPaymentMethod );
} )();
