<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/*
 * Chat reports are for super admins and for staff allowed to edit members (adniro). A reported conversation
 * can be read there, and only there: admins have no way to open a conversation nobody reported.
 */

class ChatReportAccess
{
	public const PERMISSION = 'admin.user.edit';
	
	public function handle(Request $request, Closure $next)
	{
		abort_unless(self::allows(auth()->user()), 403);
		
		return $next($request);
	}
	
	public static function allows($user): bool
	{
		return !empty($user) && (userHasSuperAdminPermissions() || doesUserHavePermission($user, self::PERMISSION));
	}
}
