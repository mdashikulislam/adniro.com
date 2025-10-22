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

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;

/**
 * @group Verification
 */
class VerificationController extends BaseController
{
	protected VerificationService $verificationService;
	
	/**
	 * @param \App\Services\VerificationService $verificationService
	 */
	public function __construct(VerificationService $verificationService)
	{
		parent::__construct();
		
		$this->verificationService = $verificationService;
	}
	
	/**
	 * Email: Re-send link
	 *
	 * Re-send email verification link to the user
	 *
	 * @urlParam entityMetadataKey string required The slug of the entity to verify ('users' or 'posts'). Example: users
	 * @urlParam entityId int required The entity/model identifier (ID). Example: 3
	 *
	 * @param string $entityMetadataKey
	 * @param int|string $entityId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resendEmailVerification(string $entityMetadataKey, int|string $entityId): JsonResponse
	{
		return $this->verificationService->resendEmailVerification($entityMetadataKey, $entityId);
	}
	
	/**
	 * SMS: Re-send code
	 *
	 * Re-send mobile phone verification token by SMS
	 *
	 * @urlParam entityMetadataKey string required The slug of the entity to verify ('users' or 'posts'). Example: users
	 * @urlParam entityId int required The entity/model identifier (ID). Example: 3
	 *
	 * @param string $entityMetadataKey
	 * @param int|string $entityId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resendPhoneVerification(string $entityMetadataKey, int|string $entityId): JsonResponse
	{
		return $this->verificationService->resendPhoneVerification($entityMetadataKey, $entityId);
	}
	
	/**
	 * Verification
	 *
	 * Verify the user's email address or mobile phone number
	 *
	 * @urlParam entityMetadataKey string required The slug of the entity to verify ('users' or 'posts'). Example: users
	 * @urlParam field string required The field to verify. Example: email
	 * @urlParam token string required The verification token. Example: null
	 *
	 * @param string $entityMetadataKey
	 * @param string $field
	 * @param string|null $token
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function verification(string $entityMetadataKey, string $field, string $token = null): JsonResponse
	{
		$params = [
			'deviceName' => request()->input('device_name'),
		];
		
		return $this->verificationService->verifyCode($entityMetadataKey, $field, $token, $params);
	}
}
