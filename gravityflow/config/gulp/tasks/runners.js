module.exports = [
	{
		name: 'lint',
		tasks: [
			'shell:eslint',
			'stylelint:admin',
			'stylelint:common',
			'stylelint:theme',
		],
	},
	{
		name: 'components:admin',
		tasks: [
			'postcss:adminComponentsCss',
			'replace:adminComponentsWrapperPrefix',
		],
	},
	{
		name: 'icons:common',
		tasks: [
			'clean:commonIconsStart',
			'decompress:commonIcons',
			'copy:commonIconsFonts',
			'copy:commonIconsStyles',
			'copy:commonIconsVariables',
			'replace:commonIconsStyle',
			'replace:commonIconsVariables',
			'header:commonIconsStyle',
			'header:commonIconsVariables',
			'footer:commonIconsVariables',
			'clean:commonIconsEnd',
			'postcss:adminCss',
			'postcss:themeCss',
			'postcss:adminComponentsCss',
			'replace:adminComponentsWrapperPrefix',
		],
	},
	{
		name: 'dist',
		tasks: [
			[
				'clean:js', 'postcss:adminCss', 'postcss:themeCss', 'postcss:adminComponentsCss',
			],
			[
				'replace:adminComponentsWrapperPrefix',
			],
			[
				'shell:scriptsThemeDev', 'shell:scriptsAdminDev',
			],
		],
	},
	{
		name: 'release',
		tasks: [
			[
				'clean:js', 'postcss:adminCss', 'postcss:themeCss', 'postcss:adminComponentsCss',
			],
			[
				'replace:adminComponentsWrapperPrefix',
			],
			[
				'shell:scriptsThemeDevRelease', 'shell:scriptsAdminDevRelease',
			],
			[
				'shell:scriptsThemeProdRelease', 'shell:scriptsAdminProdRelease',
			],
		],
	},
];
