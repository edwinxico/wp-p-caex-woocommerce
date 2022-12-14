const { src, dest, parallel } = require('gulp');
const clean = require('gulp-clean');
const stripdebug = require('gulp-strip-debug');
const uglify = require('gulp-uglify');
const less = require('gulp-less');
const minifyCSS = require('gulp-csso');
const minifyJS = require('gulp-minify');
const concat = require('gulp-concat');
const sourcemaps = require('gulp-sourcemaps');

/*
function client_css() {
  return src('./assets/less/custom.less')
    .pipe(less())
    .pipe(minifyCSS())
    .pipe(sourcemaps.write('./maps'))
    .pipe(dest('dist/assets/css'))
}
*/
function admin_css() {
  return src('./assets/less/custom-admin.less')
    .pipe(less())
    .pipe(minifyCSS())
    .pipe(sourcemaps.write('./maps'))
    .pipe(dest('dist/assets/css'))
}

function public_js() {
  return src('./assets/js/public/*.js', { sourcemaps: true })
    .pipe(concat('app.min.js'))
    .pipe(dest('dist/assets/js', { sourcemaps: true }))
}

function public_js_prod() {
  return src('./assets/js/public/*.js')
    .pipe( stripdebug() )
    .pipe(concat('app.js'))
    .pipe(minifyJS({
      ext: {
        min: '.min.js'
      }
    }))
    .pipe(dest('dist/assets/js'))
}

function admin_js() {
  return src('./assets/js/admin/*.js', { sourcemaps: true })
    .pipe(concat('admin.min.js'))
    .pipe(dest('dist/assets/js', { sourcemaps: true }))
}

function admin_js_prod() {
  return src('./assets/js/admin/*.js')
    .pipe( stripdebug() )
    .pipe(concat('admin.js'))
    .pipe(minifyJS({
      ext: {
        min: '.min.js'
      }
    }))
    .pipe(dest('dist/assets/js'))
}

function clean_dist() {
  return src(['dist/*'], {read:false})
  .pipe(clean());
}


var filesToMove = [
        './assets/images/*.*',
        './node_modules/cleave.js/dist/cleave.min.js',
        './node_modules/pdf-lib/dist/pdf-lib.min.js',
        './node_modules/downloadjs/download.min.js',
    ];

function move() {
  return src(filesToMove, { base: './' })
  .pipe(dest('dist'));
}

exports.clean_dist = clean_dist;
exports.move = move;
exports.admin_js = admin_js;
exports.admin_js_prod = admin_js_prod;

exports.public_js = public_js;
exports.public_js_prod = public_js_prod;
// exports.client_css = client_css;
exports.admin_css = admin_css;
// exports.css = parallel(client_css);
exports.clean = parallel(clean_dist);
exports.production = parallel(admin_js_prod, public_js_prod, admin_css, move);
exports.default = parallel(admin_js, public_js, admin_css, move);