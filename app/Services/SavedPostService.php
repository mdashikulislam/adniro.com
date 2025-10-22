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

namespace App\Services;

use App\Helpers\Common\PaginationHelper;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\SavedPostResource;
use App\Models\SavedPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedPostService extends BaseService
{
	/**
	 * List saved listings
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		$perPage = getNumberOfItemsPerPage('saved_posts', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$countryCode = $params['countryCode'] ?? config('country.code');
		$sort = $params['sort'] ?? [];
		
		$savedPosts = SavedPost::query()
			->whereHas('post', fn ($query) => $query->inCountry($countryCode))
			->where('user_id', $authUser->getAuthIdentifier());
		
		if (in_array('user', $embed)) {
			$savedPosts->with([
				'user',
				'user.permissions',
			]);
		}
		
		if (in_array('post', $embed)) {
			$savedPosts->with([
				'post',
				'post.picture',
				// 'post.pictures',
				'post.city',
				'post.user',
				'post.user.permissions',
			]);
		}
		
		// Sorting
		$savedPosts = $this->applySorting($savedPosts, ['created_at'], $sort);
		
		$savedPosts = $savedPosts->paginate($perPage);
		$savedPosts = PaginationHelper::adjustSides($savedPosts);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$savedPosts = setPaginationBaseUrl($savedPosts);
		
		$collection = new EntityCollection(SavedPostResource::class, $savedPosts, $params);
		
		$message = ($savedPosts->count() <= 0) ? t('no_saved_posts_found') : null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Store/Delete saved listing
	 *
	 * Save a post/listing in favorite, or remove it from favorite.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(Request $request): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$data = [
			'success' => false,
			'result'  => null,
		];
		
		// Get the 'post_id' field
		$postId = $request->input('post_id');
		if (empty($postId)) {
			$data['message'] = 'The "post_id" field need to be filled.';
			
			return apiResponse()->json($data, 400);
		}
		
		$data['success'] = true;
		
		$savedPosts = SavedPost::where('user_id', $authUser->getAuthIdentifier())->where('post_id', $postId);
		if ($savedPosts->count() > 0) {
			$savedPosts = $savedPosts->get();
			
			// Delete SavedPost
			foreach ($savedPosts as $savedPost) {
				$savedPost->delete();
			}
			
			$data['message'] = t('Listing deleted from favorites successfully');
		} else {
			// Store SavedPost
			$savedPostArray = [
				'user_id' => $authUser->getAuthIdentifier(),
				'post_id' => $postId,
			];
			$savedPost = new SavedPost($savedPostArray);
			$savedPost->save();
			
			$resource = new SavedPostResource($savedPost);
			
			$data['message'] = t('Listing saved in favorites successfully');
			$data['result'] = $resource;
		}
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete saved listing(s)
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$data = [
			'success' => false,
			'message' => t('no_deletion_is_done'),
			'result'  => null,
		];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $postId) {
			$savedPost = SavedPost::query()
				->where('user_id', $authUser->getAuthIdentifier())
				->where('post_id', $postId)
				->first();
			
			if (!empty($savedPost)) {
				$res = $savedPost->delete();
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			
			$count = count($ids);
			if ($count > 1) {
				$data['message'] = t('x entities have been deleted successfully', ['entities' => t('listings'), 'count' => $count]);
			} else {
				$data['message'] = t('1 entity has been deleted successfully', ['entity' => t('listing')]);
			}
		}
		
		return apiResponse()->json($data);
	}
}
