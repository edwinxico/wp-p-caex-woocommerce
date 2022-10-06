const { src, dest, parallel } = require('gulp');
const clean = require('gulp-clean');
const stripdebug = require('gulp-strip-debug');
const uglify = require('gulp-uglify');
const less = require('gulp-less');
const minifyCSS = require('gulp-csso');
const concat = require('gulp-concat');

/*
function client_css() {
  return src('./assets/less/custom.less')
    .pipe(less())
    .pipe(minifyCSS())
    .pipe(sourcemaps.write('./maps'))
    .pipe(dest('dist/assets/css'))
}
function admin_css() {
  return src('./assets/less/custom-admin.less')
    .pipe(less())
    .pipe(minifyCSS())
    .pipe(sourcemaps.write('./maps'))
    .pipe(dest('dist/css'))
}
*/

function admin_js() {
  return src('./assets/js/admin/*.js', { sourcemaps: true })
    .pipe(concat('admin.min.js'))
    .pipe(dest('dist/assets/js', { sourcemaps: true }))
}

function admin_js_prod() {
  return src('./assets/js/admin/*.js', { sourcemaps: true })
    .pipe( stripdebug() )
    .pipe(concat('app.min.js'))
    .pipe(dest('dist/assets/js', { sourcemaps: true }))
}

function clean_dist() {
  return src(['dist/*'], {read:false})
  .pipe(clean());
}


var filesToMove = [
        './assets/images/*.*',
        './node_modules/cleave.js/dist/cleave.min.js',
    ];

function move() {
  return src(filesToMove, { base: './' })
  .pipe(dest('dist'));
}

exports.clean_dist = clean_dist;
exports.move = move;
exports.admin_js = admin_js;
exports.admin_js_prod = admin_js_prod;
// exports.client_css = client_css;
// exports.admin_css = admin_css;
// exports.css = parallel(client_css);
exports.clean = parallel(clean_dist);
exports.production = parallel(admin_js_prod, move);
exports.default = parallel(admin_js, move);