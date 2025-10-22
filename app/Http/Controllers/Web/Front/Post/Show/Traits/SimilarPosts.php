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

namespace App\Http\Controllers\Web\Front\Post\Show\Traits;

trait SimilarPosts
{
	/**
	 * @param $postId
	 * @return array|null
	 */
	protected function similarPosts($postId): ?array
	{
		$post = null;
		$posts = [];
		$totalPosts = 0;
		$widgetSimilarPosts = null;
		$message = null;
		
		// GET SIMILAR POSTS
		if (in_array(config('settings.listing_page.similar_listings'), ['1', '2'])) {
			// Get posts
			$queryParams = [
				'op'       => 'similar',
				'postId'   => $postId,
				'distance' => 50, // km OR miles
			];
			$data = getServiceData($this->postService->getEntries($queryParams));
			
			$message = data_get($data, 'message');
			$posts = data_get($data, 'result.data');
			$totalPosts = data_get($data, 'extra.count.0');
			$post = data_get($data, 'extra.preSearch.post');
		}
		
		if (config('settings.listing_page.similar_listings') == '1') {
			// Featured Area Data
			$widgetSimilarPosts = [
				'title'      => t('Similar Listings'),
				'link'       => urlGen()->category(data_get($post, 'category')),
				'posts'      => $posts,
				'totalPosts' => $totalPosts,
				'message'    => $message,
			];
			$widgetSimilarPosts = ($totalPosts > 0) ? $widgetSimilarPosts : null;
		} else if (config('settings.listing_page.similar_listings') == '2') {
			$distance = 50; // km OR miles
			
			// Featured Area Data
			$widgetSimilarPosts = [
				'title'      => t('more_listings_at_x_distance_around_city', [
					'distance' => $distance,
					'unit'     => getDistanceUnit(config('country.code')),
					'city'     => data_get($post, 'city.name'),
				]),
				'link'       => urlGen()->city(data_get($post, 'city')),
				'posts'      => $posts,
				'totalPosts' => $totalPosts,
				'message'    => $message,
			];
			$widgetSimilarPosts = ($totalPosts > 0) ? $widgetSimilarPosts : null;
		}
		
		return $widgetSimilarPosts;
	}
}
