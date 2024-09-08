const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        'checkout-cc': path.resolve( __dirname, 'assets/js/checkout-cc.js' ), // Point to your custom entry file
    },
    output: {
        path: path.resolve( __dirname, 'public/js/' ), // Output directory
        filename: '[name].min.js', // Use the entry name for the output file (checkout-cc.js)
    },
};
