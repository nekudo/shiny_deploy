var gulp = require('gulp');
var concat = require('gulp-concat');
var minifyCSS = require('gulp-minify-css');
var uglifyJS = require('gulp-uglifyjs');
var rename = require('gulp-rename');

// Tasks
/*
gulp.task('css', function(){
    gulp.src('provider/assets/stylesheets/*.css')
        .pipe(minifyCSS())
        .pipe(concat('style.min.css'))
        .pipe(gulp.dest('public/assets/css/'))
});
*/
gulp.task('js', function(){
    gulp.src('www/js/src/*.js')
        .pipe(uglifyJS())
        .pipe(concat('project.min.js'))
        .pipe(gulp.dest('www/js/'))
});

// Watcher
gulp.task('watch', function() {
    //gulp.watch('www/css/src/*.css', ['css']);
    gulp.watch('www/js/src/*.js', ['js']);
});

// Default task
gulp.task('default', ['watch']);