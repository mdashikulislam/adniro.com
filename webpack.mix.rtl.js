const mix = require('laravel-mix');
const rtlcss = require('rtlcss');


// Init. Base directories
const tmpCssBaseDir = 'public/assets/resources/css';

// Global Laravel-Mix Options
mix.options({processCssUrls: false});

// POSTCSS Plugins' Options
const postCssOptions = [
	rtlcss()
];

/* Generate CSS's RTL versions */
mix.postCss(`${tmpCssBaseDir}/auth/bootstrap.css`, `${tmpCssBaseDir}/auth/bootstrap.rtl.css`, postCssOptions);
mix.postCss(`${tmpCssBaseDir}/front/bootstrap.css`, `${tmpCssBaseDir}/front/bootstrap.rtl.css`, postCssOptions);
mix.postCss(`${tmpCssBaseDir}/admin/bootstrap.css`, `${tmpCssBaseDir}/admin/bootstrap.rtl.css`, postCssOptions);
mix.postCss(`${tmpCssBaseDir}/auth/auth.css`, `${tmpCssBaseDir}/auth/auth.rtl.css`, postCssOptions);
mix.postCss(`${tmpCssBaseDir}/front/app.css`, `${tmpCssBaseDir}/front/app.rtl.css`, postCssOptions);
mix.postCss(`${tmpCssBaseDir}/admin/admin.css`, `${tmpCssBaseDir}/admin/admin.rtl.css`, postCssOptions);

/*
 mix.webpackConfig({
 stats: {
 children: true, // Display child compilations
 warnings: true, // Display warnings
 errors: true    // Display errors
 }
 });
 */

/* Cache Busting */
mix.version();

/* Disable Compilation Notification */
mix.disableNotifications();
