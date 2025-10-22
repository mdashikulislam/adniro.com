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

namespace App\Services\Auth;

use App\Http\Resources\UserResource;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Services\Auth\App\Http\Requests\PasswordRequest;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class PasswordService extends BaseService
{
	/**
	 * Update the user's password
	 *
	 * @param $id
	 * @param \App\Services\Auth\App\Http\Requests\PasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function change($id, PasswordRequest $request): JsonResponse
	{
		/** @var User $user */
		$user = User::query()->withoutGlobalScopes([VerifiedScope::class])->where('id', $id)->first();
		
		if (empty($user)) {
			return apiResponse()->unauthorized(trans('auth.invalid_session'));
		}
		
		/** @var User $authUser */
		$authUser = request()->user() ?? auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Check logged User
		// Get the User Personal Access Token Object
		$personalAccess = isFromApi() ? $authUser->tokens()->where('id', getApiAuthToken())->first() : null;
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
		
		$protectedColumns = ['password'];
		
		foreach ($input as $key => $value) {
			if ($request->has($key)) {
				if (in_array($key, $protectedColumns) && empty($value)) {
					continue;
				}
				
				$user->{$key} = $value;
			}
		}
		
		// Password (Need to $request)
		if ($request->filled('new_password')) {
			$user->password = Hash::make($request->input('new_password'));
		}
		
		// Save
		if ($user->isDirty()) {
			$user->save();
		}
		
		$data = [
			'success' => true,
			'message' => trans('auth.password_updated'),
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		return apiResponse()->updated($data);
	}
}
