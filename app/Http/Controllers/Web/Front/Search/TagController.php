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

namespace App\Http\Controllers\Web\Front\Search;

use Larapen\LaravelMetaTags\Facades\MetaTag;
use Throwable;

class TagController extends BaseController
{
	public ?string $tag;
	
	/**
	 * @param string|null $countryCode (Can be $tag or $countryCode)
	 * @param string|null $tag
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index(?string $countryCode, string $tag = null)
	{
		// Check if the multi-country site option is enabled
		if (!isMultiCountriesUrlsEnabled()) {
			$tag = $countryCode;
		}
		
		$this->tag = rawurldecode($tag);
		
		// Get Posts
		$queryParams = [
			'op'  => 'search',
			'tag' => $tag,
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = getServiceData($this->postService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		$apiExtra = data_get($data, 'extra');
		$preSearch = data_get($apiExtra, 'preSearch');
		
		// Real URL (lowercase tag / the seller's username URL), out-of-range pages & canonical URL
		// (see BaseController::applySeoUrlRules())
		$seoRedirect = $this->applySeoUrlRules(urlGen()->tag(mb_strtolower($this->tag)), $apiResult);
		if (!empty($seoRedirect)) {
			return $seoRedirect;
		}
		
		// Sidebar
		$this->bindSidebarVariables((array)data_get($apiExtra, 'sidebar'));
		
		// Get Titles
		$this->getBreadcrumb($preSearch);
		$this->getHtmlTitle($preSearch);
		
		// Meta Tags
		[$title, $description, $keywords] = $this->getMetaTag($preSearch);
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		MetaTag::set('keywords', $keywords);
		
		// Open Graph
		try {
			$this->og->title($title)->description($description)->type('website');
		} catch (Throwable $e) {
		}
		view()->share('og', $this->og);
		
		// SEO: noindex
		$noIndexTagsPages = (
			config('settings.seo.no_index_tags')
			&& routeActionHas('Search\TagController')
		);
		// Filters (and Orders) on Listings Pages (Except Pagination)
		$noIndexFiltersOnEntriesPages = (
			config('settings.seo.no_index_filters_orders')
			&& routeActionHas('Search\\')
			&& !empty(request()->except(['page']))
		);
		// "No result" Pages (Empty Searches Results Pages)
		$noIndexNoResultPages = (
			config('settings.seo.no_index_no_result')
			&& routeActionHas('Search\\')
			&& empty(data_get($apiResult, 'data'))
		);
		
		// Thin Pages (Fewer live listings than the indexing threshold), as on the category & city pages
		$noIndexLowContentPages = (
			(int)data_get($apiResult, 'meta.total', 0) < (int)config('seo.min_listings_to_index', 3)
		);
		
		return view(
			'front.search.results',
			compact(
				'apiMessage',
				'apiResult',
				'apiExtra',
				'noIndexTagsPages',
				'noIndexFiltersOnEntriesPages',
				'noIndexNoResultPages',
				'noIndexLowContentPages'
			)
		);
	}
}
