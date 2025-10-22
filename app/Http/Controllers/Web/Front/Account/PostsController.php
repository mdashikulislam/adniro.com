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

use App\Http\Requests\Front\PostRequest\LimitationCompliance;
use App\Models\Package;
use App\Services\PostService;
use App\Services\UserService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class PostsController extends AccountBaseController
{
	protected PostService $postService;
	
	/**
	 * @param \App\Services\UserService $userService
	 * @param \App\Services\PostService $postService
	 */
	public function __construct(UserService $userService, PostService $postService)
	{
		parent::__construct($userService);
		
		$this->postService = $postService;
		
		// Count Promotion Packages
		$countPromotionPackages = Package::query()->promotion()->applyCurrency()->count();
		view()->share('countPromotionPackages', $countPromotionPackages);
	}
	
	/**
	 * Get activated posts (i.e. Online posts)
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function onlinePosts()
	{
		// Get Posts
		$queryParams = [
			'embed'            => 'category,postType,city,currency,payment,package',
			'belongLoggedUser' => true,
			'sort'             => 'created_at',
		];
		$data = getServiceData($this->postService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		$appName = config('settings.app.name', 'Site Name');
		$title = t('my_listings') . ' - ' . $appName;
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', t('my_listings_on', ['appName' => config('settings.app.name')]));
		
		// Breadcrumb
		BreadcrumbFacade::add(t('my_listings'));
		
		return view('front.account.posts', compact('apiResult', 'apiMessage'));
	}
	
	/**
	 * Take the post offline
	 *
	 * @param $postId
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function takePostOffline($postId): RedirectResponse
	{
		// Put the post offline
		$data = getServiceData($this->postService->offline($postId));
		
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
	
	/**
	 * Get archived posts
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function archivedPosts()
	{
		// Get Posts
		$queryParams = [
			'embed'            => 'category,postType,city,currency,payment',
			'belongLoggedUser' => true,
			'archived'         => true,
			'sort'             => 'created_at',
		];
		$data = getServiceData($this->postService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		// Meta Tags
		MetaTag::set('title', t('my_archived_listings'));
		MetaTag::set('description', t('my_archived_listings_on', ['appName' => config('settings.app.name')]));
		
		// Breadcrumb
		BreadcrumbFacade::add(t('archived_listings'));
		
		return view('front.account.posts', compact('apiResult', 'apiMessage'));
	}
	
	/**
	 * Repost a post
	 *
	 * @param $postId
	 * @param \App\Http\Requests\Front\PostRequest\LimitationCompliance $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function repostPost($postId, LimitationCompliance $request): RedirectResponse
	{
		// Repost the post
		$data = getServiceData($this->postService->repost($postId, $request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back();
		}
		
		// Get User Resource
		$post = data_get($data, 'result');
		$postUrl = urlGen()->post($post);
		
		return redirect()->to($postUrl);
	}
	
	/**
	 * Get pending approval posts
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function pendingApprovalPosts()
	{
		// Get Posts
		$queryParams = [
			'embed'            => 'category,postType,city,currency,payment',
			'belongLoggedUser' => true,
			'pendingApproval'  => true,
			'sort'             => 'created_at',
		];
		$data = getServiceData($this->postService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		// Meta Tags
		MetaTag::set('title', t('my_pending_approval_listings'));
		MetaTag::set('description', t('my_pending_approval_listings_on', ['appName' => config('settings.app.name')]));
		
		// Breadcrumb
		BreadcrumbFacade::add(t('pending_approval'));
		
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
		$data = getServiceData($this->postService->destroy($ids));
		
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
