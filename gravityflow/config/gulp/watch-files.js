const paths = require( './paths' );

module.exports = [
	{
		files: [
			`${ paths.css_src }/admin/**/*.pcss`,
		],
		tasks: [ 'postcss:adminCss' ],
	},
	{
		files: [
			`${ paths.css_src }/common/**/*.pcss`,
		],
		tasks: [ 'postcss:adminCss', 'postcss:themeCss' ],
	},
	{
		files: [
			`${ paths.css_src }/theme/**/*.pcss`,
		],
		tasks: [ 'postcss:themeCss' ],
	},
];
