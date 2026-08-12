const jestUnitConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...jestUnitConfig,
	testEnvironment: 'jsdom',
	testPathIgnorePatterns: [
		'/node_modules/',
		'<rootDir>/vendor/',
		'<rootDir>/public/',
	],
};
