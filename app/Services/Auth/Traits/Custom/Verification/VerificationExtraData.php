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

trait VerificationExtraData
{
	/**
	 * @param array $entityMetadata
	 * @param $object
	 * @param array $data
	 * @return array
	 */
	protected function updateExtraDataForEmail(array $entityMetadata, $object, array $data = []): array
	{
		$entityMetadataKey = $entityMetadata['key'];
		$entityId = $object->id ?? null;
		
		// Update data & extra
		$resource = $entityMetadata['resource'];
		$fieldValue = $object->email ?? null;
		$fieldHiddenValue = !empty($fieldValue) ? addMaskToString($fieldValue, keepRgt: 2, keepLft: 5) : '********';
		
		$extra = [
			'isUnverifiedField'     => true,
			'fieldVerificationSent' => false,
			'resendUrl'             => urlGen()->resendEmailVerification($entityMetadataKey, $entityId),
			'field'                 => 'email',
			'fieldValue'            => $fieldValue,
			'fieldHiddenValue'      => $fieldHiddenValue,
			'resendLocked'          => false,
		];
		
		$data['success'] = array_key_exists('success', $data) ? $data['success'] : false;
		$data['message'] = array_key_exists('message', $data) ? $data['message'] : null;
		$data['result'] = array_key_exists('result', $data) ? $data['result'] : new $resource($object);
		
		$data['extra'] = $extra;
		
		return $data;
	}
	
	/**
	 * @param array $entityMetadata
	 * @param $object
	 * @param array $data
	 * @return array
	 */
	protected function updateExtraDataForPhone(array $entityMetadata, $object, array $data = []): array
	{
		$entityMetadataKey = $entityMetadata['key'];
		$entityId = $object->id ?? null;
		
		// Update data & extra
		$resource = $entityMetadata['resource'];
		$fieldValue = $object->phone ?? null;
		$fieldHiddenValue = !empty($fieldValue) ? addMaskToString($fieldValue, keepRgt: 2, keepLft: 5) : '********';
		
		$extra = [
			'isUnverifiedField'     => true,
			'fieldVerificationSent' => false,
			'resendUrl'             => urlGen()->resendSmsVerification($entityMetadataKey, $entityId),
			'field'                 => 'phone',
			'fieldValue'            => $fieldValue,
			'fieldHiddenValue'      => $fieldHiddenValue,
			'resendLocked'          => false,
		];
		
		$data['success'] = array_key_exists('success', $data) ? $data['success'] : false;
		$data['message'] = array_key_exists('message', $data) ? $data['message'] : null;
		$data['result'] = array_key_exists('result', $data) ? $data['result'] : new $resource($object);
		
		$data['extra'] = $extra;
		
		return $data;
	}
}
