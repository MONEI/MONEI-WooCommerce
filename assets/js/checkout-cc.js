const moneiSettings = window.wc.wcSettings.getSetting( 'monei_data', {} );
const moneiLabel    = window.wp.htmlEntities.decodeEntities( moneiSettings.title ) || window.wp.i18n.__( 'Monei', 'monei' );
	
let moneiButton = window.wp.i18n.__( 'Place order', 'monei' );
if ( 'yes' == moneiSettings.redirect ) {
	moneiButton = window.wp.i18n.__( 'Proceed to MONEI', 'monei' );
}

const moneiContent  = () => {

	const moneiExternal = window.wp.i18n.__( ' | You will be redirected for payment.', 'monei' );
	const moneiTestMode = window.wp.i18n.__( ' | You are in test mode | Use 4444444444444422 as CC number, 12/34 as an expiration date and 123 as CVC code', 'monei' );

	let moneiAdditionalContent = '';

	if ( 'yes' == moneiSettings.test_mode ) {
		 moneiAdditionalContent = moneiTestMode;
	}

	if ( 'yes' == moneiSettings.redirect ) {
		moneiAdditionalContent += moneiExternal;
	}

	return window.wp.htmlEntities.decodeEntities( moneiSettings.description + moneiAdditionalContent || moneiAdditionalContent );
};

const MoneiCheckoutCC = {
	name: 'monei',
	label: React.createElement('img', 
					{
						src: `${moneiSettings.logo}`,
						alt: moneiLabel,
					} ),
	content: Object( window.wp.element.createElement )( moneiContent, null ),
	edit: Object( window.wp.element.createElement )( moneiContent , null ),
	canMakePayment: () => true,
	placeOrderButtonLabel: moneiButton,
	ariaLabel: moneiLabel,
	supports: {
		features: moneiSettings.supports,
	},
};

window.wc.wcBlocksRegistry.registerPaymentMethod( MoneiCheckoutCC );
