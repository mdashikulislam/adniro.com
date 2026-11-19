<?php

/*
 * Admin > AI Providers and AI Models (adniro:ai).
 * Included from routes/web/admin.php, inside the admin group (prefix, middleware and namespace).
 * These screens hold API keys, so they are limited to super admins.
 */

use App\Http\Controllers\Web\Admin\AiModelController;
use App\Http\Controllers\Web\Admin\AiProviderController;
use App\Http\Controllers\Web\Admin\Panel\Library\PanelRoutes;
use App\Http\Middleware\SuperAdminOnly;
use Illuminate\Support\Facades\Route;

Route::middleware([SuperAdminOnly::class])
	->group(function () {
		Route::post('ai_providers/{id}/test', [AiProviderController::class, 'test'])->whereNumber('id');
		Route::post('ai_models/{id}/test', [AiModelController::class, 'test'])->whereNumber('id');
		
		PanelRoutes::resource('ai_providers', AiProviderController::class);
		PanelRoutes::resource('ai_models', AiModelController::class);
	});
