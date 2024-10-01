const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'checkout-cc': path.resolve( __dirname, 'assets/js/checkout-cc.js' ),
        'block-checkout-bizum': path.resolve( __dirname, 'assets/js/block-checkout-bizum.js' ),
        'monei-blocks-checkout-cc': path.resolve(__dirname, 'assets/css/monei-blocks-checkout.css'),
        'monei-admin': path.resolve(__dirname, 'assets/css/monei-admin.css'),
    },
    output: {
        path: path.resolve( __dirname, 'public/js/' ), // Output directory
        filename: '[name].min.js', // Use the entry name for the output file
    },
};
