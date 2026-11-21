<?php

/*
 * favicon.ico, app icons and the web app manifest (adniro). Included from routes/web/front.php.
 */

use App\Http\Controllers\Web\Front\SiteIconController;
use Illuminate\Support\Facades\Route;

Route::controller(SiteIconController::class)
	->group(function () {
		Route::get('favicon.ico', 'favicon')->name('site.favicon');
		Route::get('apple-touch-icon.png', 'appleTouchIcon')->name('site.apple_touch_icon');
		Route::get('icon-{size}.png', 'manifestIcon')->whereNumber('size')->name('site.manifest_icon');
		Route::get('icon-maskable-{size}.png', 'maskableIcon')->whereNumber('size')->name('site.maskable_icon');
		Route::get('manifest.json', 'manifest')->name('site.manifest');
	});
