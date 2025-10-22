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

namespace App\Http\Resources;

use App\Enums\PostType;
use Illuminate\Http\Request;

class PostResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\Post $this */
		if (!isset($this->id)) return [];
		
		$entity = [
			'id' => $this->id,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$appendedColumns = $this->getAppends();
		foreach ($appendedColumns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$entity['price_label'] = $this->price_label ?? t('price');
		$entity['count_pictures'] = $this->count_pictures ?? 0;
		
		// Main Picture
		$picture = $this->whenLoaded('picture');
		if (!empty($picture)) {
			$entity['picture'] = new PictureResource($picture, $this->params);
		} else {
			$defaultPicture = config('larapen.media.picture');
			$defaultPictureUrl = thumbParam($defaultPicture)->url();
			$entity['picture'] = [
				'file_path' => $defaultPicture,
				'url'       => [
					'full'   => $defaultPictureUrl,
					'small'  => $defaultPictureUrl,
					'medium' => $defaultPictureUrl,
					'large'  => $defaultPictureUrl,
				],
			];
		}
		
		if (in_array('pictures', $this->embed)) {
			$pictures = $this->whenLoaded('pictures');
			$picturesCollection = new EntityCollection(PictureResource::class, $pictures, $this->params);
			$entity['pictures'] = $picturesCollection->toArray(request(), true);
		}
		if (in_array('country', $this->embed)) {
			$entity['country'] = new CountryResource($this->whenLoaded('country'));
		}
		if (in_array('user', $this->embed)) {
			$entity['user'] = new UserResource($this->whenLoaded('user'), $this->params);
		}
		if (in_array('category', $this->embed)) {
			$entity['category'] = new CategoryResource($this->whenLoaded('category'), $this->params);
		}
		if (in_array('postType', $this->embed)) {
			if (!empty($this->post_type_id)) {
				$entity['postType'] = PostType::find($this->post_type_id);
			}
		}
		if (in_array('city', $this->embed)) {
			$entity['city'] = new CityResource($this->whenLoaded('city'), $this->params);
		}
		if (in_array('currency', $this->embed)) {
			$entity['currency'] = new CurrencyResource($this->whenLoaded('currency'), $this->params);
		}
		if (in_array('payment', $this->embed)) {
			$entity['payment'] = new PaymentResource($this->whenLoaded('payment'), $this->params);
		}
		if (in_array('possiblePayment', $this->embed)) {
			$entity['possiblePayment'] = new PaymentResource($this->whenLoaded('possiblePayment'), $this->params);
		}
		
		// Reviews Plugin
		if (config('plugins.reviews.installed')) {
			$entity['rating_cache'] = $this->rating_cache ?? 0;
			$entity['rating_count'] = $this->rating_count ?? 0;
			// Warning: To prevent SQL queries in loop,
			// Never embed 'userRating' and 'countUserRatings' when collection will be returned
			if (in_array('userRating', $this->embed)) {
				$entity['p_user_rating'] = $this->userRating();
			}
			if (in_array('countUserRatings', $this->embed)) {
				$entity['p_count_user_ratings'] = $this->countUserRatings();
			}
		}
		
		if (isset($this->distance)) {
			$entity['distance'] = $this->distance;
		}
		
		if (in_array('savedByLoggedUser', $this->embed)) {
			if (auth(getAuthGuard())->check()) {
				// Reloads the relation from the database
				// i.e. Prevent the relationship from being cached
				// $savedByLoggedUser = $this->fresh()->savedByLoggedUser ?? null;
				// Comment added in v18.0.3 (2025-09-06)
				// $this->load('savedByLoggedUser');
				// $savedByLoggedUser = $this->savedByLoggedUser ?? null;
				$savedByLoggedUser = $this->whenLoaded('savedByLoggedUser');
				
				$entity['p_saved_by_logged_user'] = !empty($savedByLoggedUser) ? 1 : 0;
			}
		}
		
		// From SavedPostResource
		if (isset($this->saved_at_formatted)) {
			$entity['saved_at_formatted'] = $this->saved_at_formatted;
		}
		
		return $entity;
	}
}
