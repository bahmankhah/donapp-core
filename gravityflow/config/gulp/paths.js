const { resolve } = require( 'path' );

module.exports = {
	css_dist: resolve( __dirname, '../../assets/css/dist' ),
	css_src: resolve( __dirname, '../../assets/css/src' ),
	dev: resolve( __dirname, '../../assets/dev' ),
	fonts: resolve( __dirname, '../../fonts' ),
	images: resolve( __dirname, '../../images' ),
	js_dist: resolve( __dirname, '../../assets/js/dist' ),
	js_src: resolve( __dirname, '../../assets/js/src' ),
	legacy_css: resolve( __dirname, '../../legacy/css' ),
	npm: resolve( __dirname, '../../node_modules' ),
	npm_packages: resolve( __dirname, '../../packages/npm' ),
	postcss_assets_base_url: resolve( __dirname, '../../../' ),
	reports: resolve( __dirname, '../../reports/webpack-%s.html' ),
	root: resolve( __dirname, '../../' ),
};
