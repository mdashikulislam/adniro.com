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

namespace App\Http\Controllers\Web\Front;

use App\Models\Category;
use App\Models\City;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class SitemapController extends FrontController
{
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function __invoke()
	{
		$locale = app()->getLocale(); // config('app.locale')
		$categoriesLimit = getNumberOfItemsToTake('categories');
		$citiesLimit = getNumberOfItemsToTake('cities');
		
		$data = [];
		
		$relations = [
			'parent',
			'children' => fn (Builder $query) => $query->orderBy('lft')->limit($categoriesLimit),
			'children.parent',
		];
		
		// Cache Parameters
		$cacheParams = [
			'action'    => 'get.root.categories',
			'relations' => collect($relations)->map(fn ($item, $key) => is_int($key) ? $item : $key)->implode(','),
			'orderBy'   => 'lft',
			'limit'     => $categoriesLimit,
			'locale'    => $locale,
		];
		
		// Get Categories
		$cats = caching()->remember(Category::class, $cacheParams, function () use ($relations, $categoriesLimit) {
			return Category::roots()
				->with($relations)
				->orderBy('lft')
				->take($categoriesLimit)
				->get();
		});
		$cats = collect($cats)->keyBy('id');
		$data['cats'] = $cats;
		
		// Cache Parameters
		$cacheParams = [
			'action'      => 'get.cities',
			'country'     => config('country.code'),
			'orderByDesc' => 'population',
			'orderBy'     => 'name',
			'limit'       => $citiesLimit,
		];
		
		// Get Cities
		$cities = caching()->remember(City::class, $cacheParams, function () use ($citiesLimit) {
			return City::query()
				->inCountry()
				->take($citiesLimit)
				->orderByDesc('population')
				->orderBy('name')
				->get();
		});
		$data['cities'] = $cities;
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('sitemap');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return view('front.sitemap.index', $data);
	}
}
