const path = require( 'path' );

require( 'dotenv' ).config( {
	path: path.resolve( __dirname, '../.env' ),
	quiet: true,
} );

/**
 * Plugin root, which is also the directory holding `.wp-env.json`.
 */
const PLUGIN_ROOT = path.resolve( __dirname, '../../..' );

/**
 * URL wp-env serves the development site on. `WP_ENV_PORT` is wp-env's own
 * variable, so overriding the port stays a single setting.
 * @return {string} Site URL
 */
const wpEnvUrl = () => `http://localhost:${ process.env.WP_ENV_PORT || 8888 }`;

/**
 * Whether the suite drives its own wp-env instance.
 *
 * wp-env is the default because it is the only setup that works everywhere,
 * including a CI runner with no site on it. Pointing `MONEI_E2E_WP_DIR` at a
 * docker-compose stack switches to that site instead, which is what a developer
 * with an existing local store keeps using.
 * @return {boolean} Whether wp-env serves the site
 */
const isWpEnv = () => ! process.env.MONEI_E2E_WP_DIR;

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

/**
 * URL the browser drives and pays on.
 *
 * Under wp-env the two halves cannot diverge: WP-CLI runs inside the same
 * instance the URL points at, so the URL is derived rather than configured. A
 * `MONEI_E2E_BASE_URL` naming any other site is exactly the split the explicit
 * pairing above exists to prevent, so it is refused instead of honoured.
 * @return {string} Base URL
 */
const baseUrl = () => {
	if ( ! isWpEnv() ) {
		return requireEnv(
			'MONEI_E2E_BASE_URL',
			'It must be the public URL of the site the suite pays on.'
		);
	}

	const configured = ( process.env.MONEI_E2E_BASE_URL || '' ).replace(
		/\/$/,
		''
	);

	if ( configured && configured !== wpEnvUrl() ) {
		throw new Error(
			`MONEI_E2E_BASE_URL is ${ configured }, but WP-CLI would run against ` +
				`the wp-env instance at ${ wpEnvUrl() }. Set MONEI_E2E_WP_DIR to ` +
				'the docker-compose directory of that site, or unset ' +
				'MONEI_E2E_BASE_URL to use wp-env. See tests/playwright/README.md.'
		);
	}

	return wpEnvUrl();
};

module.exports = { PLUGIN_ROOT, baseUrl, isWpEnv, requireEnv, wpEnvUrl };
