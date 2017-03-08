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
var gutil = require('gulp-util');
var cleanCSS = require('gulp-clean-css');
var uglify = require('gulp-uglify');
var gulpif = require('gulp-if');

function compile(watch, dist) {
  var bundler = false
  if (watch) {
    bundler = watchify(browserify('./js/index.js', { debug: true }))
  } else {
    bundler = browserify('./js/index.js', { debug: false })
  }
  
  bundler.transform(babel, { presets: ['es2015'] })

  function rebundle() {
    var bundle = bundler.bundle()
      .on('error', function(err) {
        if (err && err.codeFrame) {
            gutil.log(
            gutil.colors.red("Browserify error: "),
            gutil.colors.cyan(err.filename) + ` [${err.loc.line},${err.loc.column}]`, "\r\n" + err.message + "\r\n" + err.codeFrame)
        }
        else {
            gutil.log(err);
        }
        // console.error(err); 
        this.emit('end'); 
      })
      .pipe(source('build.js'))
      .pipe(buffer())
      
      .pipe(gulpif(!dist, sourcemaps.init({ loadMaps: true })))
      .pipe(gulpif(!dist, sourcemaps.write('./')))
      .pipe(gulpif(dist, uglify()))

      .pipe(gulp.dest('./js_dist'))
  }

  if (watch) {
    bundler.on('update', function() {
      console.log('-> bundling...');
      rebundle();
    });
  }

  rebundle();
}

function compileCSS(dist) {
  var bundle = gulp.src('scss/*.scss')
    .pipe(gulpif(!dist, sourcemaps.init()))
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss([ autoprefixer() ]))
    .pipe(gulpif(!dist, sourcemaps.write('.')))
    .pipe(gulpif(dist, cleanCSS()))

    .pipe(gulp.dest('./css/'));
}

function watch() {
  return compile(true);
};

gulp.task('build_js', function() { return compile(false, true); });
gulp.task('watch_js', function() { return watch(); });

gulp.task('styles', function() {
  return compileCSS(false);
});

gulp.task('dist_styles', function() {
  return compileCSS(true);
});

//Watch task
gulp.task('default',function() {
  gulp.watch('scss/**/*.scss',['styles']);
	watch()
});

gulp.task('dist', ['dist_styles', 'build_js'])