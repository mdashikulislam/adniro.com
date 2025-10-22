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

namespace App\Services\Post\Show;

use App\Events\PostWasVisited;
use App\Http\Resources\PostResource;
use App\Jobs\GeneratePostThumbnails;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use Illuminate\Http\JsonResponse;

trait DetailedTrait
{
	/**
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function showDetailedPost($id, array $params = []): JsonResponse
	{
		$locale = app()->getLocale(); // config('app.locale')
		
		// Lazy Loading Array
		$lazyLoadingArray = [
			'category',
			'category.parent',
			'city',
			'city.subAdmin1',
			'picture',
			'pictures',
			'user',
			'payment',
			'payment.package',
			'savedByLoggedUser',
		];
		
		$preview = castIntToBool($params['preview'] ?? 0);
		
		// Cache Parameters
		$cacheParams = [
			'action'              => 'get.listing',
			'with'                => implode(',', $lazyLoadingArray),
			'id'                  => $id,
			'locale'              => $locale,
			'withoutGlobalScopes' => true,
		];
		
		$authUser = auth(getAuthGuard())->user();
		if (!empty($authUser)) {
			// Get post's details even if it's not activated, not reviewed or archived
			$post = caching()->remember(Post::class, $cacheParams, function () use ($id, $lazyLoadingArray) {
				return Post::query()
					->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
					->withCountryFix()
					->where('id', $id)
					->with($lazyLoadingArray)
					->first();
			});
			
			// If the logged user is not an admin user...
			if (!$authUser->can(Permission::getStaffPermissions())) {
				// Then don't get post that is not from the user
				if (!empty($post) && $post->user_id != $authUser->getAuthIdentifier()) {
					// Cache Parameters
					$cacheParams['withoutGlobalScopes'] = false;
					$cacheParams['unarchived'] = true;
					
					$post = caching()->remember(Post::class, $cacheParams, function () use ($id, $lazyLoadingArray) {
						return Post::withCountryFix()
							->unarchived()
							->where('id', $id)
							->with($lazyLoadingArray)
							->first();
					});
				}
			}
		} else {
			// Cache Parameters
			$cacheParams['withoutGlobalScopes'] = false;
			$cacheParams['unarchived'] = true;
			
			$post = caching()->remember(Post::class, $cacheParams, function () use ($id, $lazyLoadingArray) {
				return Post::withCountryFix()
					->unarchived()
					->where('id', $id)
					->with($lazyLoadingArray)
					->first();
			});
		}
		// Preview Listing after activation
		if ($preview) {
			// Get the post's details even if it's not activated and reviewed
			$post = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->withCountryFix()
				->where('id', $id)
				->with($lazyLoadingArray)
				->first();
		}
		
		// Listing isn't found
		if (empty($post) || empty($post->category) || empty($post->city)) {
			abort(404, t('post_not_found'));
		}
		
		// Increment the listing's visit counter
		PostWasVisited::dispatch($post);
		
		// Generate the listing's images thumbnails
		GeneratePostThumbnails::dispatch($post);
		
		// Get packages features
		$picturesLimit = (int)config('settings.listing_form.pictures_limit');
		$picturesLimit = getUserSubscriptionFeatures($post->user, 'picturesLimit') ?? $picturesLimit;
		$picturesLimit = getPostPromotionFeatures($post, 'picturesLimit') ?? $picturesLimit;
		if ($post->pictures->count() > $picturesLimit) {
			$post->setRelation('pictures', $post->pictures->take($picturesLimit));
		}
		
		$data = [
			'success' => true,
			'result'  => new PostResource($post, $params),
			'extra'   => [
				'fieldsValues' => $this->getFieldsValues($post->category->id, $post->id),
			],
		];
		
		return apiResponse()->json($data);
	}
}
