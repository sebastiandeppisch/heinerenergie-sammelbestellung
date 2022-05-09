const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

const app = mix.ts('resources/js/app.js', 'public/js').vue();
//mix.sass('resources/sass/app.scss', 'public/css');

mix.version();

if (! mix.inProduction()) {
    mix.browserSync('localhost:8000');
    app.sourceMaps();
}