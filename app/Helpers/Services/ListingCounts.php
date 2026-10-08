<?php

namespace App\Helpers\Services;

use App\Models\Category;
use App\Models\Post;
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
