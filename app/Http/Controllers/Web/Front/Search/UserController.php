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

use App\Models\User;
use App\Services\SellerService;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Throwable;

class UserController extends BaseController
{
	public ?array $user;
	
	/**
	 * Seller's listings page: /user/{username}
	 * (The seller's live listings from every country)
	 *
	 * @param string $username
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function profile(string $username)
	{
		// Username of a user, or of the poster of listings published without account
		$seller = SellerService::find($username);
		abort_if(empty($seller), 404, t('user_not_found'));
		
		return $this->searchByUserId(null, $username, $seller);
	}
	
	/**
	 * Former URL: /{countryCode}/users/{id}/ads
	 * Permanently redirected to /user/{username}.
	 * It's still served as it is for a user without username (that should no longer exist:
	 * the usernames are generated on saving, see UserObserver & the "users:generate-usernames" command).
	 *
	 * @param string|null $countryCode
	 * @param int|null $userId
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function index(?string $countryCode, ?int $userId = null)
	{
		// Check if the multi-country site option is enabled
		if (!isMultiCountriesUrlsEnabled()) {
			$userId = $countryCode;
		}
		
		$user = User::query()->withoutGlobalScopes()->where('id', (int)$userId)->first(['id', 'username']);
		abort_if(empty($user), 404, t('user_not_found'));
		
		if (!empty($user->username)) {
			return $this->redirectToProfile($user->username);
		}
		
		return $this->searchByUserId($userId, null, SellerService::findByUserId((int)$userId));
	}
	
	/**
	 * Former URL: /{countryCode}/profile/{username}
	 * Permanently redirected to /user/{username}.
	 *
	 * @param string|null $countryCode
	 * @param string|null $username
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function legacyProfile(?string $countryCode, ?string $username = null): RedirectResponse
	{
		// Check if the multi-country site option is enabled
		if (!isMultiCountriesUrlsEnabled()) {
			$username = $countryCode;
		}
		
		return $this->redirectToProfile((string)$username);
	}
	
	/**
	 * @param string $username
	 * @return \Illuminate\Http\RedirectResponse
	 */
	private function redirectToProfile(string $username): RedirectResponse
	{
		$url = urlGen()->user(['username' => $username]);
		$queryString = request()->getQueryString();
		if (!empty($queryString)) {
			$url .= '?' . $queryString;
		}
		
		return redirect()->to($url, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
	}
	
	/**
	 * @param int|null $userId
	 * @param string|null $username
	 * @return \Illuminate\Contracts\View\View
	 */
	private function searchByUserId(?int $userId = null, ?string $username = null, ?array $seller = null)
	{
		// Get Posts
		$queryParams = [
			'op'       => 'search',
			'userId'   => trim($userId),
			'username' => trim($username),
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = getServiceData($this->postService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		$apiExtra = data_get($data, 'extra');
		$preSearch = data_get($apiExtra, 'preSearch');
		
		// A seller without live listing is still the page's subject (title, real URL)
		if (empty(data_get($preSearch, 'user')) && !empty($seller['username'])) {
			$preSearch = (array)$preSearch;
			$preSearch['user'] = ['name' => $seller['name'] ?? null, 'username' => $seller['username']];
		}
		
		// Real URL (lowercase tag / the seller's username URL), out-of-range pages & canonical URL
		// (see BaseController::applySeoUrlRules())
		$seoRedirect = $this->applySeoUrlRules(!empty(data_get($preSearch, 'user')) ? urlGen()->user(data_get($preSearch, 'user')) : null, $apiResult);
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
		$noIndexUsersByIdPages = false;
		$noIndexUsersByUsernamePages = false;
		if (!empty($userId)) {
			$noIndexUsersByIdPages = (
				config('settings.seo.no_index_users')
				&& routeActionHas('Search\UserController@index')
			);
		}
		if (!empty($username)) {
			$noIndexUsersByUsernamePages = (
				config('settings.seo.no_index_users_username')
				&& routeActionHas('Search\UserController@profile')
			);
		}
		// Filters (and Orders) on Listings Pages (Except Pagination)
		$noIndexFiltersOnEntriesPages = (
			config('settings.seo.no_index_filters_orders')
			&& routeActionHas('Search\\')
			&& $this->hasFilterParameters()
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
		
		// Seller's page data
		$authUserId = auth()->check() ? (int)auth()->id() : null;
		$sellerStats = SellerService::stats((array)$seller, (int)data_get($apiResult, 'meta.total', 0));
		$isOwnProfile = (!empty($authUserId) && !empty($seller['user_id']) && $seller['user_id'] == $authUserId);
		$isFollowing = SellerService::isFollowedBy((array)$seller, $authUserId);
		$sellerCountryName = SellerService::countryName((array)$seller);
		
		return view(
			'front.user.profile',
			compact(
				'seller',
				'sellerStats',
				'isOwnProfile',
				'isFollowing',
				'sellerCountryName',
				'apiMessage',
				'apiResult',
				'apiExtra',
				'noIndexUsersByIdPages',
				'noIndexUsersByUsernamePages',
				'noIndexFiltersOnEntriesPages',
				'noIndexNoResultPages',
				'noIndexLowContentPages'
			)
		);
	}
}
