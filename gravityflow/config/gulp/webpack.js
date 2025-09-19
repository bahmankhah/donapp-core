const { resolve } = require( 'path' );
const paths = require( './paths' );

module.exports = {
	alias: {
		common: resolve( __dirname, '../../assets/js/src/common' ),
		templates: resolve( __dirname, '../../assets/js/src/templates' ),
		utils: resolve( __dirname, '../../assets/js/src/utils' ),
	},
	overrides: {
		entry: {
			admin: {
				'editor.blocks': [
					`${ paths.js_src }/blocks/index.js`,
				],
			}
		},
		externals: {
			admin: {
				'gflow-config': 'gflow_config',
				'react': 'React',
				'react-dom': 'ReactDOM',
			},
			theme: {
				'gflow-config': 'gflow_config',
				'react': 'React',
				'react-dom': 'ReactDOM',
			},
			release: {
				admin: {
					'gflow-config': 'gflow_config',
					'react': 'React',
					'react-dom': 'ReactDOM',
				},
				theme: {
					'gflow-config': 'gflow_config',
					'react': 'React',
					'react-dom': 'ReactDOM',
				},
			},
		},
		output: {
			uniqueName: 'gravityflow',
		},
	},
};
