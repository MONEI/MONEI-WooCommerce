#!/usr/bin/env node

/**
 * Sort CHANGELOG.md versions by date (newest first)
 */

/* eslint-disable no-console */

const fs = require( 'fs' );
const path = require( 'path' );

const changelogPath = path.join( process.cwd(), 'CHANGELOG.md' );
const content = fs.readFileSync( changelogPath, 'utf8' );

// Split into header and versions
const lines = content.split( '\n' );
const headerLines = [];
const versionSections = [];

let currentVersion = null;
let currentLines = [];

for ( const line of lines ) {
	// Match version headers like "## 6.3.12 (2025-10-01)" or "## <small>6.3.12 (2025-10-01)</small>"
	const versionMatch = line.match(
		/^##\s+(?:<small>)?(\d+\.\d+\.\d+)\s+\(([^)]+)\)(?:<\/small>)?/
	);

	if ( versionMatch ) {
		// Save previous version if exists
		if ( currentVersion ) {
			versionSections.push( {
				version: currentVersion.version,
				date: currentVersion.date,
				header: currentVersion.header,
				lines: currentLines,
			} );
		}

		// Start new version
		currentVersion = {
			version: versionMatch[ 1 ],
			date: versionMatch[ 2 ],
			header: line,
		};
		currentLines = [];
	} else if ( currentVersion ) {
		// Add to current version body
		currentLines.push( line );
	} else {
		// Before first version (header)
		headerLines.push( line );
	}
}

// Save last version
if ( currentVersion ) {
	versionSections.push( {
		version: currentVersion.version,
		date: currentVersion.date,
		header: currentVersion.header,
		lines: currentLines,
	} );
}

// Sort versions by date (newest first)
versionSections.sort( ( a, b ) => {
	const dateA = new Date( a.date );
	const dateB = new Date( b.date );

	if ( dateB - dateA !== 0 ) {
		return dateB - dateA; // Newest first
	}

	// If same date, sort by version number (highest first)
	const partsA = a.version.split( '.' ).map( Number );
	const partsB = b.version.split( '.' ).map( Number );

	for ( let i = 0; i < 3; i++ ) {
		if ( partsB[ i ] !== partsA[ i ] ) {
			return partsB[ i ] - partsA[ i ];
		}
	}

	return 0;
} );

// Rebuild file
const output = [
	...headerLines,
	...versionSections.flatMap( ( section ) => [
		section.header,
		...section.lines,
	] ),
].join( '\n' );

fs.writeFileSync( changelogPath, output );

console.log( '✅ CHANGELOG.md sorted successfully!' );
console.log( `📦 Total versions: ${ versionSections.length }` );
console.log(
	`📅 Newest: ${ versionSections[ 0 ].version } (${ versionSections[ 0 ].date })`
);
console.log(
	`📅 Oldest: ${ versionSections[ versionSections.length - 1 ].version } (${
		versionSections[ versionSections.length - 1 ].date
	})`
);
