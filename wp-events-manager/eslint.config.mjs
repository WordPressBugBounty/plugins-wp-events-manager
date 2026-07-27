export default [
	{
		ignores: [
			'assets/dist/**',
			'assets/src/js/datetimepicker/**',
			'assets/src/js/**/*.min.js'
		]
	},
	{
		files: [ 'assets/src/js/**/*.js' ],
		languageOptions: {
			ecmaVersion: 2020,
			sourceType: 'script',
			globals: {
				alert: 'readonly',
				clearInterval: 'readonly',
				document: 'readonly',
				Error: 'readonly',
				fetch: 'readonly',
				FormData: 'readonly',
				URLSearchParams: 'readonly',
				window: 'readonly'
			}
		},
		rules: {
			'no-restricted-globals': [ 'error', 'jQuery', '$' ],
			'no-undef': 'error',
			'no-unused-vars': [ 'error', { argsIgnorePattern: '^_' } ]
		}
	}
];
