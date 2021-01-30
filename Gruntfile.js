/* eslint-env node, es6 */
module.exports = function ( grunt ) {
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-eslint' );

	grunt.initConfig( {
		eslint: {
			options: {
				cache: true
			},
			all: [
				'**/*.{js,json}',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		banana: {
			all: [
				'i18n/',
				'UserBoard/i18n/',
				'UserProfile/i18n/',
				'UserRelationship/i18n/',
				'UserStats/i18n/',
				'SystemGifts/i18n/',
				'UserActivity/i18n/',
				'UserGifts/i18n/',
				'UserWelcome/i18n/'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
