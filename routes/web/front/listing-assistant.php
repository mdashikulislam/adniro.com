<?php

/*
 * The AI box in the listing form (adniro:ai). Included from routes/web/front.php before the
 * listing creation routes.
 */

use App\Http\Controllers\Web\Front\Post\ListingAssistant\ListingAssistantController;
use App\Http\Controllers\Web\Front\Post\ListingAssistant\WritingHelpController;
use Illuminate\Support\Facades\Route;

Route::controller(ListingAssistantController::class)
	->group(function () {
		Route::post('posts/create/assistant/fill', 'fill')->name('listing.create.assistant.fill');
	});

// AI writing help next to Title and Description, on create and edit (members only)
Route::post('posts/assistant/write', [WritingHelpController::class, 'write'])->name('listing.assistant.write');
