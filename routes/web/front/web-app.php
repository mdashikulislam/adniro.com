<?php

/*
 * The installable app's offline page (adniro), kept by the service worker public/sw.js.
 * Included from routes/web/front.php.
 */

use App\Http\Controllers\Web\Front\WebAppController;
use Illuminate\Support\Facades\Route;

Route::get('offline', [WebAppController::class, 'offline'])->name('site.offline');
