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

namespace App\Http\Controllers\Web\Front\Post;

use App\Helpers\Services\Referrer;
use App\Http\Controllers\Web\Front\FrontController;
use App\Http\Requests\Front\ReportRequest;
use App\Services\ContactService;
use App\Services\PostService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Throwable;

class ReportController extends FrontController
{
	protected PostService $postService;
	protected ContactService $contactService;
	
	/**
	 * @param \App\Services\PostService $postService
	 * @param \App\Services\ContactService $contactService
	 */
	public function __construct(PostService $postService, ContactService $contactService)
	{
		parent::__construct();
		
		$this->postService = $postService;
		$this->contactService = $contactService;
		
		$this->commonQueries();
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = [];
		
		$isAuthRequiredToReportAbuse = (config('settings.listing_page.auth_required_to_report_abuse') == '1');
		if ($isAuthRequiredToReportAbuse) {
			$array[] = new Middleware('auth', only: ['showForm', 'postForm']);
		}
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	public function commonQueries(): void
	{
		// Get Report abuse types
		$reportTypes = Referrer::getReportTypes();
		view()->share('reportTypes', $reportTypes);
	}
	
	/**
	 * @param $postId
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showForm($postId): View
	{
		$postId = hashId($postId, true) ?? $postId;
		
		// Get the post
		$data = getServiceData($this->postService->getEntry($postId));
		
		$message = data_get($data, 'message');
		$post = data_get($data, 'result');
		
		abort_if(empty($post), 404, $message ?? t('post_not_found'));
		
		// Meta Tags
		$title = t('Report for', ['title' => mb_ucfirst(data_get($post, 'title'))]);
		$description = t('Send a report for', ['title' => mb_ucfirst(data_get($post, 'title'))]);
		
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		
		// Open Graph
		try {
			$this->og->title($title)->description($description);
		} catch (Throwable $e) {
		}
		view()->share('og', $this->og);
		
		// SEO: noindex
		$noIndexListingsReportPages = (
			config('settings.seo.no_index_listing_report')
			&& routeActionHas(static::class)
		);
		
		return view('front.post.report', compact('title', 'post', 'noIndexListingsReportPages'));
	}
	
	/**
	 * @param $postId
	 * @param \App\Http\Requests\Front\ReportRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm($postId, ReportRequest $request): RedirectResponse
	{
		$postId = hashId($postId, true) ?? $postId;
		
		// Submit the report form
		$data = getServiceData($this->contactService->submitReportForm($postId, $request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back()->withInput();
		}
		
		$post = data_get($data, 'extra.post');
		
		if (!empty($post)) {
			return redirect()->to(urlGen()->post($post));
		} else {
			return redirect()->to('/');
		}
	}
}
