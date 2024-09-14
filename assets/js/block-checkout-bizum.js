( function() {
    const { registerPaymentMethod } = wc.wcBlocksRegistry;
    const { __ } = wp.i18n;
    const bizumData = wc.wcSettings.getSetting('monei_bizum_data');

const MoneiBizumPaymentMethod = {
    name: 'monei_bizum',
    label: <div> {__(bizumData.title, 'monei')}</div>,
    ariaLabel: __(bizumData.title, 'monei'),
    content: <div></div>,
    edit: <div> {__(bizumData.title, 'monei')}</div>,
    canMakePayment: () => true,
    supports: bizumData.supports,
};
    registerPaymentMethod(MoneiBizumPaymentMethod );
} )();