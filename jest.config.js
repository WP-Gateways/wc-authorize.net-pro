module.exports = {
	preset: '@wordpress/jest-preset-default',
	testEnvironment: 'jsdom',
	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
	setupFilesAfterEnv: [ '<rootDir>/tests/js/setup.js' ],
	// Babel config for Jest only. A project babel.config.js would also replace the Babel preset
	// wp-scripts uses for the production build, so the same preset is passed to babel-jest here.
	transform: {
		'\.[jt]sx?$': [
			'babel-jest',
			{
				configFile: false,
				babelrc: false,
				presets: [ '@wordpress/babel-preset-default' ],
			},
		],
	},
	moduleNameMapper: {
		'\.(scss|css)$': '<rootDir>/tests/js/__mocks__/style.js',
		'^@woocommerce/settings$': '<rootDir>/tests/js/__mocks__/wc-settings.js',
		'^@woocommerce/blocks-registry$': '<rootDir>/tests/js/__mocks__/blocks-registry.js',
	},
	collectCoverageFrom: [ 'src/**/*.js' ],
};
