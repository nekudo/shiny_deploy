var gulp = require('gulp');
var concat = require('gulp-concat');
var minifyCSS = require('gulp-minify-css');
var uglify = require('gulp-uglifyjs');

// CSS Tasks
gulp.task('project_css', function() {
    gulp.src('www/css/src/*.css')
        .pipe(minifyCSS())
        .pipe(concat('project.min.css'))
        .pipe(gulp.dest('www/css/'))
});

gulp.task('vendor_css', function() {
    gulp.src([
        'www/css/vendor/bootstrap.min.css',
        'www/css/vendor/font-awesome.min.css',
        'www/css/vendor/AdminLTE.min.css',
        'www/css/vendor/skin-blue.min.css',
        'www/fonts/source-code-pro.css',
        'www/fonts/source-sans-pro.css'
    ])
        .pipe(concat('vendor.min.css'))
        .pipe(gulp.dest('www/css/'))
});

// JS Tasks
gulp.task('project_js', function() {
    gulp.src([
        'www/js/app/app.js',
        'www/js/app/**/*.js'
    ])
        .pipe(concat('project.js'))
        .pipe(uglify())
        .pipe(gulp.dest('www/js/'))
});

gulp.task('vendor_js', function() {
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
gulp.task('watch', function() {
    gulp.watch('www/css/src/*.css', ['project_css']);
    gulp.watch(['www/js/app.js', 'www/js/app/**/*.js'], ['project_js']);
});

// Default task
gulp.task('default', ['watch']);