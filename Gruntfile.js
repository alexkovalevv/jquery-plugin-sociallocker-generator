'use strict';

module.exports = function(grunt) {

	var banner = '/*!\n' +
		' * <%= pkg.title %> - v<%= pkg.version %>, <%= grunt.template.today("yyyy-mm-dd") %> \n' +
		' * for jQuery: https://sociallocker.ru \n' +
		' * \n' +
		' * <%= pkg.copyright %> \n' +
		' * Support: https://sociallocker.ru/create-ticket/ \n' +
		'*/\n';

	grunt.loadTasks("tasks");

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		obfuscator: {
			options: {
				compact: true,
				controlFlowFlattening: true,
				controlFlowFlatteningThreshold: 1,
				debugProtection: true,
				debugProtectionInterval: true,
				disableConsoleOutput: true,
				rotateStringArray: true,
				selfDefending: true,
				stringArray: true,
				stringArrayEncoding: 'rc4',
				stringArrayThreshold: 1,
				unicodeEscapeSequence: false
			}
		},

		concat: {
			options: {
				separator: ';\n',
				banner: banner + '\n'
			}
		},

		build_generator: {
			targets: {}
		}
	});

	grunt.registerMultiTask("build_generator", "Build generator", function() {

		var build = grunt.option('build') || 'premium';
		var lang = grunt.option('lang') || 'rus';

		/*grunt.config.set('obfuscator.task1', {
		 files: [
		 {
		 "temp/jsObfuscate/assets/js/autoloader.js": "src/assets/js/autoloader.js"
		 }
		 ]
		 });*/

		grunt.config.set('preprocess', {
			options: {
				context: {
					lang: lang,
					facebook_default_lang: 'rus',
					twitter_default_lang: 'rus',
					google_default_lang: 'rus',
					license: 0,
					build: build
				}
			},

			js: {
				files: [
					{
						"temp/preprocess/index.php": "src/index.php",
						"temp/preprocess/assets/js/wizard.js": "src/assets/js/wizard.js"
					}
				]
			}
			/*css: {
			 files: [
			 {
			 "temp/preprocess/index.php": "src/index.php",
			 "temp/preprocess/assets/js/wizard.js": "src/assets/js/wizard.js"
			 }
			 ]
			 }*/
		});

		grunt.config.set('copy', {
			js: {
				files: [
					{
						expand: true,
						cwd: 'src/assets/',
						src: ['**/*', '!js/*', '!css/'],
						dest: 'dist/assets/'
					},
					{
						expand: true,
						cwd: 'libs/js/',
						src: ['**/*'],
						dest: 'dist/assets/js/libs'
					},
					{
						"dist/assets/js/wizard.js": "temp/preprocess/assets/js/wizard.js",
						"dist/assets/js/md5.min.js": "src/assets/js/md5.min.js"
					}
				]
			},
			php: {
				files: [
					{
						expand: true,
						cwd: 'libs/php/',
						src: ['**/*'],
						dest: 'dist/libs/'
					},
					{
						expand: true,
						cwd: 'src/includes/',
						src: ['*'],
						dest: 'dist/includes/'
					},
					{
						"dist/index.php": "temp/preprocess/index.php"
					}
				]
			}
		});

		grunt.config.set('uglify', {
			options: {
				preserveComments: 'some'
			},
			js: {
				files: {
					"dist/assets/js/wizard.min.js": "temp/preprocess/assets/js/wizard.js",
					"dist/assets/js/autoload.min.js": "src/assets/js/autoload.js",
					"dist/assets/js/autoload.1.1.0.min.js": "src/assets/js/autoload.1.1.0.js",
					"dist/assets/js/autoload.1.1.1.min.js": "src/assets/js/autoload.1.1.1.js"
				}
			}
		});

		grunt.config.set('cssmin', {
			target: {
				files: [
					{
						expand: true,
						cwd: 'src/assets/css',
						src: ['*.css'],
						dest: 'dist/assets/css',
						ext: '.min.css'
					}
				]
			}
		});

		grunt.config.set('clean', {
			before: [
				'dist/*'
			],
			after: ['temp']
		});

		grunt.task.run(['clean:before', 'preprocess', 'uglify', 'cssmin', 'copy', 'clean:after']);
	});

	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-obfuscator');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-onpress-preprocess');

};
