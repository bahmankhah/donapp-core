module.exports = {
	pluginData: {
		customProperties: {
			admin: [
				{ customProperties: require( '@gravityforms/theme/custom-properties/admin' ) },
			],
			theme: [
				{ customProperties: require( '@gravityforms/theme/custom-properties/theme' ) },
			],
		},
		customMedia: {
			admin: [
				{ customMedia: require( '@gravityforms/theme/custom-media/admin' ) },
			],
		},
		mixins: {
			admin: require( '@gravityforms/theme/mixins/admin' ),
			common: require( '@gravityforms/theme/mixins/common' ),
			theme: require( '@gravityforms/theme/mixins/theme' ),
		}
	},
};
