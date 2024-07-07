(()=>{
	'use strict';
	const e=window.wp.element,t=window.wp.htmlEntities,r=window.wp.i18n,a=window.wc.wcBlocksRegistry,n=window.wc.blocksCheckout,o=window.wc.wcSettings,
	htmlToElem=(html)=>e.RawHTML({children:html}),
	c=()=>{const e=(0,o.getSetting)('monei_data',null);if(!e)throw new Error('MONEI initialization data is not available.');return e},
	moneiScript = c().scriptUrl,
	g=()=>{
		const moneiTestMode = ' === <strong>' + r.__( 'Test mode enabled', 'monei' ) + '</strong> === ';
		const moneiExternal = '<br><strong>' + r.__( 'You will be redirected for payment.', 'monei' ) + '</strong>';
		const moneiTestCard = '<br>' + r.__('Use 4444444444444422 as CC number, 12/34 as an expiration date and 123 as CVC code.', 'monei' );

		let moneiAdditionalContent = '';

		if ( 'yes' == c().test_mode ) {
			 moneiAdditionalContent = moneiTestMode;
		}
		if ( 'yes' == c().redirect ) {
			moneiAdditionalContent += moneiExternal;
		}

		if ( 'yes' == c().test_mode ) {
			moneiAdditionalContent += moneiTestCard;
		}
		
		return ( c()?.description || r.__('Pay with credit card.','monei') ) + moneiAdditionalContent;
	},
	k1=()=>{
		if ( 'yes' == c().hide_logo ) {
			return '';
		} else {
			return (0,e.createElement( 'img', { src: `${c().logo}`, alt: c().title } ));
		}
	},
	k2=()=>{
		if ( 'no' == c().tokenization ) {
			return htmlToElem('');// htmlToElem('<h1>TODO: hide saved cards...</h1>');
		} else {
			return htmlToElem('<label> <input name="wc-monei-new-payment-method" type="checkbox" id="a" value="new"> ' + (0,r.__)('Save payment information to my account for future purchases.','monei') + '</label>');
		}
	},
	k3=()=>{
		// Same HTML code from protected function render_monei_form().
		return htmlToElem( '<style>#payment-form{padding-bottom:15px}#card-input{border:1px solid transparent;border-radius:4px;background-color:#fff;box-shadow:0 1px 3px 0 #e6ebf1;height:38px;box-sizing:border-box;-webkit-transition:box-shadow 150ms ease;transition:box-shadow 150ms ease;max-width:350px}#card-input.is-focused{box-shadow:0 1px 3px 0 #cfd7df}</style><fieldset id="wc-monei-cc-form" class="wc-credit-card-form wc-payment-form" style="background:0 0"><div id="payment-form"><div class="card-field"><div id="card-input"></div><div id="monei-card-error"></div></div></div></fieldset>' );
	},
    i=t=> {let {id:a,value:o="",onChange:c}=t;return(0,e.createElement)(n.ValidatedTextInput, {id:a,type:"hidden",value:o,onChange:c})},	
	l=r=>{

		let{eventRegistration:a,emitResponse:n}=r;
	
		const
			{onPaymentSetup:o}=a,
			[s,setS,l]=(0,e.useState)('');
			
		(e.useEffect( ( ) => 
			{
				const validation = a.onCheckoutValidation( ( async ) => {

					const createToken = async () => {
						const moneiToken = await window.wc_monei_block_form.create_token();
						setS( moneiToken );
					}

					if ( ! jQuery('#monei_payment_token_created').length ) {
						createToken();
					}

				} );

				const unsubscribe = a.onPaymentSetup( ( async ) => {

					if ( ! jQuery('#monei_payment_token_created').length ) {

						//console.log( 'Missing token, stop this and wait for token creation which triggers click' );
						throw new Error('but not an actual error. MONEI: just stop while waiting for remote token creation....');
					}

					const 
						moneiToken = jQuery('#monei_payment_token_created').val(),
						moneiDataIsValid= !! moneiToken.length;

					if ( moneiDataIsValid ) {
						
						return {
							type: n.responseTypes.SUCCESS,
							meta: {
								paymentMethodData: { monei_payment_token: moneiToken },
							},
						};
					}

					return {
						type: n.responseTypes.ERROR,
						message: 'MONEI: There was an error with token creation.',
					};

				} );

				// Unsubscribes when this component is unmounted.
				return () => {
					unsubscribe();
				};

			}, 
			[ n.responseTypes.ERROR, n.responseTypes.SUCCESS, a.onPaymentSetup, s ] 
		));
			

		return(0,e.useEffect)((()=>o((()=>{

			return {type:n.responseTypes.SUCCESS,meta:{paymentMethodData:[l]}}}
			))),
				[n.responseTypes.SUCCESS,o]),

				( 'yes' == c().redirect ) ? 
					(0,e.createElement)
						(e.Fragment,null,
							(0,e.createElement( e.RawHTML, null, g() )),
							(0,e.createElement)(k2),
						)

				:
					(0,e.createElement)
						(e.Fragment,null,
							(0,e.createElement( e.RawHTML, null, g() )),
							(0,e.createElement)(k3),
							(0,e.createElement)(i, { id:'monei_payment_token',value:s,onChange:l }),
							(0,e.createElement)(k2),
							e.useEffect(
								() => { 

									const script = document.createElement("script");
	
									script.src = moneiScript;
									script.async = true;
									script.onload = () => { 
										window.wc_monei_block_form.init_checkout_monei();
									}

									document.body.appendChild(script);

								}, [] )  // <-- empty array means 'run once'
						)
};

var x;

(0,a.registerPaymentMethod)({
	name:'monei',
	label:(0,e.createElement)((t=>{const{PaymentMethodLabel:a}=t.components,n=c().title?c().title:(0,r.__)('MONEI','monei');return(e.Fragment,null,(0,e.createElement)(a,{text:n}),(0,e.createElement)(k1) )}),null),
	ariaLabel:(0,r.__)('MONEI Payment Gateway','monei'),
	canMakePayment:()=>!0,
	content:(0,e.createElement)(l,null),
	edit:(0,e.createElement)(l,null),
	supports:{features:null!==(x=c()?.supports)&&void 0!==x?x:[]}
	
})})();
