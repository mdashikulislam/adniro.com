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

namespace App\Services\User\Update;

use App\Http\Requests\Front\UserPreferencesRequest;
use App\Http\Resources\UserResource;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\JsonResponse;

trait Settings
{
	/**
	 * Update the user's preferences
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\UserPreferencesRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function updateUserPreferences($id, UserPreferencesRequest $request): JsonResponse
	{
		/** @var User $user */
		$user = User::query()
			->withoutGlobalScopes([VerifiedScope::class])
			->where('id', $id)
			->first();
		
		if (empty($user)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		/** @var User $authUser */
		$authUser = request()->user() ?? auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Check logged User
		// Get the User Personal Access Token Object
		$personalAccess = isFromApi()
			? $authUser->tokens()->where('id', getApiAuthToken())->first()
			: null;
		
		if (!empty($personalAccess)) {
			if ($personalAccess->tokenable_id != $user->id) {
				return apiResponse()->unauthorized();
			}
		} else {
			if ($authUser->getAuthIdentifier() != $user->id) {
				return apiResponse()->unauthorized();
			}
		}
		
		// Update User
		$input = $request->only($user->getFillable());
		
		foreach ($input as $key => $value) {
			if ($request->has($key)) {
				$user->{$key} = $value;
			}
		}
		
		// Checkboxes
		$user->disable_comments = (int)$request->input('disable_comments');
		$user->accept_marketing_offers = (int)$request->input('accept_marketing_offers');
		if ($request->filled('accept_terms')) {
			$user->accept_terms = (int)$request->input('accept_terms');
		}
		
		// Save
		$user->save();
		
		$data = [
			'success' => true,
			'message' => t('account_settings_has_updated_successfully'),
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		return apiResponse()->updated($data);
	}
}
