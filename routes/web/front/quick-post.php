<?php

/*
 * The AI box in the listing form (adniro:ai). Included from routes/web/front.php before the
 * listing creation routes.
 */

use App\Http\Controllers\Web\Front\Post\QuickPost\QuickPostController;
use Illuminate\Support\Facades\Route;

Route::controller(QuickPostController::class)
	->group(function () {
		// The old composer page now opens the normal form, which has the AI box
		Route::get('posts/create/quick', 'composer')->name('listing.create.quick');
		Route::post('posts/create/quick/fill', 'fill')->name('listing.create.quick.fill');
	});
