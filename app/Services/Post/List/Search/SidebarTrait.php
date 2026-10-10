<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Services\Post\List\Search;

use App\Enums\PostType;
use App\Helpers\Common\Date\TimeZoneManager;
use App\Helpers\Services\ListingCounts;
use App\Models\Category;
use App\Models\City;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

trait SidebarTrait
{
	/**
	 * @param array|null $preSearch
	 * @param array|null $fields
	 * @param array $params
	 * @return array
	 */
	protected function getSidebar(?array $preSearch = [], ?array $fields = [], array $params = []): array
	{
		$citiesLimit = getNumberOfItemsToTake('cities');
		
		$data = [];
		
		// Get Root Categories
		$data['cats'] = $this->getRootCategories();
		
		$data['cat'] = $preSearch['cat'] ?? null;
		$data['customFields'] = $fields;
		
		$data['city'] = $preSearch['city'] ?? null;
		$data['admin'] = $preSearch['admin'] ?? null;
		
		if ($data['city'] instanceof City) {
			$data['city'] = $data['city']->toArray();
		}
		
		$data['countPostsPerCat'] = $this->countListingsPerCategory($data['city'], $params);
		$data['cities'] = $this->getMostPopulateCities($citiesLimit, $params, data_get($data['cat'], 'id'));
		$data['periodList'] = $this->getPeriodList($params);
		$data['postTypes'] = $this->getPostTypes($params);
		$data['orderByOptions'] = $this->orderByOptions($data['city'], $params);
		$data['displayModes'] = $this->getDisplayModes($params);
		
		return $data;
	}
	
	/**
	 * @param array|null $city
	 * @param array $params
	 * @return array
	 */
	private function countListingsPerCategory(?array $city = [], array $params = []): array
	{
		if (!config('settings.listings_list.show_left_sidebar')) {
			return [];
		}
		
		if (!config('settings.listings_list.count_categories_listings')) {
			return [];
		}
		
		$cityId = $city['id'] ?? null;
		
		// Cache Parameters
		$cacheParams = [
			'action'  => 'count.listings.per.cat',
			'country' => config('country.code'),
			'city'    => $cityId,
			'locale'  => config('app.locale'),
		];
		
		return caching()->remember(Category::class, $cacheParams, function () use ($cityId) {
			return Category::countListingsPerCategory($cityId);
		});
	}
	
	/**
	 * Cities linked in the sidebar: the ones with the most live listings (in the current
	 * category, on a category page), then the most populated ones to fill the list.
	 * Ranking by live listings links the pages worth crawling, wherever their city ranks
	 * by population, instead of the same most populated cities whether they have listings or not.
	 *
	 * @param int $limit
	 * @param array $params
	 * @param int|string|null $categoryId
	 * @return array
	 */
	private function getMostPopulateCities(int $limit = 50, array $params = [], int|string|null $categoryId = null): array
	{
		if (!config('settings.listings_list.show_left_sidebar')) {
			return [];
		}
		
		// Live listings per city (in the category), from the cached listings counts
		$counts = ListingCounts::forCountry(config('country.code'));
		$listingsPerCity = [];
		if (!empty($categoryId)) {
			$prefix = (int)$categoryId . '-';
			foreach ($counts['catCity'] ?? [] as $key => $total) {
				if (str_starts_with((string)$key, $prefix)) {
					$listingsPerCity[(int)substr((string)$key, strlen($prefix))] = (int)$total;
				}
			}
		} else {
			$listingsPerCity = $counts['city'] ?? [];
		}
		unset($listingsPerCity[0]);
		arsort($listingsPerCity);
		$topCityIds = array_slice(array_keys($listingsPerCity), 0, $limit);
		
		// Cache Parameters
		$cacheParams = [
			'action'  => 'get.sidebar.cities',
			'country' => config('country.code'),
			'cityIds' => implode(',', $topCityIds),
			'limit'   => $limit,
		];
		
		$cities = caching()->remember(City::class, $cacheParams, function () use ($topCityIds, $limit) {
			$cities = collect();
			if (!empty($topCityIds)) {
				$cities = City::query()->inCountry()->whereIn('id', $topCityIds)->get()
					->sortBy(fn ($city) => array_search($city->id, $topCityIds))
					->values();
			}
			
			// Fill the list with the most populated cities
			if ($cities->count() < $limit) {
				$moreCities = City::query()->inCountry()
					->whereNotIn('id', $cities->pluck('id')->all())
					->orderByDesc('population')
					->orderBy('name')
					->take($limit - $cities->count())
					->get();
				$cities = $cities->concat($moreCities)->values();
			}
			
			return $cities;
		});
		
		// Live listings counts (in the category)
		return $cities->map(function ($city) use ($listingsPerCity) {
			$city = $city->toArray();
			$city['posts_count'] = $listingsPerCity[$city['id']] ?? 0;
			
			return $city;
		})->all();
	}
	
	/**
	 * @param array $params
	 * @return array
	 */
	private function getPeriodList(array $params = []): array
	{
		if (!config('settings.listings_list.show_left_sidebar')) {
			return [];
		}
		
		$tz = TimeZoneManager::getContextualTimeZone();
		
		return [
			// '2'   => now($tz)->subDays()->fromNow(),
			'4'   => now($tz)->subDays(3)->fromNow(),
			'8'   => now($tz)->subDays(7)->fromNow(),
			'31'  => now($tz)->subMonths()->fromNow(),
			// '92'  => now($tz)->subMonths(3)->fromNow(),
			'184' => now($tz)->subMonths(6)->fromNow(),
			'368' => now($tz)->subYears()->fromNow(),
		];
	}
	
	/**
	 * @param array $params
	 * @return array
	 */
	private function getPostTypes(array $params = []): array
	{
		if (!config('settings.listing_form.show_listing_type')) {
			return [];
		}
		
		return PostType::all();
	}
	
	/**
	 * @param array|null $city
	 * @param array $params
	 * @return array
	 */
	private function orderByOptions(?array $city = [], array $params = []): array
	{
		$keyword = data_get($params, 'keyword');
		$keyword = data_get($params, 'q', $keyword);
		$orderBy = data_get($params, 'orderBy');
		$orderBy = data_get($params, 'sort', $orderBy);
		
		$distanceRange = $this->getDistanceRanges($city);
		
		$orderByArray = [
			[
				'condition'  => true,
				'isSelected' => false,
				'query'      => ['orderBy' => ''],
				'label'      => t('Sort by'),
			],
			[
				'condition'  => !empty($city),
				'isSelected' => ($orderBy == 'distance'),
				'query'      => ['orderBy' => 'distance'],
				'label'      => t('distance'),
			],
			[
				'condition'  => true,
				'isSelected' => ($orderBy == 'priceAsc'),
				'query'      => ['orderBy' => 'priceAsc'],
				'label'      => t('price_low_to_high'),
			],
			[
				'condition'  => true,
				'isSelected' => ($orderBy == 'priceDesc'),
				'query'      => ['orderBy' => 'priceDesc'],
				'label'      => t('price_high_to_low'),
			],
			[
				'condition'  => !empty($keyword),
				'isSelected' => ($orderBy == 'relevance'),
				'query'      => ['orderBy' => 'relevance'],
				'label'      => t('Relevance'),
			],
			[
				'condition'  => true,
				'isSelected' => ($orderBy == 'date'),
				'query'      => ['orderBy' => 'date'],
				'label'      => t('Date'),
			],
			[
				'condition'  => config('plugins.reviews.installed'),
				'isSelected' => ($orderBy == 'rating'),
				'query'      => ['orderBy' => 'rating'],
				'label'      => trans('reviews::messages.Rating'),
			],
		];
		
		return array_merge($orderByArray, $distanceRange);
	}
	
	/**
	 * @param array|null $city
	 * @param array $params
	 * @return array
	 */
	private function getDistanceRanges(?array $city = [], array $params = []): array
	{
		if (!config('settings.listings_list.cities_extended_searches')) {
			return [];
		}
		
		$defaultDistance = config('settings.listings_list.search_distance_default', 100);
		$distance = $params['distance'] ?? $defaultDistance;
		
		config()->set('distance.distanceRange.min', 0);
		config()->set('distance.distanceRange.max', config('settings.listings_list.search_distance_max', 500));
		config()->set('distance.distanceRange.interval', config('settings.listings_list.search_distance_interval', 150));
		$distanceRange = DistanceHelper::distanceRange();
		
		// Format the Array for the OrderBy SelectBox
		return collect($distanceRange)
			->map(function ($item) use ($defaultDistance, $city, $distance) {
				return [
					'condition'  => !empty($city),
					'isSelected' => ($distance == $item),
					'query'      => ['distance' => $item],
					'label'      => t('around_x_distance', ['distance' => $item, 'unit' => getDistanceUnit()]),
				];
			})->toArray();
	}
	
	/**
	 * @return array[]
	 */
	private function getDisplayModes(array $params = []): array
	{
		return [
			'grid-view'    => [
				'icon'  => 'bi bi-grid-fill',
				'query' => ['display' => 'grid'],
			],
			'list-view'    => [
				'icon'  => 'fa-solid fa-list',
				'query' => ['display' => 'list'],
			],
			'compact-view' => [
				'icon'  => 'fa-solid fa-bars',
				'query' => ['display' => 'compact'],
			],
		];
	}
}
