var gulp = require('gulp');
var config = require('../config.js');
var utils = require('../utils.js');
var debugMode = false;

gulp.task('copy-lightbox-images', function(){
    return gulp.src([config.paths.src + 'lightbox/images/**/*'])
        .pipe(gulp.dest( config.paths.build + 'lightbox/images'));
});

gulp.task('build-lightbox-mobile-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'lightbox/styles/main-mobile.scss'
    ], 'lightbox-mobile', 'lightbox/css/', debugMode);
});

gulp.task('build-lightbox-mobile-js', function(){

    return utils.buildJsGroup([
        config.paths.src + 'lightbox/js/jquery.validator.mobile.js'
    ], 'lightbox-mobile', 'lightbox/js', debugMode);
});

gulp.task('build-lightbox-ie6-css', function(){
    return utils.buildCssGroup([
        config.paths.src + 'lightbox/styles/main-ie6.scss'
    ], 'lightbox-ie6', 'lightbox/css/', debugMode)
});

gulp.task('build-lightbox-css', ['build-lightbox-mobile-css', 'build-lightbox-ie6-css'], function(){
    return utils.buildCssGroup([
        config.paths.src + 'lightbox/styles/main.scss'
    ], 'lightbox', 'lightbox/css/', debugMode)
});

gulp.task('build-lightbox-js', ['build-lightbox-mobile-js'], function(){
    var lightboxGroup = [
        config.paths.src + 'lightbox/js/jquery.lightbox.js'
    ];

    var lightboxIE6Group = [
        config.paths.src + 'lightbox/js/jquery.lightbox.ie6.js'
    ];
    utils.buildJsGroup(lightboxIE6Group, 'lightboxIe6', 'lightbox/js', debugMode);
    return utils.buildJsGroup(lightboxGroup, 'lightbox', 'lightbox/js', debugMode);
});

gulp.task('watch-lightbox-js', function() {
    debugMode = true;
    return gulp.watch(config.paths.src + 'lightbox/**/*.js', ['build-lightbox-js']);
});

gulp.task('watch-lightbox-css', function() {
    debugMode = true;
    gulp.watch(config.paths.src + 'lightbox/**/*.scss', ['build-lightbox-css']);
});

gulp.task('build-lightbox', ['copy-lightbox-images', 'build-lightbox-css'], function(){
    debugMode = false;
    return gulp.start('build-lightbox-js');
});