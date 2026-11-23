<?php

namespace App\Services;

use App\Helpers\Common\SiteHost;
use App\Http\Middleware\GuestPageCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/*
 * The server's own copy of guest pages (adniro).
 *
 * A page built for a visitor without a session is the same for every such visitor: the CDN keeps it for ten
 * minutes (App\Http\Middleware\GuestPageCache). But each of the CDN's many locations keeps its own copy, so
 * most first visits still reached the server, which built the same page again (0.3 to 0.6 s). The finished
 * page is now kept here as well, for the same ten minutes, and handed back in the time it takes to start PHP.
 *
 * Only what the CDN already treats as one page is kept as one page here:
 * - the site's own address, without a query string or with a plain "page" number only (searches with words
 *   are too many to keep);
 * - never when the page depends on the visitor's country by IP or on their browser's language.
 * A copy is compressed, and at most MAX_PER_WINDOW copies are made per ten minutes, so made-up addresses
 * cannot fill the cache.
 */
class GuestPageStore
{
	public const SECONDS = GuestPageCache::EDGE_TTL;
	private const MAX_PER_WINDOW = 3000;
	private const KEY_PREFIX = 'guest_page:';
	private const COUNTER_PREFIX = 'guest_page_count:';
	private const COMPRESSION_LEVEL = 6;
	private const ALLOWED_QUERY_KEYS = ['page'];
	private const PAGE_NUMBER_PATTERN = '/^[1-9][0-9]{0,3}$/';
	/** Sent fresh with every answer, or not ours to repeat */
	private const HEADERS_NOT_KEPT = ['date', 'set-cookie', 'cache-control', 'age', 'content-length', 'etag', 'x-guest-cache'];

	/** Whether this address has one page for every guest, and few enough addresses like it to keep. */
	public static function covers(Request $request): bool
	{
		if (config('settings.localization.geoip_activation') == '1') {
			return false;
		}
		if (config('settings.localization.auto_detect_language') === 'from_browser') {
			return false;
		}

		if (!SiteHost::isOwn($request)) {
			return false;
		}
		if (array_diff(array_keys($request->query->all()), self::ALLOWED_QUERY_KEYS) !== []) {
			return false;
		}
		
		// "page" is a plain page number or absent: nothing else (a list, a long string) gets a copy of its own
		$page = $request->query->all()['page'] ?? null;
		
		return $page === null || (is_string($page) && preg_match(self::PAGE_NUMBER_PATTERN, $page) === 1);
	}

	/**
	 * The kept page, or null.
	 *
	 * @return array{content: string, headers: array<string, array<int, string>>, etag: ?string, age: int}|null
	 */
	public static function get(Request $request): ?array
	{
		// A "HEAD" request is answered by building the page as before: it has no body to hand back
		if ($request->getMethod() !== 'GET' || !self::covers($request)) {
			return null;
		}

		try {
			$kept = Cache::get(self::key($request));
			if (!is_array($kept) || !isset($kept['body'], $kept['at'])) {
				return null;
			}
			$content = gzuncompress($kept['body']);
			if ($content === false) {
				return null;
			}

			return [
				'content' => $content,
				'headers' => (array)($kept['headers'] ?? []),
				'etag'    => $kept['etag'] ?? null,
				'age'     => max(0, now()->getTimestamp() - (int)$kept['at']),
			];
		} catch (Throwable $e) {
			Log::warning('Guest page store: read failed', ['error' => $e->getMessage()]);

			return null;
		}
	}

	/** Keeps a page that was just built for a guest. A failure here never fails the page. */
	public static function put(Request $request, Response $response): void
	{
		if ($request->getMethod() !== 'GET' || !self::covers($request)) {
			return;
		}

		try {
			$content = $response->getContent();
			if (!is_string($content) || $content === '' || !self::hasRoom()) {
				return;
			}

			$headers = array_filter(
				$response->headers->allPreserveCaseWithoutCookies(),
				fn ($name) => !in_array(strtolower((string)$name), self::HEADERS_NOT_KEPT, true),
				ARRAY_FILTER_USE_KEY
			);

			Cache::put(self::key($request), [
				'body'    => gzcompress($content, self::COMPRESSION_LEVEL),
				'headers' => $headers,
				'etag'    => $response->getEtag(),
				'at'      => now()->getTimestamp(),
			], self::SECONDS);
		} catch (Throwable $e) {
			Log::warning('Guest page store: write failed', ['error' => $e->getMessage()]);
		}
	}

	/** At most MAX_PER_WINDOW pages are kept per ten minutes. */
	private static function hasRoom(): bool
	{
		$counter = self::COUNTER_PREFIX . intdiv(now()->getTimestamp(), self::SECONDS);
		Cache::add($counter, 0, self::SECONDS * 2);

		return (int)Cache::increment($counter) <= self::MAX_PER_WINDOW;
	}

	private static function key(Request $request): string
	{
		$page = (string)($request->query->all()['page'] ?? '');

		return self::KEY_PREFIX . sha1($request->getSchemeAndHttpHost() . '|' . $request->getPathInfo() . '|' . $page);
	}
}
