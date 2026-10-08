<?php
/*
 * Guest page cache
 * ----------------
 * Makes anonymous GET page views cacheable by the CDN (Cloudflare):
 *  - the session is not persisted (in-memory only) and no cookies are sent back,
 *  - a public "s-maxage" Cache-Control header is sent,
 * so the CDN can serve the same HTML to every cookie-less visitor.
 *
 * Any request that carries a session or "remember me" cookie, is not a GET,
 * targets an interactive area (account, auth, listing forms, ajax...) or whose
 * response is not a plain 200 HTML page keeps the normal (private) behaviour.
 *
 * The CSRF token embedded in cached pages is refreshed on demand by
 * public/assets/js/app/guest-cache.js through the "common/csrf-token" endpoint.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestPageCache
{
	/**
	 * Seconds a guest page may be kept by the CDN
	 */
	public const EDGE_TTL = 600;
	
	/**
	 * Request attribute set when the current request is a cacheable guest page view
	 */
	public const ATTRIBUTE = 'guest_page_cacheable';
	
	/**
	 * URI prefixes (first segment, or "a/b" pairs) that are never cacheable
	 */
	protected array $excludedPrefixes = [
		'admin', 'account', 'auth', 'login', 'logout', 'register', 'password', 'verify',
		'posts/create', 'posts/edit', 'post/create', 'post/edit', 'create', 'edit', 'checkout', 'payment',
		'common', 'browsing', 'ajax', 'api', 'locale', 'lang', 'install', 'upgrade', 'impersonate',
		'webhooks', 'captcha', 'messages', 'contact', 'file', 'oauth', 'sitemaps', 'feed', 'plugins',
	];
	
	public function handle(Request $request, Closure $next): Response
	{
		// Kill switch: the CDN must bypass its cache for visitors carrying a session cookie
		// before this is enabled (GUEST_PAGE_CACHE=true in .env), otherwise logged-in users
		// could be served a cached guest page.
		if (!filter_var(env('GUEST_PAGE_CACHE', false), FILTER_VALIDATE_BOOLEAN)) {
			return $next($request);
		}
		
		$isCacheable = $this->isCacheableGuestRequest($request);
		
		if ($isCacheable) {
			$request->attributes->set(self::ATTRIBUTE, true);
			
			// Keep the session in memory only: nothing is written to Redis for cookie-less guests
			config()->set('session.driver', 'array');
		}
		
		$response = $next($request);
		
		if (!$isCacheable) {
			return $response;
		}
		
		if (!$this->isCacheableResponse($request, $response)) {
			$request->attributes->set(self::ATTRIBUTE, false);
			
			return $response;
		}
		
		// No cookies at all on a cacheable page
		foreach ($response->headers->getCookies() as $cookie) {
			$response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
		}
		$response->headers->remove('Set-Cookie');
		
		// Browser: always revalidate. CDN: keep for EDGE_TTL seconds.
		$response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=' . self::EDGE_TTL . ', stale-while-revalidate=60');
		$response->headers->set('Vary', 'Accept-Encoding');
		$response->headers->set('X-Guest-Cache', 'cacheable');

		// Conditional requests: crawlers (Googlebot...) send back the ETag they got, and an
		// unchanged page is answered with an empty "304 Not Modified" instead of the full HTML
		$this->setEtag($response);
		$response->isNotModified($request);

		return $response;
	}

	/**
	 * Weak ETag from the page's content. The per-visitor CSRF token is left out of the hash
	 * (it's refreshed on the client by guest-cache.js), so the ETag only changes when the
	 * page itself changes. Weak, because the CDN may re-compress the body.
	 */
	protected function setEtag(Response $response): void
	{
		$content = $response->getContent();
		if (!is_string($content) || $content === '') {
			return;
		}

		$content = preg_replace(
			'#((?:name="csrf-token"\s+content|data-csrf-token|name="_token"\s+value)=")[^"]*(")#i',
			'$1$2',
			$content
		);

		// Parts that change on every view (visit counter, relative dates) are marked
		// with "data-etag-skip" in the views and left out too
		$content = preg_replace('#(<span data-etag-skip>).*?(</span>)#s', '$1$2', (string)$content);
		
		$response->setEtag(md5((string)$content), true);
	}
	
	protected function isCacheableGuestRequest(Request $request): bool
	{
		if (!in_array($request->getMethod(), ['GET', 'HEAD'])) {
			return false;
		}
		if ($request->ajax() || $request->wantsJson() || $request->expectsJson() || $request->pjax()) {
			return false;
		}
		if ($request->filled('sessionId') || $request->filled('skin') || $request->filled('display') || $request->filled('debug')) {
			return false;
		}
		
		// A session / remember-me / auth cookie means this visitor is not a plain guest
		$sessionCookie = (string)config('session.cookie', 'laravel_session');
		foreach (array_keys($request->cookies->all()) as $cookieName) {
			if (
				$cookieName === $sessionCookie
				|| str_starts_with($cookieName, 'remember_web')
				|| str_starts_with($cookieName, 'remember_')
			) {
				return false;
			}
		}
		
		$path = trim($request->path(), '/');
		foreach ($this->excludedPrefixes as $prefix) {
			if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
				return false;
			}
			// Same prefixes under a country/locale segment (e.g. "us/account", "fr/posts/create")
			if (preg_match('#^[a-z]{2}(-[a-zA-Z]{2,4})?/(.+)$#', $path, $m)) {
				if ($m[2] === $prefix || str_starts_with($m[2], $prefix . '/')) {
					return false;
				}
			}
		}
		
		return true;
	}
	
	protected function isCacheableResponse(Request $request, Response $response): bool
	{
		if ($response->getStatusCode() !== 200) {
			return false;
		}
		
		$contentType = (string)$response->headers->get('Content-Type', '');
		if (!str_contains($contentType, 'text/html')) {
			return false;
		}
		
		// The app explicitly asked for no caching (e.g. "no.http.cache" middleware).
		// Note: Symfony adds a default "no-cache, private" to every response, so only "no-store" counts.
		$cacheControl = strtolower((string)$response->headers->get('Cache-Control', ''));
		if (str_contains($cacheControl, 'no-store')) {
			return false;
		}
		
		// Never cache a page rendered for an authenticated user
		if (function_exists('auth') && auth()->check()) {
			return false;
		}
		
		return true;
	}
}
