<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/*
 * The AI review queue is for super admins and for staff allowed to edit listings (adniro:ai).
 */

class ModerationQueueAccess
{
	public const PERMISSION = 'admin.post.edit';
	
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
