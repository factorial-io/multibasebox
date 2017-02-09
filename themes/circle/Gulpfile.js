var gulp = require('gulp');
var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var browserify = require('browserify');
var watchify = require('watchify');
var babel = require('babelify');

function compile(watch) {
  var bundler = false
  if (watch) {
    bundler = watchify(browserify('./js/index.js', { debug: true }))
  } else {
    bundler = browserify('./js/index.js', { debug: false })
  }
  
  bundler.transform(babel, { presets: ['es2015'] })

  function rebundle() {
    bundler.bundle()
      .on('error', function(err) { console.error(err); this.emit('end'); })
      .pipe(source('build.js'))
      .pipe(buffer())
      .pipe(sourcemaps.init({ loadMaps: true }))
      .pipe(sourcemaps.write('./'))
      .pipe(gulp.dest('./js_dist'));
  }

  if (watch) {
    bundler.on('update', function() {
      console.log('-> bundling...');
      rebundle();
    });
  }

  rebundle();
}

function watch() {
  return compile(true);
};

gulp.task('build_js', function() { return compile(); });
gulp.task('watch_js', function() { return watch(); });

gulp.task('styles', function() {
    gulp.src('scss/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(postcss([ autoprefixer() ]))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./css/'));
});

//Watch task
gulp.task('default',function() {
  gulp.watch('scss/**/*.scss',['styles']);
	watch()
});

gulp.task('dist', ['styles', 'build_js'])