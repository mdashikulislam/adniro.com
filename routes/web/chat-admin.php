<?php

/*
 * Admin > Users > Chat reports (adniro): the conversations members reported, to read and to close.
 * Included from routes/web/admin.php, inside the admin group (prefix, middleware and namespace).
 * Open to super admins and to staff allowed to edit members (ChatReportAccess).
 */

use App\Http\Controllers\Web\Admin\ChatReportController;
use App\Http\Middleware\ChatReportAccess;
use Illuminate\Support\Facades\Route;

Route::middleware([ChatReportAccess::class])
	->controller(ChatReportController::class)
	->group(function () {
		Route::get('chat-reports', 'reports');
		Route::get('chat-reports/{id}', 'report')->whereNumber('id');
		Route::post('chat-reports/{id}/close', 'close')->whereNumber('id');
		Route::post('chat-reports/{id}/reopen', 'reopen')->whereNumber('id');
		Route::get('chat-reports/{id}/photos/{message}', 'photo')->whereNumber(['id', 'message']);
	});
