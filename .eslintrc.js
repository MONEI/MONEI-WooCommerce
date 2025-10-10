module.exports = {
	env: {
		browser: true,
		es6: true,
	},
	parserOptions: {
		ecmaVersion: 2020,
		sourceType: 'module',
		ecmaFeatures: {
			jsx: true,
		},
	},
	plugins: [ 'react-hooks' ],
	rules: {
		// Critical rules - only ban console.log, allow warn/error
		'no-console': [ 'error', { allow: [ 'warn', 'error' ] } ],

		// React Hooks rules
		'react-hooks/rules-of-hooks': 'error',
		'react-hooks/exhaustive-deps': 'warn',
	},
};
