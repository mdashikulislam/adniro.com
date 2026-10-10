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

use App\Http\Controllers\Web\Front\FrontController;
use App\Http\Controllers\Web\Front\Search\Traits\MetaTagTrait;
use App\Http\Controllers\Web\Front\Search\Traits\TitleTrait;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BaseController extends FrontController
{
	use MetaTagTrait, TitleTrait;
	
	protected PostService $postService;
	
	/**
	 * @param \App\Services\PostService $postService
	 * @param \Illuminate\Http\Request $request
	 */
	public function __construct(PostService $postService, Request $request)
	{
		parent::__construct();
		
		$this->postService = $postService;
		$this->request = $request;
	}
	
	/**
	 * @param array|null $sidebar
	 * @return void
	 */
	protected function bindSidebarVariables(?array $sidebar = []): void
	{
		if (!empty($sidebar)) {
			foreach ($sidebar as $key => $value) {
				view()->share($key, $value);
			}
		}
	}
	
	/**
	 * Set the Open Graph info
	 *
	 * @param $og
	 * @param $title
	 * @param $description
	 * @param array|null $apiExtra
	 * @return void
	 */
	protected function setOgInfo($og, $title, $description, ?array $apiExtra = null): void
	{
		$og->title($title)->description($description)->type('website');
		
		// If listings found, then remove the fallback image
		$doesListingsFound = (is_array($apiExtra) && (int)data_get($apiExtra, 'count.0') > 0);
		if ($doesListingsFound) {
			if ($og->has('image')) {
				$og->forget('image')->forget('image:width')->forget('image:height');
			}
		}
		
		view()->share('og', $og);
	}
	
	/**
	 * URL rules of the listings result pages, for search engines:
	 * - A page reached through another URL than its real one (wrong city slug, other letter case,
	 *   subcategory without its parent...) is permanently redirected to the real URL.
	 * - A page number beyond the last page is a 404 (instead of an empty "soft 404" page).
	 * - The canonical URL is the real URL, with the page number when it's greater than 1
	 *   (other query string parameters like filters or tracking ones are left out).
	 *
	 * @param string|null $expectedUrl The page's real URL (built with urlGen), null to skip the redirection
	 * @param mixed $apiResult
	 * @return \Illuminate\Http\RedirectResponse|null
	 */
	protected function applySeoUrlRules(?string $expectedUrl, mixed $apiResult): ?RedirectResponse
	{
		$requestPath = trim(rawurldecode(request()->path()), '/');
		$expectedPath = $requestPath;
		if (!empty($expectedUrl)) {
			$expectedPath = trim(rawurldecode((string)parse_url($expectedUrl, PHP_URL_PATH)), '/');
		}
		
		// The path compared decoded, but output percent-encoded (spaces in tags, non-ASCII slugs...)
		$encodedPath = implode('/', array_map('rawurlencode', explode('/', $expectedPath)));
		
		if ($expectedPath !== $requestPath) {
			$url = url($encodedPath);
			$queryString = request()->getQueryString();
			if (!empty($queryString)) {
				$url .= '?' . $queryString;
			}
			
			return redirect()->to($url, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$page = request()->query('page');
		$currentPage = 1;
		if (!is_null($page) && $page !== '') {
			abort_unless(is_string($page) && ctype_digit($page) && (int)$page >= 1, 404);
			$currentPage = (int)$page;
		}
		$lastPage = max((int)data_get($apiResult, 'meta.last_page', 1), 1);
		abort_if($currentPage > $lastPage, 404);
		
		// Filtered pages are "noindex" when the "no_index_filters_orders" option is enabled:
		// no canonical URL then (noindex & a canonical URL pointing elsewhere are conflicting signals)
		if (config('settings.seo.no_index_filters_orders') && $this->hasFilterParameters()) {
			view()->share('hideCanonical', true);
			
			return null;
		}
		
		$canonicalUrl = url($encodedPath);
		if ($currentPage > 1) {
			$canonicalUrl .= '?page=' . $currentPage;
		}
		view()->share('canonicalUrl', $canonicalUrl);
		
		return null;
	}
	
	/**
	 * Is the results page filtered or reordered (query string parameters other than the page number)?
	 * Tracking parameters (utm_*, gclid...) don't count: a page reached from a campaign link
	 * is the same page (its canonical URL is the page without them).
	 *
	 * @return bool
	 */
	protected function hasFilterParameters(): bool
	{
		$trackingParameters = ['gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'yclid', 'ttclid', 'twclid', 'li_fat_id', 'mc_cid', 'mc_eid', 'ref', 'srsltid'];
		
		$parameters = collect(request()->query())
			->except(array_merge(['page'], $trackingParameters))
			->reject(fn ($value, $key) => str_starts_with((string)$key, 'utm_'));
		
		return $parameters->isNotEmpty();
	}
}
