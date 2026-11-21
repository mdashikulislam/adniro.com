<?php

namespace App\Services\ListingForm;

use App\Helpers\Common\Num;
use App\Models\Category;
use App\Models\City;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

/*
 * The price hint under the listing form's Price (adniro): what similar listings on the site ask,
 * from statistics only, never AI. It is shown as information and never fills the price.
 *
 * Counts live listings buyers can see (the Post global scopes: verified and approved), not
 * archived, with a price, in the site's country and currency, from the last 12 months, newest
 * 400. It narrows by place first: the same city, then the whole country, then every category
 * under the same parent. Each answer is cached for a few hours.
 */

class PriceHint
{
	public const SCOPE_CITY = 'city';
	public const SCOPE_COUNTRY = 'country';
	public const SCOPE_PARENT = 'parent';

	private const SAMPLE_SIZE = 400;
	private const MONTHS = 12;
	private const CACHE_HOURS = 6;
	private const DEFAULT_MIN_SAMPLE = 8;

	public function enabled(): bool
	{
		return config('settings.listing_form.price_hint_enabled', '1') == '1';
	}

	/**
	 * @param int|null $excludePostId the listing being edited, so it is not compared with itself
	 * @return array{low: float, high: float, median: float, count: int, scope: string, text: string}|null
	 */
	public function for(int $categoryId, ?int $cityId, ?int $excludePostId = null): ?array
	{
		$countryCode = (string)config('country.code');
		$currency = (string)config('currency.code');
		if ($categoryId <= 0 || $countryCode === '' || $currency === '') {
			return null;
		}

		$minSample = $this->minSample();
		$key = implode(':', ['price_hint', $countryCode, $currency, $minSample, $categoryId, (int)$cityId, (int)$excludePostId, app()->getLocale()]);

		// Wrapped, so "no hint" is cached too
		return Cache::remember($key, now()->addHours(self::CACHE_HOURS), fn () => [
			'hint' => $this->compute($categoryId, $cityId, $excludePostId, $countryCode, $currency, $minSample),
		])['hint'];
	}

	private function minSample(): int
	{
		return max(3, (int)config('settings.listing_form.price_hint_min_sample', self::DEFAULT_MIN_SAMPLE));
	}

	private function compute(int $categoryId, ?int $cityId, ?int $excludePostId, string $countryCode, string $currency, int $minSample): ?array
	{
		$category = Category::query()->withoutGlobalScopes()->find($categoryId);
		if (!$category) {
			return null;
		}

		$attempts = [];
		if ($cityId) {
			$attempts[] = [self::SCOPE_CITY, [$categoryId], $cityId];
		}
		$attempts[] = [self::SCOPE_COUNTRY, [$categoryId], null];
		if (!empty($category->parent_id)) {
			$siblings = Category::query()->withoutGlobalScopes()->where('parent_id', $category->parent_id)->pluck('id')->all();
			$attempts[] = [self::SCOPE_PARENT, $siblings, null];
		}

		foreach ($attempts as [$scope, $categoryIds, $inCity]) {
			$prices = $this->prices($countryCode, $currency, $categoryIds, $inCity, $excludePostId);
			$stats = PriceStats::summarize($prices, $minSample);
			if ($stats) {
				return $stats + ['scope' => $scope, 'text' => $this->text($stats, $scope, $cityId, $category)];
			}
		}

		return null;
	}

	/**
	 * @param int[] $categoryIds
	 * @return float[]
	 */
	private function prices(string $countryCode, string $currency, array $categoryIds, ?int $cityId, ?int $excludePostId): array
	{
		return Post::query()
			->unarchived()
			->whereNull('deleted_at')
			->where('country_code', $countryCode)
			->whereIn('category_id', $categoryIds)
			->when($cityId, fn ($query) => $query->where('city_id', $cityId))
			->when($excludePostId, fn ($query) => $query->whereKeyNot($excludePostId))
			->where('currency_code', $currency)
			->where('price', '>', 0)
			->where('created_at', '>=', now()->subMonths(self::MONTHS))
			->orderByDesc('created_at')
			->limit(self::SAMPLE_SIZE)
			->pluck('price')
			->map(fn ($price) => (float)$price)
			->all();
	}

	/**
	 * Plain text in the site currency, which listing prices are stored in: not converted to a
	 * visitor's display currency (the text is cached for everyone) and never HTML superscript.
	 */
	private function money(float $amount): string
	{
		$currency = (array)config('currency');
		$decimals = (floor($amount) == $amount) ? 0 : (int)($currency['decimal_places'] ?? 2);
		$number = Num::format($amount, $decimals, (string)($currency['decimal_separator'] ?? '.'), (string)($currency['thousand_separator'] ?? ','));
		$symbol = (string)($currency['symbol'] ?? ($currency['code'] ?? ''));

		return (($currency['in_left'] ?? 1) == 1) ? $symbol . $number : $number . ' ' . $symbol;
	}

	private function text(array $stats, string $scope, ?int $cityId, Category $category): string
	{
		$place = (string)config('country.name');
		if ($scope === self::SCOPE_CITY) {
			$city = City::query()->withoutGlobalScopes()->find($cityId);
			$place = $city ? (string)data_get($city, 'name') : $place;
		}
		$values = [
			'place' => $place,
			'low'   => $this->money($stats['low']),
			'high'  => $this->money($stats['high']),
			'count' => $stats['count'],
		];

		if ($scope === self::SCOPE_PARENT) {
			$parent = Category::query()->withoutGlobalScopes()->find($category->parent_id);

			return trans('listing_form.price_hint_parent', $values + ['category' => (string)data_get($parent, 'name')]);
		}

		return $stats['low'] === $stats['high']
			? trans('listing_form.price_hint_one', $values)
			: trans('listing_form.price_hint', $values);
	}
}
