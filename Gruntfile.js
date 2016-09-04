module.exports = function( grunt ) {

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		uglify: {
			build: {
				files: [
					{
						expand: true,
						cwd: 'assets/js/',
						src: [ '*.js', '!*.min.js' ],
						dest: 'assets/js/',
						ext: '.min.js'
					}
				]
			}
		},
		pot: {
			options: {
				encoding: 'UTF-8',
				keywords: [
					'gettext',
					'__',
					'_e',
					'_n:1,2',
					'_x:1,2c',
					'_ex:1,2c',
					'_nx:4c,1,2',
					'esc_attr__',
					'esc_attr_e',
					'esc_attr_x:1,2c',
					'esc_html__',
					'esc_html_e',
					'esc_html_x:1,2c',
					'_n_noop:1,2',
					'_nx_noop:3c,1,2',
					'__ngettext_noop:1,2'
				],
				package_version: '',
				msgid_bugs_address: 'help@ninjaforms.com',
				comment_tag: 'translators:'
			},
			build: {
				options: {
					text_domain: 'ninja-forms-uploads',
					dest: 'languages/ninja-forms-uploads.pot',
					package_name: 'ninja-forms-uploads'
				},
				files: [
					{
						expand: true,
						src: [
							'**/*.php',
							'!vendor',
						    '!node_modules'
						]
					}
				]
			}
		},
		replace: {
			build: {
				src: [ 'languages/ninja-forms-uploads.pot' ],
				overwrite: true,
				replacements: [
					{
						from: 'SOME DESCRIPTIVE TITLE',
						to: 'Ninja Forms Uploads Pot File'
					},
					{
						from: 'YEAR THE PACKAGE\'S COPYRIGHT HOLDER',
						to: grunt.template.today('yyyy')
					},
					{
						from: 'LANGUAGE',
						to: 'English'
					},
					{
						from: 'FIRST AUTHOR',
						to: 'Ninja Forms'
					},
					{
						from: 'FULL NAME',
						to: 'Ninja Forms'
					},
					{
						from: 'EMAIL@ADDRESS',
						to: 'help@ninjaforms.com'
					},
					{
						from: 'YEAR-MO-DA HO:MI+ZONE',
						to  : grunt.template.today( 'yyyy-mm-dd HH:MM+Z' )
					},
					{
						from: 'YEAR',
						to  : grunt.template.today( 'yyyy' )
					}
				]
			}
		},
		watch: {
			js: {
				files: [
					'src/*/assets/js/*',
					'!src/*/assets/js/*.min.js'
				],
				tasks: [ 'uglify' ]
			}
		}
	} );

	require( 'load-grunt-tasks' )( grunt );

	grunt.registerTask( 'default', [
		'uglify',
		'pot',
		'replace'
	] );
};
