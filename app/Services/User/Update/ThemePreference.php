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

use App\Http\Resources\UserResource;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ThemePreference
{
	/**
	 * Update the user's theme preference
	 *
	 * @param $userId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function saveThemePreferenceForUser($userId, Request $request): JsonResponse
	{
		$user = User::query()
			->withoutGlobalScopes([VerifiedScope::class])
			->where('id', $userId)
			->first();
		
		if (empty($user)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Check logged User
		if ($authUser->getAuthIdentifier() != $user->id) {
			return apiResponse()->unauthorized();
		}
		
		// Set the dark mode in the DB
		$user->theme_preference = $request->input('theme');
		$user->save();
		
		$message = match (true) {
			($user->theme_preference == 'light')  => t('theme_preference_light'),
			($user->theme_preference == 'dark')   => t('theme_preference_dark'),
			($user->theme_preference == 'system') => t('theme_preference_system'),
			empty($user->theme_preference)        => t('theme_preference_empty'),
			default                               => t('theme_preference_error'),
		};
		
		// Result data
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
}
