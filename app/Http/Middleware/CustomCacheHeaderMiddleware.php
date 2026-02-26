<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class CustomCacheHeaderMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Skip cache headers for admin, account, and posts pages
        if (
            $request->is('admin*') ||
            $request->is('account*') ||
            $request->is('posts*')
        ) {
            return $response;
        }

        if ($response->getStatusCode() === 200) {
            $headers = [
                'Cache-Control'  => 'public, max-age=600',
                'Vary'           => 'Cookie',
            ];
            $response->headers->add($headers);
        }

        return $response;
    }

    /**
     * Determine the page type based on the request
     *
     * @param Request $request
     * @return string
     */
    private function determinePageType(Request $request): string
    {
        $path = $request->path();

        // Check if it's a search/listing page
        if (
            $request->is('search*') ||
            $request->is('*/search*') ||
            str_contains($path, '/c/') ||  // Category pages
            str_contains($path, '/l/') ||  // Location pages
            str_contains($path, '/tag/')   // Tag pages
        ) {
            return 'search';
        }

        // Default to home page
        return 'home';
    }
}