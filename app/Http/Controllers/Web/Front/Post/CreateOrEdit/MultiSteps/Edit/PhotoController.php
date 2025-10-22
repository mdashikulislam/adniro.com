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

namespace App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Edit;

use App\Http\Controllers\Web\Front\Post\CreateOrEdit\Traits\PricingPageUrlTrait;
use App\Http\Requests\Front\PhotoRequest;
use App\Services\Payment\RetrievePackageFeatures;
use App\Services\PictureService;
use App\Services\PostService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class PhotoController extends BaseController
{
	use RetrievePackageFeatures;
	use PricingPageUrlTrait;
	
	protected PictureService $pictureService;
	
	public $package = null;
	
	/**
	 * @param \App\Services\PostService $postService
	 * @param \App\Services\PictureService $pictureService
	 */
	public function __construct(PostService $postService, PictureService $pictureService)
	{
		parent::__construct($postService);
		
		$this->pictureService = $pictureService;
		
		$this->commonQueries();
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = [
			new Middleware('only.ajax', only: ['delete']),
		];
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	private function commonQueries(): void
	{
		// Get the selected package
		$this->package = $this->getSelectedPackage();
		view()->share('selectedPackage', $this->package);
		
		// Set the Package's pictures limit
		$this->getCurrentActivePaymentInfo(null, $this->package);
	}
	
	/**
	 * Show the listing's pictures form
	 *
	 * @param $postId
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showForm($postId): View
	{
		$data = [];
		
		// Get Post
		$post = null;
		if (auth()->check()) {
			// Get the post
			$queryParams = [
				'embed'               => 'pictures',
				'countryCode'         => config('country.code'),
				'unactivatedIncluded' => 1,
				'belongLoggedUser'    => 1, // Logged user required
				'noCache'             => 1,
			];
			$data = getServiceData($this->postService->getEntry($postId, $queryParams));
			
			$apiMessage = data_get($data, 'message');
			$post = data_get($data, 'result');
		}
		
		if (empty($post)) {
			abort(404, t('post_not_found'));
		}
		
		view()->share('post', $post);
		$this->shareNavItems($post);
		
		// Set the Package's pictures limit
		if (!empty($this->package)) {
			$this->getCurrentActivePaymentInfo(null, $this->package);
		} else {
			// Share the post's current active payment info (If exists)
			// & Set the Package's pictures limit
			$this->getCurrentActivePaymentInfo($post);
		}
		
		// Meta Tags
		MetaTag::set('title', t('update_my_listing'));
		MetaTag::set('description', t('update_my_listing'));
		
		// Get steps URLs & labels
		$previousStepUrl = urlGen()->editPost($post);
		$previousStepUrl = urlBuilder($previousStepUrl)->setParameters(request()->only(['packageId']))->toString();
		$previousStepLabel = '<i class="bi bi-chevron-left"></i>  ' . t('Previous');
		$formActionUrl = request()->fullUrl();
		if (
			$this->countPackages > 0
			&& $this->countPaymentMethods > 0
		) {
			$nextStepUrl = urlGen()->editPostPayment($post);
			$nextStepUrl = urlBuilder($nextStepUrl)->setParameters(request()->only(['packageId']))->toString();
			$nextStepLabel = t('Next') . '  <i class="bi bi-chevron-right"></i>';
		} else {
			$nextStepUrl = urlGen()->post($post);
			$nextStepLabel = t('Finish');
		}
		
		// Share steps URLs & label variables
		view()->share('previousStepUrl', $previousStepUrl);
		view()->share('previousStepLabel', $previousStepLabel);
		view()->share('formActionUrl', $formActionUrl);
		view()->share('nextStepUrl', $nextStepUrl);
		view()->share('nextStepLabel', $nextStepLabel);
		
		return view('front.post.createOrEdit.multiSteps.edit.photos', $data);
	}
	
	/**
	 * Update the listing's pictures
	 *
	 * @param $postId
	 * @param \App\Http\Requests\Front\PhotoRequest $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function postForm($postId, PhotoRequest $request): JsonResponse|RedirectResponse
	{
		// Add required data in the request for API
		$inputArray = [
			'count_packages'        => $this->countPackages ?? 0,
			'count_payment_methods' => $this->countPaymentMethods ?? 0,
			'post_id'               => $postId,
		];
		$request->merge($inputArray);
		
		// Store picture(s)
		$data = getServiceData($this->pictureService->store($request));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			
			// AJAX Response
			if (isFromAjax()) {
				return ajaxResponse()->json(['error' => $message], $status);
			}
			
			flash($message)->error();
			$previousUrl = url()->previous();
			
			return redirect()->to($previousUrl)->withInput();
		}
		
		$post = data_get($data, 'extra.post.result');
		
		// Get Next URL
		if (data_get($data, 'extra.steps.payment')) {
			$nextUrl = urlGen()->editPostPayment($post);
		} else {
			$nextUrl = urlGen()->post($post);
		}
		$nextStepLabel = data_get($data, 'extra.nextStepLabel');
		
		view()->share('nextStepUrl', $nextUrl);
		view()->share('nextStepLabel', $nextStepLabel);
		
		// AJAX Response
		if (isFromAjax()) {
			$data = data_get($data, 'extra.fileInput');
			
			return ajaxResponse()->json($data);
		}
		
		// Non AJAX Response
		return redirect()->to($nextUrl);
	}
	
	/**
	 * Delete a listing picture
	 *
	 * @param $postId
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function delete($postId, $id): JsonResponse|RedirectResponse
	{
		// Delete the picture
		$inputParams = [
			'post_id' => $postId,
		];
		$data = getServiceData($this->pictureService->destroy($id, $inputParams));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message');
		
		$result = ['status' => 0];
		
		// Notification Message
		if (data_get($data, 'success')) {
			if (isFromAjax()) {
				$result['status'] = 1;
				$result['message'] = $message;
				
				return ajaxResponse()->json($result);
			} else {
				flash($message)->success();
			}
		} else {
			$message = $message ?? t('unknown_error');
			if (isFromAjax()) {
				$result['error'] = $message;
				
				return ajaxResponse()->json($result, $status);
			} else {
				flash($message)->error();
			}
		}
		
		return redirect()->back();
	}
	
	/**
	 * Reorder the listing's pictures
	 *
	 * @param $postId
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reorder($postId, Request $request): JsonResponse
	{
		$httpStatus = 200;
		$result = ['status' => 0, 'message' => null];
		
		$params = $request->input('params');
		$stack = $params['stack'] ?? [];
		
		if (is_array($stack) && count($stack) > 0) {
			$stack = collect($stack)->reject(fn ($item) => empty($item))->toArray();
			
			$body = collect($stack)
				->map(function ($item, $position) {
					return [
						'id'       => $item['key'] ?? null,
						'position' => $position,
					];
				})
				->reject(fn ($item) => empty($item['id']))
				->toArray();
			
			if (!empty($body)) {
				// Reorder post's pictures
				$inputParams = [
					'post_id' => $postId,
					'body'    => json_encode($body),
				];
				$data = getServiceData($this->pictureService->reorder($inputParams));
				
				// Parsing the API response
				$message = data_get($data, 'message');
				
				if (data_get($data, 'success')) {
					$result = [
						'status'  => 1,
						'message' => $message,
					];
				} else {
					$message = $message ?? t('unknown_error');
					$result['error'] = $message;
					$httpStatus = (int)data_get($data, 'status', 400);
				}
			}
		}
		
		return ajaxResponse()->json($result, $httpStatus);
	}
}
