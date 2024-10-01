( function() {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    const { __ } = wp.i18n;
    const bizumData = wc.wcSettings.getSetting('monei_bizum_data');
    const bizumLabel = () => {
        return (
            <div className="monei-label-container">
                {bizumData?.logo && (
                    <div className="monei-logo">
                        <img src={bizumData.logo} alt="" />
                    </div>
                )}
                <div>{__(bizumData.title, 'monei')}</div>
            </div>
        );
    }



    const MoneiBizumPaymentMethod = {
        name: 'monei_bizum',
        label: <div> {bizumLabel()} </div>,
        ariaLabel: __(bizumData.title, 'monei'),
        content: <div></div>,
        edit: <div> {__(bizumData.title, 'monei')}</div>,
        canMakePayment: ({billingData}) => {
            return billingData.country === 'ES';
        },
        supports: bizumData.supports,
    };
    registerPaymentMethod(MoneiBizumPaymentMethod );
} )();