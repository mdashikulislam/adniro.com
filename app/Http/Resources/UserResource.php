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

use App\Enums\Gender;
use App\Enums\UserType;
use App\Models\Post;
use App\Models\SavedPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\User $this */
		if (!isset($this->id)) return [];
		
		$entity = [
			'id'                 => $this->id,
			'name'               => $this->name ?? null,
			'username'           => $this->username ?? null,
			'two_factor_enabled' => $this->two_factor_enabled ?? null,
			'two_factor_method'  => $this->two_factor_method ?? null,
		];
		
		$entity['updated_at'] = $this->updated_at ?? null;
		$entity['original_updated_at'] = $this->original_updated_at ?? null;
		$entity['original_last_activity'] = $this->original_last_activity ?? null;
		$entity['created_at_formatted'] = $this->created_at_formatted ?? null;
		$entity['photo_url'] = $this->photo_url ?? null;
		$entity['p_is_online'] = $this->p_is_online ?? null;
		$entity['country_flag_url'] = $this->country_flag_url ?? null;
		
		$authUser = auth(getAuthGuard())->user();
		
		if (!empty($authUser)) {
			$isAuthUserData = ($this->id == $authUser->getAuthIdentifier());
			
			$columns = array_diff($this->getFillable(), $this->getHidden());
			foreach ($columns as $column) {
				$entity[$column] = $this->{$column};
			}
			
			$entity['phone_intl'] = $this->phone_intl ?? null;
			$entity['remaining_posts'] = $this->remaining_posts ?? null;
			$entity['last_login_at_formatted'] = $this->last_login_at_formatted ?? null;
			
			if (array_key_exists('can_be_impersonate', $entity)) {
				unset($entity['can_be_impersonate']);
			}
			
			if (in_array('country', $this->embed)) {
				$entity['country'] = new CountryResource($this->whenLoaded('country'), $this->params);
			}
			if (in_array('userType', $this->embed)) {
				if (!empty($this->user_type_id)) {
					$entity['userType'] = UserType::find($this->user_type_id);
				}
			}
			if (in_array('gender', $this->embed)) {
				if (!empty($this->gender_id)) {
					$entity['gender'] = Gender::find($this->gender_id);
				}
			}
			
			// Logged User's Info
			if ($isAuthUserData) {
				$entity['time_zone'] = $this->time_zone ?? null;
				
				if (in_array('payment', $this->embed)) {
					$entity['payment'] = new PaymentResource($this->whenLoaded('payment'), $this->params);
				}
				if (in_array('possiblePayment', $this->embed)) {
					$entity['possiblePayment'] = new PaymentResource($this->whenLoaded('possiblePayment'), $this->params);
				}
				
				// Mini Stats
				$count = [];
				if (in_array('postsInCountry', $this->embed)) {
					// $count['posts'] = Post::inCountry()->where('user_id', $this->id)->count();
					$count['posts'] = isset($this->postsInCountry) ? $this->postsInCountry->count() : 0;
				}
				if (in_array('countPostsViews', $this->embed)) {
					$countPostsViews = Post::query()
						->select('user_id', DB::raw('SUM(visits) as total_views'))
						->inCountry()
						->where('user_id', $this->id)
						->groupBy('user_id')
						->first();
					$count['postsViews'] = (int)($countPostsViews->total_views ?? 0);
				}
				if (in_array('countSavedPosts', $this->embed)) {
					$count['savedPosts'] = SavedPost::query()
						->has('postsInCountry')
						->where('user_id', $this->id)
						->count();
				}
				if (!empty($count)) {
					$entity['count'] = $count;
				}
			} else {
				if (array_key_exists('email_token', $entity)) {
					unset($entity['email_token']);
				}
				if (array_key_exists('phone_token', $entity)) {
					unset($entity['phone_token']);
				}
			}
		}
		
		return $entity;
	}
}
