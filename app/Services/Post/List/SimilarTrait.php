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

namespace App\Services\Post\List;

use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Services\Post\List\Search\CategoryTrait;
use App\Services\Post\List\Search\LocationTrait;
use App\Services\Post\List\Search\SidebarTrait;
use Illuminate\Http\JsonResponse;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

trait SimilarTrait
{
	use CategoryTrait, LocationTrait, SidebarTrait;
	
	/**
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function getSimilarPosts(array $params = []): JsonResponse
	{
		$postId = $params['postId'] ?? null;
		
		// Create the MySQL Distance Calculation function If it doesn't exist
		$distanceCalculationFormula = config('settings.listings_list.distance_calculation_formula', 'haversine');
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists($distanceCalculationFormula)) {
			DistanceHelper::createDistanceCalculationFunction($distanceCalculationFormula);
		}
		
		// similar
		$posts = collect();
		
		if (!empty($postId)) {
			$distance = $params['distance'] ?? null;
			$embed = ['user', 'savedByLoggedUser', 'picture', 'pictures', 'payment', 'package'];
			if (!config('settings.listings_list.hide_post_type')) {
				$embed[] = 'postType';
			}
			if (!config('settings.listings_list.hide_category')) {
				$embed[] = 'category';
				$embed[] = 'parent';
			}
			if (!config('settings.listings_list.hide_location')) {
				$embed[] = 'city';
			}
			$params['embed'] = $embed;
			
			$res = $this->getSimilarPostsData($postId, $distance);
			$posts = $res['posts'] ?? collect();
			$post = $res['post'] ?? null;
			
			$postResource = new PostResource($post, $params);
			$postApiResult = apiResponse()->withResource($postResource)->getData(true);
			$post = data_get($postApiResult, 'result');
		}
		
		$postsCollection = new EntityCollection(PostResource::class, $posts, $params);
		$postsResult = $postsCollection->toResponse(request())->getData(true);
		
		$totalPosts = $posts->count();
		$message = ($totalPosts <= 0) ? t('no_posts_found') : null;
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => $postsResult, // $postsCollection,
			'extra'   => [
				'count' => [$totalPosts],
			],
		];
		if (!empty($post)) {
			$data['extra']['preSearch'] = ['post' => $post];
		}
		
		return apiResponse()->json($data);
	}
	
	/**
	 * @param int|null $postId
	 * @param int|null $distance
	 * @return array
	 */
	private function getSimilarPostsData(?int $postId, ?int $distance = 50): array
	{
		$locale = config('app.locale');
		$withRelations = ['category', 'city', 'picture'];
		
		// Cache Parameters
		$cacheParams = [
			'action'              => 'get.listing',
			'with'                => implode(',', $withRelations),
			'id'                  => $postId,
			'locale'              => $locale,
			'withoutGlobalScopes' => true,
		];
		
		// Get the current listing
		$post = caching()->remember(Post::class, $cacheParams, function () use ($postId, $withRelations) {
			return Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->with($withRelations)
				->where('id', $postId)
				->first();
		});
		
		$posts = [];
		
		if (empty($post)) {
			return $posts;
		}
		
		// Get the similar listings
		$postsLimit = getNumberOfItemsToTake('similar_posts');
		
		if (config('settings.listing_page.similar_listings') == '1') {
			// Cache Parameters
			$cacheParams = [
				'action'     => 'get.similar.listings',
				'id'         => $post->id,
				'locale'     => $locale,
				'categoryId' => $post->category_id,
				'limit'      => $postsLimit,
			];
			
			$posts = caching()->remember(Post::class, $cacheParams, function () use ($post, $postsLimit) {
				/** @var Post $post */
				return $post->getSimilarByCategory($postsLimit);
			});
		}
		
		if (config('settings.listing_page.similar_listings') == '2') {
			$distance = $distance ?? 50; // km OR miles
			
			// Cache Parameters
			$cacheParams = [
				'action'   => 'get.similar.listings',
				'id'       => $post->id,
				'locale'   => $locale,
				'cityId'   => $post->city_id,
				'distance' => $distance,
				'limit'    => $postsLimit,
			];
			
			$posts = caching()->remember(Post::class, $cacheParams, function () use ($post, $distance, $postsLimit) {
				/** @var Post $post */
				return $post->getSimilarByLocation($distance, $postsLimit);
			});
		}
		
		return [
			'post'  => $post,
			'posts' => $posts,
		];
	}
}
