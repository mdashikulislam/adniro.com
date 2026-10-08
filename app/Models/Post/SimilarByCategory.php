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

namespace App\Models\Post;

use App\Jobs\GeneratePostCollectionThumbnails;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

trait SimilarByCategory
{
	use SimilarNeighbors;

	/**
	 * Get similar Posts (Posts in the same Category)
	 *
	 * @param int|null $limit
	 * @return \Illuminate\Pagination\LengthAwarePaginator
	 */
	public function getSimilarByCategory(?int $limit = 20): LengthAwarePaginator
	{
		$posts = Post::query();
		
		$postsTable = (new Post())->getTable();
		
		$select = [
			$postsTable . '.id',
			$postsTable . '.country_code',
			'category_id',
			'title',
			$postsTable . '.price',
			'city_id',
			'featured',
			'email_verified_at',
			'phone_verified_at',
			'reviewed_at',
			$postsTable . '.created_at',
			$postsTable . '.archived_at',
		];
		if (isFromApi() && !doesRequestIsFromWebClient()) {
			$select[] = $postsTable . '.description';
			$select[] = 'user_id';
			$select[] = 'contact_name';
			$select[] = $postsTable . '.auth_field';
			$select[] = $postsTable . '.phone';
			$select[] = $postsTable . '.email';
		}
		if (config('plugins.reviews.installed')) {
			$select[] = 'rating_cache';
			$select[] = 'rating_count';
		}
		
		if (!empty($select)) {
			foreach ($select as $column) {
				$posts->addSelect($column);
			}
		}
		
		// Get the sub-categories of the current listing parent's category
		$similarCatIds = [];
		if (!empty($this->category)) {
			if ($this->category->id == $this->category->parent_id) {
				$similarCatIds[] = $this->category->id;
			} else {
				if (!empty($this->category->parent_id)) {
					$similarCatIds = Category::childrenOf($this->category->parent_id)->get()
						->keyBy('id')
						->keys()
						->toArray();
					$similarCatIds[] = (int)$this->category->parent_id;
				} else {
					$similarCatIds[] = (int)$this->category->id;
				}
			}
		}
		
		// Default Filters
		$posts->inCountry()->verified()->unarchived();
		if (config('settings.listing_form.listings_review_activation')) {
			$posts->reviewed();
		}
		
		// Relations
		$posts->has('category');
		if (!config('settings.listings_list.hide_category')) {
			$posts->with('category', fn ($query) => $query->with('parent'));
		}
		$posts->with('picture');
		$posts->with('pictures');
		$posts->has('city');
		if (!config('settings.listings_list.hide_location')) {
			$posts->with('city');
		}
		$posts->with('savedByLoggedUser');
		$posts->with('payment', fn ($query) => $query->with('package'));
		$posts->with('user');
		$posts->with('user.permissions');
		$basePosts = $posts;
		
		// The listing's neighbors in its own category first (a single index range),
		// then in the sibling categories when there aren't enough
		$limit = (int)$limit;
		$posts = $this->takeNeighbors((clone $basePosts)->where('category_id', (int)$this->category_id), $limit);
		
		$siblingCatIds = array_values(array_diff($similarCatIds, [(int)$this->category_id]));
		if ($posts->count() < $limit && !empty($siblingCatIds)) {
			$siblingPosts = $this->takeNeighbors(
				(clone $basePosts)->whereIn('category_id', $siblingCatIds),
				$limit - $posts->count()
			);
			$items = $posts->getCollection()->concat($siblingPosts->getCollection())->values();
			$posts = new LengthAwarePaginator($items, $items->count(), $limit, 1, [
				'path' => LengthAwarePaginator::resolveCurrentPath(),
			]);
		}
		
		// Generate listings images thumbnails
		GeneratePostCollectionThumbnails::dispatch($posts);
		
		return $posts;
	}
}
