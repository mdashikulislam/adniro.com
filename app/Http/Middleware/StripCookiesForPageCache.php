<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Removes Set-Cookie from responses that CustomCacheHeaderMiddleware marked
 * as shared-cacheable (cookieless guest/bot traffic). Session & XSRF cookies
 * are attached by the 'web' group as the response bubbles out, so this must
 * run in the global (outermost) stack. Without this, Cloudflare refuses to
 * edge-cache the pages (cf-cache-status: BYPASS on every hit).
 */
class StripCookiesForPageCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->headers->has('X-Page-Cache')) {
            $response->headers->remove('Set-Cookie');
        }

        return $response;
    }
}
