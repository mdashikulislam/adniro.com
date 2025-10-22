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

namespace App\Http\Controllers\Web\Front\Account;

use App\Services\SavedSearchService;
use App\Services\UserService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class SavedSearchesController extends AccountBaseController
{
	protected SavedSearchService $savedSearchService;
	
	/**
	 * @param \App\Services\UserService $userService
	 * @param \App\Services\SavedSearchService $savedSearchService
	 */
	public function __construct(UserService $userService, SavedSearchService $savedSearchService)
	{
		parent::__construct($userService);
		
		$this->savedSearchService = $savedSearchService;
	}
	
	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(Request $request): JsonResponse
	{
		// Store saved search
		$data = getServiceData($this->savedSearchService->store($request));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message');
		
		// Error Found
		if (!data_get($data, 'success')) {
			$message = $message ?? t('unknown_error');
			
			return ajaxResponse()->json(['message' => $message], $status);
		}
		
		$queryUrl = $request->input('search_url');
		
		// Validate data extraction
		$query = null;
		if (!empty($queryUrl)) {
			$tmp = parse_url($queryUrl);
			$query = $tmp['query'] ?? null;
		}
		if (empty($query)) {
			$errorMsg = 'The "query" parameter cannot not be extracted.';
			
			return ajaxResponse()->json(['message' => $errorMsg], 400);
		}
		
		// Get entry resource
		$savedSearch = data_get($data, 'result');
		
		// AJAX response data
		$result = [
			'isLoggedUser' => !($status == 401), // No longer used. Will be removed.
			'query'        => $query,
			'isSaved'      => !empty($savedSearch),
			'message'      => $message,
			'loginUrl'     => urlGen()->signIn(), // No longer used. Will be removed.
		];
		
		return ajaxResponse()->json($result, $status);
	}
	
	/**
	 * Get saved searches
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		// Get saved searches
		$queryParams = [
			'embed' => 'user,country,postType,category,city',
			'sort'  => 'created_at',
		];
		$data = getServiceData($this->savedSearchService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		$appName = config('settings.app.name', 'Site Name');
		$title = t('my_saved_search') . ' - ' . $appName;
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', t('my_saved_search_on', ['appName' => config('settings.app.name')]));
		
		// Breadcrumb
		BreadcrumbFacade::add(t('saved_searches'));
		
		return view('front.account.saved-searches.index', compact('apiMessage', 'apiResult'));
	}
	
	/**
	 * Get a saved search
	 *
	 * @param $id
	 * @return \Illuminate\Contracts\View\View
	 */
	public function show($id)
	{
		// Get the saved search
		$queryParams = [
			'embed' => 'user,country,postType,category,city',
			'sort'  => 'created_at',
		];
		$data = getServiceData($this->savedSearchService->getEntry($id, $queryParams));
		
		$message = data_get($data, 'message');
		$savedSearch = data_get($data, 'result');
		
		abort_if(empty($savedSearch), 404, $message ?? t('saved_search_not_found'));
		
		$apiMessagePosts = data_get($savedSearch, 'posts.message');
		$apiResultPosts = data_get($savedSearch, 'posts.result');
		$apiExtraPosts = data_get($savedSearch, 'posts.extra');
		
		// Meta Tags
		MetaTag::set('title', t('my_saved_search'));
		MetaTag::set('description', t('my_saved_search_on', ['appName' => config('settings.app.name')]));
		
		return view(
			'front.account.saved-searches.show',
			compact('savedSearch', 'apiMessagePosts', 'apiResultPosts', 'apiExtraPosts')
		);
	}
	
	/**
	 * Delete a saved search
	 *
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function destroy($id = null): RedirectResponse
	{
		// Get entries ID(s)
		$ids = getSelectedEntryIds($id, request()->input('entries'), asString: true);
		
		// Delete the saved search
		$data = getServiceData($this->savedSearchService->destroy($ids));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
		}
		
		return redirect()->back();
	}
}
