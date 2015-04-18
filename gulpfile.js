var gulp = require('gulp');
var concat = require('gulp-concat');
/*
var minifyCSS = require('gulp-minify-css');
var uglifyJS = require('gulp-uglifyjs');
var rename = require('gulp-rename');
*/

// Tasks
/*
gulp.task('css', function(){
    gulp.src('provider/assets/stylesheets/*.css')
        .pipe(minifyCSS())
        .pipe(concat('style.min.css'))
        .pipe(gulp.dest('public/assets/css/'))
});
*/
gulp.task('project_js', function(){
    gulp.src([
        'www/js/app/app.js',
        'www/js/app/**/*.js'
    ])
        .pipe(concat('project.js'))
        .pipe(gulp.dest('www/js/'))
});
gulp.task('vendor_js', function(){
    gulp.src([
        'www/js/vendor/angular.min.js',
        'www/js/vendor/angular-route.min.js',
        'www/js/vendor/jquery.min.js',
        'www/js/vendor/jquery.nanoscroller.min.js',
        'www/js/vendor/bootstrap.min.js',
        'www/js/vendor/template.min.js',
        'www/js/vendor/autobahn.min.js'
    ])
        .pipe(concat('vendor.min.js'))
        .pipe(gulp.dest('www/js/'))
});

// Watcher
gulp.task('watch_project_js', function() {
    //gulp.watch('www/css/src/*.css', ['css']);
    gulp.watch(['www/js/app.js', 'www/js/app/**/*.js'], ['project_js']);
});

// Default task
gulp.task('default', ['watch_project_js']);