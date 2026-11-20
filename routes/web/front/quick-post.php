<?php

/*
 * Quick post (adniro:ai). Included from routes/web/front.php before the listing creation routes.
 */

use App\Http\Controllers\Web\Front\Post\QuickPost\QuickPostController;
use Illuminate\Support\Facades\Route;

Route::controller(QuickPostController::class)
	->group(function () {
		Route::get('posts/create/quick', 'composer')->name('listing.create.quick');
		Route::post('posts/create/quick/draft', 'draft')->name('listing.create.quick.draft');
		Route::get('posts/create/quick/{token}/apply', 'apply')
			->where('token', '[A-Za-z0-9]{32}')
			->name('listing.create.quick.apply');
	});
