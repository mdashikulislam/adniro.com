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

namespace App\Services\Post;

use App\Helpers\Common\PaginationHelper;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostResource;
use App\Jobs\GeneratePostCollectionThumbnails;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Services\Post\List\SearchTrait;
use App\Services\Post\List\SimilarTrait;
use Illuminate\Http\JsonResponse;

trait ListTrait
{
	use SearchTrait, SimilarTrait;
	
	/**
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function getPostsList(array $params = []): JsonResponse
	{
		$perPage = getNumberOfItemsPerPage('posts', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$countryCode = $params['countryCode'] ?? config('country.code');
		$arePendingApproval = castIntToBool($params['pendingApproval'] ?? 0);
		$areArchived = castIntToBool($params['archived'] ?? 0);
		$_belongLoggedUser = castIntToBool($params['belongLoggedUser'] ?? 0);
		$_logged = castIntToBool($params['logged'] ?? 0);
		$areBelongLoggedUser = ($_belongLoggedUser || $_logged);
		$sort = $params['sort'] ?? [];
		
		$posts = Post::query()
			->with(['user', 'user.permissions', 'picture'])
			->inCountry($countryCode)
			->has('country');
		
		if ($areBelongLoggedUser) {
			$authUser = auth(getAuthGuard())->user();
			if (!empty($authUser)) {
				$posts->where('user_id', $authUser->getAuthIdentifier());
				
				if ($arePendingApproval) {
					$posts->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])->unverified();
				} else if ($areArchived) {
					$posts->archived();
				} else {
					$posts->verified()->unarchived()->reviewed();
				}
			} else {
				return apiResponse()->unauthorized();
			}
		}
		
		if (in_array('country', $embed)) {
			$posts->with('country');
		}
		if (in_array('user', $embed)) {
			if (!$posts->relationLoaded('user')) {
				$posts->with(['user']);
			}
			if ($posts->relationLoaded('user') && !$posts->relationLoaded('user.permissions')) {
				$posts->with(['user.permissions']);
			}
		}
		if (in_array('category', $embed)) {
			$posts->with('category');
		}
		if (in_array('city', $embed)) {
			$posts->with('city');
		}
		if (in_array('pictures', $embed)) {
			$posts->with('pictures');
		}
		if (in_array('payment', $embed)) {
			if (in_array('package', $embed)) {
				$posts->with(['payment' => fn ($builder) => $builder->with(['package'])]);
			} else {
				$posts->with('payment');
			}
		}
		
		// Sorting
		$posts = $this->applySorting($posts, ['created_at'], $sort);
		
		$posts = $posts->paginate($perPage);
		$posts = PaginationHelper::adjustSides($posts);
		
		// Generate listings images thumbnails
		GeneratePostCollectionThumbnails::dispatch($posts);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$posts = setPaginationBaseUrl($posts);
		
		$resourceCollection = new EntityCollection(PostResource::class, $posts, $params);
		$message = ($posts->count() <= 0) ? t('no_posts_found') : null;
		$resourceCollection = apiResponse()->withCollection($resourceCollection, $message);
		
		$data = json_decode($resourceCollection->content(), true);
		
		return apiResponse()->json($data);
	}
}
