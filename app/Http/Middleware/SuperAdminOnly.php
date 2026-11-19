<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/*
 * Restricts a route to super admins (adniro:ai).
 * Used for the AI provider and model screens, which hold API keys.
 */

class SuperAdminOnly
{
	public function handle(Request $request, Closure $next)
	{
		if (!userHasSuperAdminPermissions()) {
			abort(403, trans('admin.forbidden'));
		}
		
		return $next($request);
	}
}
