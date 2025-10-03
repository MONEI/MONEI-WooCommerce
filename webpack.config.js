const path = require( 'path' );
const fs = require( 'fs' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

// Helper function to dynamically generate entries for a specific file type
const getEntries = ( dir, extension ) => {
	const entries = {};
	fs.readdirSync( dir ).forEach( ( file ) => {
		if ( path.extname( file ) === extension ) {
			const name = path.parse( file ).name; // Get file name without extension
			entries[ name ] = path.resolve( dir, file );
		}
	} );
	return entries;
};

const configs = [
	{
		...defaultConfig,
		entry: getEntries( path.resolve( __dirname, 'assets/js' ), '.js' ),
		output: {
			path: path.resolve( __dirname, 'public/js/' ),
			filename: '[name].min.js',
		},
	},
	{
		...defaultConfig,
		entry: getEntries( path.resolve( __dirname, 'assets/css' ), '.css' ),
		output: {
			path: path.resolve( __dirname, 'public/css/' ),
			filename: '[name].js',
		},
	},
];

module.exports = configs;
