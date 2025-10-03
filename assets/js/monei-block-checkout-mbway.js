( function () {
	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { __ } = wp.i18n;
	const mbwayData = wc.wcSettings.getSetting( 'monei_mbway_data' );

	const mbwayLabel = () => {
		return (
			<div className="monei-label-container">
				<span className="monei-text">
					{ __( mbwayData.title, 'monei' ) }
				</span>
				{ mbwayData?.logo && (
					<div className="monei-logo">
						<img src={ mbwayData.logo } alt="" />
					</div>
				) }
			</div>
		);
	};

	const MoneiMbwayPaymentMethod = {
		name: 'monei_mbway',
		label: <div> { mbwayLabel() } </div>,
		ariaLabel: __( mbwayData.title, 'monei' ),
		content: <div> { __( mbwayData.description, 'monei' ) }</div>,
		edit: <div> { __( mbwayData.title, 'monei' ) }</div>,
		canMakePayment: ( { billingData } ) => {
			return billingData.country === 'PT';
		},
		supports: mbwayData.supports,
	};
	registerPaymentMethod( MoneiMbwayPaymentMethod );
} )();
