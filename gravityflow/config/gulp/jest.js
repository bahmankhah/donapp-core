const esModules = [ '@gravityforms' ].join( '|' );

module.exports = {
	setupFiles: [
		'<rootDir>/tests/js/setup/global.js',
	],
	setupFilesAfterEnv: [
		'<rootDir>/tests/js/setup/gflow-config.setup.js',
	],
	roots: [
		'<rootDir>/tests/js',
	],
	transformIgnorePatterns: [ `/node_modules/(?!${esModules})` ],
}
