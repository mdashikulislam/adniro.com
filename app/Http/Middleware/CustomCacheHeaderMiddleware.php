<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Pages served to cookie-less guests get their cache headers from GuestPageCache.
 * Everything else (visitors with a session, logged-in users) must stay private.
 */
class CustomCacheHeaderMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		$response = $next($request);
		
		if ($request->attributes->get(GuestPageCache::ATTRIBUTE) === true) {
			return $response;
		}
		
		if ($response->getStatusCode() === 200) {
			$response->headers->set('Cache-Control', 'private, no-cache, max-age=0');
		}
		
		return $response;
	}
}
