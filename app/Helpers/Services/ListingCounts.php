<?php

namespace App\Helpers\Services;

use App\Models\Category;
use App\Models\Post;
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
	public const CACHE_TTL = 3600;

	/**
	 * @param string|null $countryCode
	 * @return array{cat: array<int, int>, city: array<int, int>, catCity: array<string, int>}
	 */
	public static function forCountry(?string $countryCode = null): array
	{
		$countryCode = !empty($countryCode) ? $countryCode : (string)config('country.code');
		$cacheId = 'sitemaps.listingCounts.' . strtolower($countryCode);

		return Cache::remember($cacheId, self::CACHE_TTL, function () use ($countryCode) {
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
		$cacheId = 'sitemaps.listingLastMod.' . strtolower($countryCode);

		return Cache::remember($cacheId, self::CACHE_TTL, function () use ($countryCode) {
			$rows = Post::query()
				->inCountry($countryCode)
				->whereNotNull('category_id')
				->groupBy('category_id', 'city_id')
				->selectRaw('category_id, city_id, MAX(COALESCE(updated_at, created_at)) as last_mod')
				->get();

			$parentByCat = Category::query()->pluck('parent_id', 'id');
			$timeZone = (string)config('app.timezone', 'UTC');

			$dates = ['country' => 0, 'cat' => [], 'city' => [], 'catCity' => []];
			foreach ($rows as $row) {
				if (empty($row->last_mod)) {
					continue;
				}
				$time = Carbon::parse($row->last_mod, $timeZone)->getTimestamp();
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
