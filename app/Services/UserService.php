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
use App\Http\Requests\Front\AvatarRequest;
use App\Http\Requests\Front\UserPreferencesRequest;
use App\Http\Requests\Front\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Services\Auth\Traits\Custom\VerificationTrait;
use App\Services\User\Delete;
use App\Services\User\Register;
use App\Services\User\Stats;
use App\Services\User\Update;
use App\Services\User\Update\Photo;
use App\Services\User\Update\Settings;
use App\Services\User\Update\ThemePreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserService extends BaseService
{
	use VerificationTrait;
	use Register, Update, Delete, Settings, Photo, ThemePreference, Stats;
	
	/**
	 * List users
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(): JsonResponse
	{
		/*
		$users = User::paginate($this->perPage);
		$users = PaginationHelper::adjustSides($users);
		$resourceCollection = new EntityCollection(UserResource::class, $users);
		
		return apiResponse()->withCollection($resourceCollection);
		*/
		
		return apiResponse()->forbidden();
	}
	
	/**
	 * Store user
	 *
	 * @param \App\Http\Requests\Front\UserRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(UserRequest $request): JsonResponse
	{
		try {
			return $this->register($request);
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
	}
	
	/**
	 * Get user
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		$user = User::query()->verified();
		
		if (in_array('country', $embed)) {
			$user->with('country');
		}
		if (in_array('payment', $embed)) {
			$user->with(['payment' => function ($query) {
				$query->withoutGlobalScope(StrictActiveScope::class);
			}]);
			if (in_array('package', $embed)) {
				$user->with('payment.package');
			}
		}
		if (in_array('possiblePayment', $embed)) {
			$user->with(['possiblePayment']);
			if (in_array('package', $embed)) {
				$user->with('possiblePayment.package');
			}
		}
		if (in_array('posts', $embed) || in_array('countPosts', $embed)) {
			$postScopes = [VerifiedScope::class, ReviewedScope::class];
			$user->with(['posts' => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived()]);
		}
		if (in_array('postsInCountry', $embed) || in_array('countPostsInCountry', $embed)) {
			$user->with('postsInCountry');
		}
		
		$user = $user->find($id);
		
		abort_if(empty($user), 404, t('user_not_found'));
		
		$resource = new UserResource($user, $params);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Update user
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\UserRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update($id, UserRequest $request): JsonResponse
	{
		return $this->updateDetails($id, $request);
	}
	
	/**
	 * Delete user
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy($id): JsonResponse
	{
		return $this->closeAccount($id);
	}
	
	/**
	 * User's mini stats
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function stats($id): JsonResponse
	{
		return $this->getStats($id);
	}
	
	/**
	 * Update user's preferences
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\UserPreferencesRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updatePreferences($id, UserPreferencesRequest $request): JsonResponse
	{
		return $this->updateUserPreferences($id, $request);
	}
	
	/**
	 * Update user's photo
	 *
	 * @param $userId
	 * @param \App\Http\Requests\Front\AvatarRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updatePhoto($userId, AvatarRequest $request): JsonResponse
	{
		return $this->updateUserPhoto($userId, $request);
	}
	
	/**
	 * Delete user's photo
	 *
	 * @param $userId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function removePhoto($userId): JsonResponse
	{
		return $this->removeUserPhoto($userId);
	}
	
	/**
	 * Update the user's theme preference
	 *
	 * @param $userId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function saveThemePreference($userId, Request $request): JsonResponse
	{
		return $this->saveThemePreferenceForUser($userId, $request);
	}
}
