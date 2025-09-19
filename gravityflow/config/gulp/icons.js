module.exports = {
	common: {
		fontsPath: '../../../fonts',
		replaceName: /'gflow-icons-common' !important/g,
		replaceScss: /\$icomoon-font-family: "gflow-icons-common" !default;\n/g,
		varName: 'var(--t-font-family-common-icons) !important',
		zipName: 'gflow-icons-common',
	},
};
