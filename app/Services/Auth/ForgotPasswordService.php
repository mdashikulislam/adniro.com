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

use App\Services\Auth\App\Http\Requests\ForgotPasswordRequest;
use App\Services\Auth\Traits\Custom\VerificationTrait;
use App\Services\Auth\Traits\System\SendsPasswordResetEmails;
use App\Services\Auth\Traits\System\SendsPasswordResetSms;
use App\Services\BaseService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ForgotPasswordService extends BaseService
{
	use VerificationTrait;
	use SendsPasswordResetEmails, SendsPasswordResetSms;
	
	/**
	 * Forgot password
	 *
	 * @param \App\Services\Auth\App\Http\Requests\ForgotPasswordRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendResetLinkOrCode(ForgotPasswordRequest $request): JsonResponse
	{
		// Get the right auth field
		$authField = getAuthField();
		
		// Send the Token by SMS
		if ($authField == 'phone') {
			return $this->sendOtpCodeToSms($request);
		}
		
		// Go to the core process
		try {
			return $this->sendResetLinkToEmail($request);
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
	}
}
