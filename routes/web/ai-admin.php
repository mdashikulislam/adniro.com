<?php

/*
 * Admin > AI Providers and AI Models, and the AI review queue (adniro:ai).
 * Included from routes/web/admin.php, inside the admin group (prefix, middleware and namespace).
 * Providers and models hold API keys, so they are limited to super admins.
 * The review queue is open to super admins and to staff allowed to edit listings.
 */

use App\Http\Controllers\Web\Admin\AiModelController;
use App\Http\Controllers\Web\Admin\AiPostLimitController;
use App\Http\Controllers\Web\Admin\AiProviderController;
use App\Http\Controllers\Web\Admin\ModerationQueueController;
use App\Http\Controllers\Web\Admin\Panel\Library\PanelRoutes;
use App\Http\Middleware\ModerationQueueAccess;
use App\Http\Middleware\SuperAdminOnly;
use Illuminate\Support\Facades\Route;

Route::middleware([SuperAdminOnly::class])
	->group(function () {
		Route::post('ai_providers/{id}/test', [AiProviderController::class, 'test'])->whereNumber('id');
		Route::post('ai_models/{id}/test', [AiModelController::class, 'test'])->whereNumber('id');
		// "Load models": calls the provider's API, so it is rate limited (before the resource routes)
		Route::get('ai_models/available', [AiModelController::class, 'available'])->middleware('throttle:20,1');

		PanelRoutes::resource('ai_providers', AiProviderController::class);
		PanelRoutes::resource('ai_models', AiModelController::class);
	});

Route::middleware([ModerationQueueAccess::class])
	->controller(ModerationQueueController::class)
	->group(function () {
		Route::get('moderation', 'queue');
		Route::get('moderation/{id}/history', 'history')->whereNumber('id');
		Route::get('moderation/{id}/reject', 'rejectForm')->whereNumber('id');
		Route::post('moderation/{id}/approve', 'approve')->whereNumber('id');
		Route::post('moderation/{id}/reject', 'reject')->whereNumber('id');
		Route::post('moderation/{id}/rerun', 'rerun')->whereNumber('id');
	});

// AI post limit of one user (access checked in the controller)
Route::controller(AiPostLimitController::class)
	->group(function () {
		Route::get('users/{id}/ai-post-limit', 'limitForm')->whereNumber('id');
		Route::post('users/{id}/ai-post-limit', 'saveLimit')->whereNumber('id');
	});
