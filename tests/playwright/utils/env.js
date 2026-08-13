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
	// A secret pasted into a CI store almost always carries a trailing newline,
	// which turns a valid credential into a 401 that reads like a wrong key.
	const value = ( process.env[ name ] || '' ).trim();
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

/**
 * Whether the store is reachable from outside this machine over HTTPS.
 *
 * 3D Secure sends the shopper's browser to the issuer and back to the store, and
 * the challenge is framed over HTTPS. A store on `http://localhost` satisfies
 * neither half: the page is plain HTTP, and MONEI cannot reach the host at all.
 * The challenge then never renders, so a card journey that expects one waits for
 * a thank you page that can never arrive.
 *
 * wp-env serves exactly such a site, which is why the card specs run only
 * against a publicly reachable HTTPS store — an ngrok tunnel, staging, or any
 * real host.
 * @return {boolean} Whether 3DS can complete against this store
 */
const supportsThreeDs = () => baseUrl().startsWith( 'https://' );

/**
 * Reason shown when a spec is skipped for want of a public HTTPS store.
 */
const THREE_DS_SKIP_REASON =
	`3D Secure needs a publicly reachable HTTPS store; this run targets ${ baseUrl() }. ` +
	'Set MONEI_E2E_WP_DIR and MONEI_E2E_BASE_URL to a tunnelled or hosted site to run it.';

module.exports = {
	PLUGIN_ROOT,
	THREE_DS_SKIP_REASON,
	baseUrl,
	isWpEnv,
	requireEnv,
	supportsThreeDs,
	wpEnvUrl,
};
