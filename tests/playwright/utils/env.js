const path = require( 'path' );

require( 'dotenv' ).config( {
	path: path.resolve( __dirname, '../.env' ),
	quiet: true,
} );

/**
 * Read a required E2E setting.
 *
 * The suite drives a browser against one site and mutates settings on another
 * through WP-CLI. Defaulting either target would let a misconfigured run take
 * real test payments on one site while reconfiguring a different one, so both
 * are explicit or the run stops here.
 * @param {string} name - Environment variable name
 * @param {string} hint - What the value must point at
 * @return {string} The value
 */
const requireEnv = ( name, hint ) => {
	const value = process.env[ name ];
	if ( ! value ) {
		throw new Error(
			`${ name } is not set. ${ hint }\n` +
				'Copy tests/playwright/.env.example to tests/playwright/.env ' +
				'and fill both values in. See tests/playwright/README.md.'
		);
	}
	return value;
};

module.exports = { requireEnv };
