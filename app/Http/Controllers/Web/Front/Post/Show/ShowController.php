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

namespace App\Http\Controllers\Web\Front\Post\Show;

use App\Http\Controllers\Web\Front\FrontController;
use App\Http\Controllers\Web\Front\Post\Show\Traits\CatBreadcrumb;
use App\Http\Controllers\Web\Front\Post\Show\Traits\ReviewsPlugin;
use App\Http\Controllers\Web\Front\Post\Show\Traits\SimilarPosts;
use App\Models\Package;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Larapen\TextToImage\Facades\TextToImage;
use Throwable;

class ShowController extends FrontController
{
	use CatBreadcrumb, SimilarPosts, ReviewsPlugin;
	
	protected PostService $postService;
	
	/**
	 * @param \App\Services\PostService $postService
	 */
	public function __construct(PostService $postService)
	{
		parent::__construct();
		
		$this->postService = $postService;
		
		$this->commonQueries();
	}
	
	/**
	 * @return void
	 */
	public function commonQueries(): void
	{
		// Cache Parameters
		$cacheParams = [
			'action'        => 'count.packages',
			'applyCurrency' => true,
		];
		
		// Count Packages
		$countPackages = caching()->remember(Package::class, $cacheParams, function () {
			return Package::applyCurrency()->count();
		});
		
		view()->share('countPackages', $countPackages);
		
		// Count Payment Methods
		view()->share('countPaymentMethods', $this->countPaymentMethods);
	}
	
	/**
	 * Show the Post's Details.
	 *
	 * @param $postId
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function index($postId)
	{
		// Get and Check the Controller's Method Parameters
		$parameters = request()->route()->parameters();
		
		// Check if the Listing's ID key exists
		$idKey = array_key_exists('hashableId', $parameters) ? 'hashableId' : 'id';
		$idKeyDoesNotExist = (
			empty($parameters[$idKey])
			|| (!isHashedId($parameters[$idKey]) && !is_numeric($parameters[$idKey]))
		);
		
		// Show 404 error if the Listing's ID key cannot be found
		abort_if($idKeyDoesNotExist, 404, "The listing's ID key cannot be handled.");
		
		// Set the Parameters
		$postId = $parameters[$idKey];
		$slug = $parameters['slug'] ?? null;
		
		// Forcing redirection 301 for hashed (or non-hashed) ID to update links in search engine indexes
		if (config('settings.seo.listing_hashed_id_seo_redirection')) {
			if (config('settings.seo.listing_hashed_id_enabled') && !isHashedId($postId) && is_numeric($postId)) {
				// Don't lose important notification, so we need to persist your flash data for the request (the redirect request)
				request()->session()->reflash();
				
				$uri = urlGen()->postPathBasic(hashId($postId), $slug);
				
				return redirect()->to($uri, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
			}
			if (!config('settings.seo.listing_hashed_id_enabled') && isHashedId($postId) && !is_numeric($postId)) {
				// Don't lose important notification, so we need to persist your flash data for the request (the redirect request)
				request()->session()->reflash();
				
				$uri = urlGen()->postPathBasic(hashId($postId, true), $slug);
				
				return redirect()->to($uri, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
			}
		}
		
		// Decode Hashed ID
		$postId = hashId($postId, true) ?? $postId;
		
		// Get the post details
		$queryParams = [
			'detailed' => 1,
		];
		if (config('plugins.reviews.installed')) {
			$queryParams['embed'] = 'userRating,countUserRatings';
		}
		$data = getServiceData($this->postService->getEntry($postId, $queryParams));
		
		$message = data_get($data, 'message');
		$post = data_get($data, 'result');
		$customFields = data_get($data, 'extra.fieldsValues');
		
		// Listing isn't found
		abort_if(empty($post), 404, $message ?? t('post_not_found'));
		
		session()->put('isPostVisited', $postId);
		
		// Get post's pictures
		$pictures = (array)data_get($post, 'pictures');
		
		// Get possible post's registered Author (User)
		$user = data_get($post, 'user');
		
		// Get post's user decision about comments activation
		$commentsAreDisabledByUser = (data_get($user, 'disable_comments') == 1);
		
		// Category Breadcrumb
		$catBreadcrumb = $this->getCatBreadcrumb(data_get($post, 'category'), 1);
		
		// GET SIMILAR POSTS
		$widgetSimilarPosts = $this->similarPosts(data_get($post, 'id'));
		
		$isFromPostDetails = routeActionHas('Post\ShowController');
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('listingDetails');
		$title = str_replace('{ad.title}', data_get($post, 'title'), $title);
		$title = str_replace('{location.name}', data_get($post, 'city.name'), $title);
		$description = str_replace('{ad.description}', str(normalizeWhitespace(strip_tags(data_get($post, 'description'))))->limit(200), $description);
		$keywords = str_replace('{ad.tags}', str_replace(',', ', ', @implode(',', data_get($post, 'tags'))), $keywords);
		
		$title = removeUnmatchedPatterns($title);
		$description = removeUnmatchedPatterns($description);
		$keywords = removeUnmatchedPatterns($keywords);
		
		// Fallback
		if (empty($title)) {
			$title = data_get($post, 'title') . ', ' . data_get($post, 'city.name');
		}
		if (empty($description)) {
			$description = str(normalizeWhitespace(strip_tags(data_get($post, 'description'))))->limit(200);
		}
		
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		MetaTag::set('keywords', $keywords);
		
		// Open Graph
		try {
			$this->og->title($title)->description($description)->type('article');
			if (!empty($pictures)) {
				if ($this->og->has('image')) {
					$this->og->forget('image')->forget('image:width')->forget('image:height');
				}
				
				foreach ($pictures as $picture) {
					$pictureUrl = data_get($picture, 'url.large');
					if (!empty($pictureUrl)) {
						$this->og->image($pictureUrl, [
							'width'  => (int)config('settings.social_share.og_image_width', 1200),
							'height' => (int)config('settings.social_share.og_image_height', 630),
						]);
					}
				}
			}
		} catch (Throwable $e) {
		}
		view()->share('og', $this->og);
		
		// Reviews Plugin Data
		if (config('plugins.reviews.installed')) {
			$reviewsApiResult = $this->getReviews(data_get($post, 'id'));
			view()->share('reviewsApiResult', $reviewsApiResult);
		}
		
		return view(
			'front.post.show.index',
			compact(
				'post',
				'pictures',
				'user',
				'catBreadcrumb',
				'customFields',
				'commentsAreDisabledByUser',
				'widgetSimilarPosts',
				'isFromPostDetails'
			)
		);
	}
	
	/**
	 * Get post's phone number
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getPhone(Request $request): JsonResponse
	{
		$postId = $request->input('post_id', 0);
		
		// Get Post
		$queryParams = [
			'unactivatedIncluded' => true,
		];
		$data = getServiceData($this->postService->getEntry($postId, $queryParams));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message');
		
		// Error Found
		if (!data_get($data, 'success')) {
			$message = $message ?? t('unknown_error');
			
			return ajaxResponse()->json(['message' => $message], $status);
		}
		
		// Get entry resource
		$post = data_get($data, 'result');
		
		// Get the phone
		$phone = data_get($post, 'phone');
		$phoneIntl = data_get($post, 'phone_intl');
		$phoneModal = $phoneIntl;
		$phoneLink = 'tel:' . $phone;
		
		$phoneNumberCanBeConvertedInToImg = (config('settings.listing_page.convert_phone_number_to_img') == '1');
		$securityTipsCanBeShown = (config('settings.listing_page.show_security_tips') == '1');
		
		if ($phoneNumberCanBeConvertedInToImg) {
			try {
				$phone = TextToImage::make($phoneIntl, config('larapen.core.textToImage'));
			} catch (Throwable $e) {
				$phone = data_get($post, 'phone_intl');
			}
		}
		
		if ($securityTipsCanBeShown) {
			$phone = t('phone_number');
		}
		
		$data = [
			'phone'      => $phone,
			'phoneModal' => $phoneModal,
			'link'       => $phoneLink,
		];
		
		return ajaxResponse()->json($data);
	}
}
