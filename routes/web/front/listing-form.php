<?php

/*
 * Helpers for the listing form (adniro). Included from routes/web/front.php before the
 * listing creation routes.
 */

use App\Http\Controllers\Web\Front\Post\ListingForm\PriceHintController;
use Illuminate\Support\Facades\Route;

// What similar listings ask, under the Price field (statistics only)
Route::get('posts/price-hint', [PriceHintController::class, 'show'])
	->middleware('throttle:30,1')
	->name('listing.form.price-hint');
