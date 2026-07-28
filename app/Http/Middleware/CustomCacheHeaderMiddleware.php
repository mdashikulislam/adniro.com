<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomCacheHeaderMiddleware
{
    /**
     * Full-page cache lifetime (in seconds) for guest/bot requests,
     * also used as the browser/proxy Cache-Control max-age.
     */
    protected int $cacheTtl = 600;

    public function handle(Request $request, Closure $next)
    {
        if (!$this->isCacheable($request)) {
            return $next($request);
        }

        $cacheKey = $this->getCacheKey($request);

        // Serve the cached copy without running the controller at all.
        // This is what protects PHP-FPM & MySQL from crawler traffic.
        $cached = cache()->get($cacheKey);
        if (is_array($cached) && isset($cached['content'])) {
            return response($cached['content'], 200, [
                'Content-Type'  => $cached['contentType'] ?? 'text/html; charset=UTF-8',
                'Cache-Control' => 'public, max-age=' . $this->cacheTtl,
                'X-Page-Cache'  => 'HIT',
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            cache()->put($cacheKey, [
                'content'     => $response->getContent(),
                'contentType' => $response->headers->get('Content-Type'),
            ], $this->cacheTtl);

            $response->headers->set('Cache-Control', 'public, max-age=' . $this->cacheTtl);
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }

    /**
     * Only GET requests from visitors WITHOUT a session cookie (crawlers,
     * first-time hits) are served from the shared cache. Anyone with a
     * session (logged-in users, returning visitors) always gets a fresh,
     * personalized response, so CSRF tokens and account state stay correct.
     */
    private function isCacheable(Request $request): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        if ($request->hasCookie(config('session.cookie', 'laravel_session'))) {
            return false;
        }

        if (
            $request->is('admin*') ||
            $request->is('account*') ||
            $request->is('posts*')
        ) {
            return false;
        }

        return true;
    }

    private function getCacheKey(Request $request): string
    {
        // Locale & country are resolved before route middleware runs, so two
        // visitors hitting the same URL with different detected languages
        // must not share a cache entry.
        return 'page-cache:'
            . md5($request->fullUrl() . '|' . app()->getLocale() . '|' . config('country.code'));
    }
}
