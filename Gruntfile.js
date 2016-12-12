module.exports = function( grunt ) {
    grunt.initConfig( {
        pkg: grunt.file.readJSON( "package.json" ),

        phpcs: {
            plugin: {
                src: [ "./*.php", "./includes/*.php" ]
            },
            options: {
                bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
                standard: "phpcs.ruleset.xml"
            }
        }
    } );

	grunt.loadNpmTasks( "grunt-phpcs" );

    // Default task(s).
    grunt.registerTask( "default", [ "phpcs" ] );
};
