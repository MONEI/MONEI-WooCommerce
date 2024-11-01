const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = () => {
	const configs = [
		{
			...defaultConfig,
			entry: {
				'checkout-cc': path.resolve(
					__dirname,
					'assets/js/checkout-cc.js'
				),
				'block-checkout-bizum': path.resolve(
					__dirname,
					'assets/js/block-checkout-bizum.js'
				),
				'bizum-shortcode-checkout': path.resolve(
					__dirname,
					'assets/js/bizum-shortcode-checkout.js'
				)
			},
			output: {
				path: path.resolve( __dirname, 'public/js/' ), // Output directory
				filename: '[name].min.js', // Use the entry name for the output file
			},
		},
		{
			...defaultConfig,
			entry: {
				'monei-blocks-checkout-cc': path.resolve(
					__dirname,
					'assets/css/monei-blocks-checkout.css'
				),
			},
			output: {
				path: path.resolve( __dirname, 'public/css/' ),
				filename: '[name].js',
			},
		},
		{
			...defaultConfig,
			entry: {
				'monei-admin': path.resolve(
					__dirname,
					'assets/css/monei-admin.css'
				),
			},
			output: {
				path: path.resolve( __dirname, 'public/css/' ),
				filename: '[name].js',
			},
		},
	];

	return configs;
};
