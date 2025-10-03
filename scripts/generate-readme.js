#!/usr/bin/env node

/**
 * Custom WordPress readme.txt generator
 *
 * Takes CHANGELOG.md and generates readme.txt and README.md
 * Shows the LATEST N versions (not the oldest N like the buggy npm package)
 */

const fs = require( 'fs' );
const path = require( 'path' );

// Configuration
const CONFIG = {
	changelogFile: 'CHANGELOG.md',
	templateFile: '.readme-template',
	outputTxt: 'readme.txt',
	outputMd: 'README.md',
	packageFile: 'package.json',
	mainFile: 'woocommerce-gateway-monei.php',
	changelogLimit: parseInt( process.argv[ 2 ] ) || 10, // Default to 10 versions
};

/**
 * Parse CHANGELOG.md and extract version entries
 * @param changelogContent
 */
function parseChangelog( changelogContent ) {
	const versions = [];
	const lines = changelogContent.split( '\n' );

	let currentVersion = null;
	let currentBody = [];

	for ( const line of lines ) {
		// Match version headers like "## 6.3.12 (2025-10-01)" or "## <small>6.3.12 (2025-10-01)</small>"
		const versionMatch = line.match(
			/^##\s+(?:<small>)?(\d+\.\d+\.\d+)\s+\(([^)]+)\)(?:<\/small>)?/
		);

		if ( versionMatch ) {
			// Save previous version if exists
			if ( currentVersion ) {
				versions.push( {
					version: currentVersion.version,
					date: currentVersion.date,
					body: currentBody.join( '\n' ).trim(),
				} );
			}

			// Start new version
			currentVersion = {
				version: versionMatch[ 1 ],
				date: versionMatch[ 2 ],
			};
			currentBody = [];
		} else if ( currentVersion && line.trim() ) {
			// Add to current version body
			currentBody.push( line );
		}
	}

	// Save last version
	if ( currentVersion ) {
		versions.push( {
			version: currentVersion.version,
			date: currentVersion.date,
			body: currentBody.join( '\n' ).trim(),
		} );
	}

	return versions;
}

/**
 * Format versions for WordPress readme.txt format
 * @param versions
 * @param limit
 */
function formatForReadme( versions, limit ) {
	// CHANGELOG.md has oldest first, newest last - so reverse to get newest first
	const reversed = [ ...versions ].reverse();
	const limited = reversed.slice( 0, limit );

	const formatted = limited.map( ( version, index ) => {
		const header = `= v${ version.version } - ${ version.date } =`;
		const body = version.body
			.split( '\n' )
			.filter( ( line ) => line.trim() && ! line.match( /^##/ ) ) // Remove headers
			.join( '\n' );

		return index === 0
			? `${ header }\n${ body }`
			: `\n\n${ header }\n${ body }`;
	} );

	return formatted.join( '' );
}

/**
 * Read package.json version
 */
function getPackageVersion() {
	const packagePath = path.join( process.cwd(), CONFIG.packageFile );
	const packageData = JSON.parse( fs.readFileSync( packagePath, 'utf8' ) );
	return packageData.version;
}

/**
 * Read main plugin file metadata
 */
function getPluginMetadata() {
	const mainPath = path.join( process.cwd(), CONFIG.mainFile );
	const content = fs.readFileSync( mainPath, 'utf8' );

	const metadata = {};

	// Extract metadata from plugin header comments
	const patterns = {
		name: /Plugin Name:\s*(.+)/,
		uri: /Plugin URI:\s*(.+)/,
		description: /Description:\s*(.+)/,
		version: /Version:\s*(\d+\.\d+\.\d+)/,
		author: /Author:\s*(.+)/,
		authorUri: /Author URI:\s*(.+)/,
		license: /License:\s*(.+)/,
		licenseUri: /License URI:\s*(.+)/,
		textDomain: /Text Domain:\s*(.+)/,
		requiresAtLeast: /Requires at least:\s*(.+)/,
		testedUpTo: /Tested up to:\s*(.+)/,
		requiresPHP: /Requires PHP:\s*(.+)/,
		wcRequiresAtLeast: /WC requires at least:\s*(.+)/,
		wcTestedUpTo: /WC tested up to:\s*(.+)/,
	};

	for ( const [ key, pattern ] of Object.entries( patterns ) ) {
		const match = content.match( pattern );
		if ( match ) {
			metadata[ key ] = match[ 1 ].trim();
		}
	}

	return metadata;
}

/**
 * Generate readme.txt (WordPress format)
 * @param template
 * @param changelog
 * @param metadata
 * @param version
 */
function generateReadmeTxt( template, changelog, metadata, version ) {
	let readme = template;

	// Replace version
	readme = readme.replace( /{{__PLUGIN_VERSION__}}/g, version );

	// Replace changelog
	readme = readme.replace( /{{__PLUGIN_CHANGELOG__}}/g, changelog );

	// Replace metadata if exists
	if ( metadata.requiresAtLeast ) {
		readme = readme.replace(
			/Requires at least: .+/g,
			`Requires at least: ${ metadata.requiresAtLeast }`
		);
	}
	if ( metadata.testedUpTo ) {
		readme = readme.replace(
			/Tested up to: .+/g,
			`Tested up to: ${ metadata.testedUpTo }`
		);
	}
	if ( metadata.requiresPHP ) {
		readme = readme.replace(
			/Requires PHP: .+/g,
			`Requires PHP: ${ metadata.requiresPHP }`
		);
	}
	if ( metadata.wcRequiresAtLeast ) {
		readme = readme.replace(
			/WC requires at least: .+/g,
			`WC requires at least: ${ metadata.wcRequiresAtLeast }`
		);
	}
	if ( metadata.wcTestedUpTo ) {
		readme = readme.replace(
			/WC tested up to: .+/g,
			`WC tested up to: ${ metadata.wcTestedUpTo }`
		);
	}

	return readme;
}

/**
 * Generate README.md (GitHub format)
 * @param readmeTxt
 */
function generateReadmeMd( readmeTxt ) {
	let readme = readmeTxt;

	// Convert WordPress readme.txt format to Markdown
	// Headers: === Title === -> # Title #
	readme = readme.replace( /^===\s*(.+?)\s*===/gm, '# $1 #' );

	// Subheaders: == Section == -> ## Section ##
	readme = readme.replace( /^==\s*(.+?)\s*==/gm, '## $1 ##' );

	// Changelog versions: = v6.3.12 - 2025-10-01 = -> ### v6.3.12 - 2025-10-01 ###
	readme = readme.replace(
		/^=\s+(v\d+\.\d+\.\d+\s+-\s+\d{4}-\d{2}-\d{2})\s+=$/gm,
		'### $1 ###'
	);

	return readme;
}

/**
 * Main function
 */
function main() {
	try {
		console.log( '🚀 Generating WordPress readme files...\n' );

		// Read files
		const changelogPath = path.join( process.cwd(), CONFIG.changelogFile );
		const templatePath = path.join( process.cwd(), CONFIG.templateFile );

		if ( ! fs.existsSync( changelogPath ) ) {
			throw new Error( `CHANGELOG.md not found at ${ changelogPath }` );
		}

		if ( ! fs.existsSync( templatePath ) ) {
			throw new Error(
				`.readme-template not found at ${ templatePath }`
			);
		}

		const changelogContent = fs.readFileSync( changelogPath, 'utf8' );
		const template = fs.readFileSync( templatePath, 'utf8' );

		// Get version and metadata
		const version = getPackageVersion();
		const metadata = getPluginMetadata();

		console.log( `📦 Version: ${ version }` );
		console.log(
			`📝 Changelog limit: ${ CONFIG.changelogLimit } versions\n`
		);

		// Parse and format changelog
		const versions = parseChangelog( changelogContent );
		console.log( `✅ Found ${ versions.length } versions in CHANGELOG.md` );

		const formattedChangelog = formatForReadme(
			versions,
			CONFIG.changelogLimit
		);

		// Generate readme files
		const readmeTxt = generateReadmeTxt(
			template,
			formattedChangelog,
			metadata,
			version
		);
		const readmeMd = generateReadmeMd( readmeTxt );

		// Write files
		const txtPath = path.join( process.cwd(), CONFIG.outputTxt );
		const mdPath = path.join( process.cwd(), CONFIG.outputMd );

		fs.writeFileSync( txtPath, readmeTxt );
		fs.writeFileSync( mdPath, readmeMd );

		console.log( `✅ Generated ${ CONFIG.outputTxt }` );
		console.log( `✅ Generated ${ CONFIG.outputMd }` );
		console.log( '\n✨ Done!' );
	} catch ( error ) {
		console.error( '\n❌ Error:', error.message );
		process.exit( 1 );
	}
}

// Run
main();
