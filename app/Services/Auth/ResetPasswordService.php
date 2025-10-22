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
use App\Models\User;
use App\Services\Auth\App\Http\Requests\ResetPasswordRequest;
use App\Services\Auth\Traits\Custom\CreateLoginToken;
use App\Services\Auth\Traits\Custom\TwoFactorCode;
use App\Services\Auth\Traits\System\ResetsPasswordsForEmail;
use App\Services\Auth\Traits\System\ResetsPasswordsForPhone;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ResetPasswordService extends BaseService
{
	use ResetsPasswordsForEmail, ResetsPasswordsForPhone;
	use TwoFactorCode, CreateLoginToken;
	
	/**
	 * Reset password
	 *
	 * @param \App\Services\Auth\App\Http\Requests\ResetPasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reset(ResetPasswordRequest $request): JsonResponse
	{
		// Get the right auth field
		$authField = getAuthField();
		
		// Go to the custom process (Phone)
		if ($authField == 'phone') {
			return $this->resetForPhone($request);
		}
		
		// Go to the core process (Email)
		try {
			$jsonResponse = $this->resetForEmail($request);
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
		
		return $jsonResponse;
	}
}
