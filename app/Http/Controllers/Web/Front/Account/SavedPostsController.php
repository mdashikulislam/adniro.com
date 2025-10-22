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

use App\Services\SavedPostService;
use App\Services\UserService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class SavedPostsController extends AccountBaseController
{
	protected SavedPostService $savedPostService;
	
	/**
	 * @param \App\Services\UserService $userService
	 * @param \App\Services\SavedPostService $savedPostService
	 */
	public function __construct(UserService $userService, SavedPostService $savedPostService)
	{
		parent::__construct($userService);
		
		$this->savedPostService = $savedPostService;
	}
	
	/**
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function toggle(Request $request): JsonResponse
	{
		// Store saved post
		$data = getServiceData($this->savedPostService->store($request));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message');
		
		// Error Found
		if (!data_get($data, 'success')) {
			$message = $message ?? t('unknown_error');
			
			return ajaxResponse()->json(['message' => $message], $status);
		}
		
		// Get entry resource
		$savedPost = data_get($data, 'result');
		
		// AJAX response data
		$result = [
			'isLoggedUser' => !($status == 401), // No longer used. Will be removed.
			'postId'       => $request->input('post_id'),
			'isSaved'      => !empty($savedPost),
			'message'      => $message,
			'loginUrl'     => urlGen()->signIn(), // No longer used. Will be removed.
		];
		
		return ajaxResponse()->json($result, $status);
	}
	
	/**
	 * Get the user's saved posts
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		// Get Saved Posts
		$queryParams = [
			'embed' => 'post,city,currency,user',
			'sort'  => 'created_at',
		];
		$data = getServiceData($this->savedPostService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		// Transform the API result
		$apiResult = collect($apiResult)
			->map(function ($item, $key) {
				if ($key == 'data' && is_array($item)) {
					$newItem = [];
					foreach ($item as $savedPost) {
						$newItem[$savedPost['id']] = $savedPost['post'];
					}
					$item = $newItem;
				}
				
				return $item;
			})->toArray();
		
		// Meta Tags
		MetaTag::set('title', t('my_favourite_listings'));
		MetaTag::set('description', t('my_favourite_listings_on', ['appName' => config('settings.app.name')]));
		
		// Breadcrumb
		BreadcrumbFacade::add(t('favourite_listings'));
		
		return view('front.account.posts', compact('apiResult', 'apiMessage'));
	}
	
	/**
	 * @param null $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function destroy($id = null): RedirectResponse
	{
		// Get entries ID(s)
		$ids = getSelectedEntryIds($id, request()->input('entries'), asString: true);
		
		// Delete the entry
		$data = getServiceData($this->savedPostService->destroy($ids));
		
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
