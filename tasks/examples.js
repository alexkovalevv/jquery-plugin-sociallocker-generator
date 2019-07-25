module.exports = function( grunt ) {

	var path = require('path');

    grunt.registerMultiTask( "examples", "Generate examples depending on configuration", function() {
	
        var options = this.options({
            wrap: null
        });

        var wrapTemplate = null;
        if ( options.wrap ) {
                wrapTemplate = grunt.file.read( options.wrap );
        }

        var dest = null;

        this.files.forEach(function(f) {

            var filename = path.basename(f.src);
            if ( filename.substring(0, 1) == '_' ) return true;

            var example = grunt.file.read(f.src);
            var result = null;

            dest = path.dirname(f.dest);

            if ( options.wrap && f.wrap ) {

                    var data = {};
                    var reg = /begin\:([a-z0-9]+)([^]*?)end\:(\1)/ig;

                    while ((myArray = reg.exec(example)) != null) {
                            data[myArray[1]] = myArray[2];
                    }

                    data.pkg = grunt.config('pkg');

                    result = grunt.template.process(wrapTemplate, {data: data});
            } else {
                    result = grunt.template.process(example);
            }

            grunt.file.write(f.dest, result);
        });
    });
};