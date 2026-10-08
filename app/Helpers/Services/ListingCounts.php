<?php

namespace App\Helpers\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Live listings counts for a country, aggregated per category (including
 * ancestor categories), per city and per category×city combination.
 *
 * Built from a single grouped query and cached. Shared by the XML sitemaps
 * and by the search sidebar so both agree on which pages are worth crawling.
 */
class ListingCounts
{
	/**
	 * Seconds the data is fresh. It's rebuilt every 30 minutes by the scheduler
	 * (see the "listings:warm-counts" command), so requests rarely see it stale.
	 */
	public const CACHE_TTL = 1800;
	
	/**
	 * Seconds stale data is still served while it's rebuilt
	 */
	public const STALE_TTL = 86400;
	
	/**
	 * Data already read during the current request (the search sidebar reads it once per city link)
	 */
	private static array $memo = [];

	/**
	 * @param string|null $countryCode
	 * @return array{cat: array<int, int>, city: array<int, int>, catCity: array<string, int>}
	 */
	public static function forCountry(?string $countryCode = null): array
	{
		$countryCode = !empty($countryCode) ? $countryCode : (string)config('country.code');
		$cacheId = 'seo.listingCounts.v2.' . strtolower($countryCode);

		return self::remember($cacheId, function () use ($countryCode) {
			$rows = Post::query()
				->verified()
				->unarchived()
				->whereNull('deleted_at')
				->inCountry($countryCode)
				->whereNotNull('category_id')
				->groupBy('category_id', 'city_id')
				->selectRaw('category_id, city_id, COUNT(*) as total')
				->get();

			$parentByCat = Category::query()->pluck('parent_id', 'id');

			$counts = ['cat' => [], 'city' => [], 'catCity' => []];
			foreach ($rows as $row) {
				$cityId = (int)$row->city_id;
				$total = (int)$row->total;

				$counts['city'][$cityId] = ($counts['city'][$cityId] ?? 0) + $total;

				// Roll the count up through the category's ancestors
				$catId = (int)$row->category_id;
				$depthGuard = 0;
				while (!empty($catId) && $depthGuard++ < 10) {
					$counts['cat'][$catId] = ($counts['cat'][$catId] ?? 0) + $total;
					$key = $catId . '-' . $cityId;
					$counts['catCity'][$key] = ($counts['catCity'][$key] ?? 0) + $total;
					$catId = (int)($parentByCat[$catId] ?? 0);
				}
			}

			return $counts;
		});
	}

	/**
	 * Last time the listings of a country changed (UNIX timestamps), aggregated
	 * like forCountry(): per category (including ancestors), per city and per
	 * category×city, plus the whole country.
	 *
	 * Archived & deleted listings are included on purpose: archiving or deleting
	 * a listing bumps its "updated_at", and that removal changes the result pages
	 * too. Used as the real "lastmod" of the XML sitemaps' URLs.
	 *
	 * @param string|null $countryCode
	 * @return array{country: int, cat: array<int, int>, city: array<int, int>, catCity: array<string, int>}
	 */
	public static function lastModifiedForCountry(?string $countryCode = null): array
	{
		$countryCode = !empty($countryCode) ? $countryCode : (string)config('country.code');
		$cacheId = 'seo.listingLastMod.v2.' . strtolower($countryCode);

		return self::remember($cacheId, function () use ($countryCode) {
			// Without the verified/reviewed scopes: the query is then answered from the
			// "idx_posts_lastmod_cat_city" index alone (a not yet verified listing's change
			// only makes a "lastmod" slightly too recent, which is harmless)
			$rows = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->inCountry($countryCode)
				->whereNotNull('category_id')
				->groupBy('category_id', 'city_id')
				->selectRaw('category_id, city_id, MAX(updated_at) as updated, MAX(created_at) as created')
				->get();

			$parentByCat = Category::query()->pluck('parent_id', 'id');
			$timeZone = (string)config('app.timezone', 'UTC');

			$dates = ['country' => 0, 'cat' => [], 'city' => [], 'catCity' => []];
			foreach ($rows as $row) {
				$lastMod = max((string)$row->updated, (string)$row->created);
				if (empty($lastMod)) {
					continue;
				}
				$time = Carbon::parse($lastMod, $timeZone)->getTimestamp();
				$cityId = (int)$row->city_id;

				$dates['country'] = max($dates['country'], $time);
				$dates['city'][$cityId] = max($dates['city'][$cityId] ?? 0, $time);

				// Roll the date up through the category's ancestors
				$catId = (int)$row->category_id;
				$depthGuard = 0;
				while (!empty($catId) && $depthGuard++ < 10) {
					$dates['cat'][$catId] = max($dates['cat'][$catId] ?? 0, $time);
					$key = $catId . '-' . $cityId;
					$dates['catCity'][$key] = max($dates['catCity'][$key] ?? 0, $time);
					$catId = (int)($parentByCat[$catId] ?? 0);
				}
			}

			return $dates;
		});
	}

	/**
	 * Rebuild a country's cached data now (used by the scheduler)
	 *
	 * @param string $countryCode
	 * @return void
	 */
	public static function refresh(string $countryCode): void
	{
		$code = strtolower($countryCode);
		foreach (['seo.listingCounts.v2.', 'seo.listingLastMod.v2.'] as $prefix) {
			Cache::forget($prefix . $code . '.built');
			unset(self::$memo[$prefix . $code]);
		}

		self::forCountry($countryCode);
		self::lastModifiedForCountry($countryCode);
	}

	/**
	 * Cache for data built by a query scanning a whole country's listings:
	 * - read once per request (memoized),
	 * - when it's stale, a single request rebuilds it while the others keep getting the stale data,
	 * - when there's none (e.g. after a cache clear), a single process builds it, the others wait for it,
	 * so concurrent requests (e.g. a crawler's) never run the heavy query at the same time.
	 *
	 * @param string $cacheId
	 * @param callable $callback
	 * @return array
	 */
	private static function remember(string $cacheId, callable $callback): array
	{
		// Memoized during web requests only (console processes, e.g. queue workers, can be long-running)
		$canMemoize = !app()->runningInConsole();
		if ($canMemoize && isset(self::$memo[$cacheId])) {
			return self::$memo[$cacheId];
		}

		$builtKey = $cacheId . '.built';
		$build = function () use ($cacheId, $builtKey, $callback) {
			$data = $callback();
			Cache::put($cacheId, $data, self::STALE_TTL);
			Cache::put($builtKey, time(), self::CACHE_TTL);

			return $data;
		};

		$data = Cache::get($cacheId);
		if (is_array($data)) {
			// Stale: one request (the one getting the lock) rebuilds it, the others use the stale data
			if (!Cache::has($builtKey)) {
				$lock = Cache::lock('lock.' . $cacheId, 300);
				if ($lock->get()) {
					try {
						$data = $build();
					} finally {
						$lock->release();
					}
				}
			}

			return $canMemoize ? (self::$memo[$cacheId] = $data) : $data;
		}

		// Missing: built by a single process at a time, which the others wait for
		$data = Cache::lock('lock.' . $cacheId, 300)->block(120, function () use ($cacheId, $build) {
			$data = Cache::get($cacheId);

			return is_array($data) ? $data : $build();
		});

		return $canMemoize ? (self::$memo[$cacheId] = $data) : $data;
	}

	/**
	 * Live listings for a category×city pair (0 when unknown)
	 */
	public static function forCategoryCity(int|string|null $categoryId, int|string|null $cityId, ?string $countryCode = null): int
	{
		if (empty($categoryId) || empty($cityId)) {
			return 0;
		}

		$counts = self::forCountry($countryCode);

		return (int)($counts['catCity'][(int)$categoryId . '-' . (int)$cityId] ?? 0);
	}

	/**
	 * The indexing threshold shared with the "noindex" rules
	 */
	public static function minListingsToIndex(): int
	{
		return (int)config('seo.min_listings_to_index', 3);
	}
}
