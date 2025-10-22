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

namespace App\Services\Auth\Traits\Custom\Verification;

use App\Helpers\Common\Num;
use App\Services\Auth\App\Notifications\VerifyPhoneCode;
use Illuminate\Http\JsonResponse;
use Throwable;

trait PhoneVerificationTrait
{
	/**
	 * SMS: Re-send code
	 *
	 * Re-send mobile phone verification token by SMS
	 *
	 * @param string $entityMetadataKey
	 * @param int|string $entityId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resendPhoneVerification(string $entityMetadataKey, int|string $entityId, array $params = []): JsonResponse
	{
		$alreadyIncremented = (bool)($params['alreadyIncremented'] ?? false);
		
		$data = [
			'success' => true,
			'message' => null,
		];
		
		// Get the entity metadata
		$entityMetadata = $this->getEntityMetadata($entityMetadataKey);
		
		if (empty($entityMetadata)) {
			return apiResponse()->notFound(sprintf($this->metadataNotFoundMessage, $entityMetadataKey));
		}
		
		// Get entity by ID
		/** @var \App\Models\User|\App\Models\Post $object */
		$model = $entityMetadata['model'];
		$object = $model::query()->withoutGlobalScopes($entityMetadata['scopes'])->where('id', $entityId)->first();
		
		if (empty($object)) {
			return apiResponse()->notFound(trans('auth.entity_id_not_found'));
		}
		
		// Update data & extra
		$data = $this->updateExtraDataForPhone($entityMetadata, $object, $data);
		$fieldValue = $data['extra']['fieldValue'] ?? '*********';
		$fieldHiddenValue = $data['extra']['fieldHiddenValue'] ?? '*********';
		
		// Check if the Phone is already verified
		if (!empty($object->phone_verified_at)) {
			
			$data['success'] = false;
			$data['message'] = trans('auth.field_already_verified', ['field' => t('phone_number')]);
			
			// Remove Notification Trigger
			$data['extra']['isUnverifiedField'] = false;
			$data['extra']['fieldVerificationSent'] = false;
			
		} else {
			
			if ($object->canRequestNewToken($entityMetadataKey)) {
				if (!$alreadyIncremented) {
					$object->incrementTokenAttempts($entityMetadataKey);
				}
				
				// Generate and set OTP with expiration
				$object->generatePhoneToken($entityMetadataKey);
				
				// Send the code
				// Re-Send the confirmation
				$data = $this->sendPhoneVerification($entityMetadataKey, $object, true, $params);
				
				if (data_get($data, 'success')) {
					$message = isAdminPanel()
						? trans('auth.verification_code_sent_to_user', ['fieldValue' => $fieldValue])
						: trans('auth.verification_code_sent', ['fieldHiddenValue' => $fieldHiddenValue]);
					
					$data['message'] = $message;
					
					// Remove Notification Trigger
					// $data['extra']['fieldVerificationSent'] = false;
				}
			} else {
				if ($object->isVerificationLocked()) {
					$data['message'] = trans('auth.account_locked_for_excessive_otp_resend_attempts');
					$data['extra']['resendLocked'] = true;
				} else {
					$cooldownInSecond = otpCooldownInSeconds();
					$maxAttempts = otpResendMaxAttempts();
					
					$remainingSeconds = $cooldownInSecond - ($object->last_otp_sent_at ? $object->last_otp_sent_at->diffInSeconds(now()) : 0);
					$humanReadableTime = Num::shortTime($remainingSeconds);
					
					$message = ($object->otp_resend_attempts >= $maxAttempts)
						? trans('auth.maximum_otp_resend_attempts_reached')
						: trans('auth.wait_before_request_new_otp', ['humanReadableTime' => $humanReadableTime]);
					
					$data['message'] = $message;
				}
			}
			
		}
		
		return apiResponse()->json($data);
	}
	
	/**
	 * SMS: Send code (It's not an endpoint)
	 * Send mobile phone verification token by SMS
	 *
	 * @param string $entityMetadataKey
	 * @param $object
	 * @param bool $displayFlashMessage
	 * @param array $params
	 * @return array
	 */
	protected function sendPhoneVerification(string $entityMetadataKey, $object, bool $displayFlashMessage = true, array $params = []): array
	{
		$languageCode = $params['languageCode'] ?? null;
		$languageCode = $params['locale'] ?? $languageCode;
		$languageCode = (!empty($languageCode) && array_key_exists($languageCode, getSupportedLanguages()))
			? $languageCode
			: null;
		
		$data = [];
		
		// Get the entity metadata
		$entityMetadata = $this->getEntityMetadata($entityMetadataKey);
		
		if (empty($entityMetadata) || empty($object)) {
			$message = empty($entityMetadata)
				? sprintf($this->metadataNotFoundMessage, $entityMetadataKey)
				: trans('auth.entity_id_not_found');
			
			$data['success'] = false;
			$data['message'] = $message;
			
			return $data;
		}
		
		// Update data & extra
		$data = $this->updateExtraDataForPhone($entityMetadata, $object, $data);
		$fieldHiddenValue = $data['extra']['fieldHiddenValue'] ?? '*********';
		
		// Send Confirmation Email
		try {
			if (!empty($languageCode)) {
				$object->notify((new VerifyPhoneCode($object, $entityMetadata))->locale($languageCode));
			} else {
				$object->notify(new VerifyPhoneCode($object, $entityMetadata));
			}
			
			if ($displayFlashMessage) {
				$message = trans('auth.verification_code_sent', ['fieldHiddenValue' => $fieldHiddenValue]);
				
				$data['success'] = true;
				$data['message'] = $message;
			}
			
			$data['extra']['fieldVerificationSent'] = true;
			
			return $data;
		} catch (Throwable $e) {
			$message = replaceNewlinesWithSpace($e->getMessage());
			
			$data['success'] = false;
			$data['message'] = $message;
			
			return $data;
		}
	}
}
