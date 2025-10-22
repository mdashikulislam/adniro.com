const mix = require('laravel-mix');


// Init. Base directories
const sassBaseDir = 'resources/sass';
const tmpCssBaseDir = 'public/assets/resources/css';

// Global Laravel-Mix Options
mix.options({processCssUrls: false});

// SASS Options
const sassOptions = {
	// https://github.com/twbs/bootstrap/issues/40621#issuecomment-3038628160 (2025/07/05)
	silenceDeprecations: [
		"mixed-decls",
		"color-functions",
		"global-builtin",
		"import",
	],
};

// SASS Local Options (Not Global)
const lineOptions = {
	sassOptions: sassOptions
};

/* SASS/SCSS File Processing (Export CSS's LTR versions) */
mix.sass(`${sassBaseDir}/auth/bootstrap.scss`, `${tmpCssBaseDir}/auth/bootstrap.css`, lineOptions);
mix.sass(`${sassBaseDir}/front/bootstrap.scss`, `${tmpCssBaseDir}/front/bootstrap.css`, lineOptions);
mix.sass(`${sassBaseDir}/admin/bootstrap.scss`, `${tmpCssBaseDir}/admin/bootstrap.css`, lineOptions);
mix.sass(`${sassBaseDir}/auth/auth.scss`, `${tmpCssBaseDir}/auth/auth.css`);
mix.sass(`${sassBaseDir}/front/app.scss`, `${tmpCssBaseDir}/front/app.css`);
mix.sass(`${sassBaseDir}/admin/admin.scss`, `${tmpCssBaseDir}/admin/admin.css`);

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
