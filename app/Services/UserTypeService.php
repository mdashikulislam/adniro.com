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

use App\Enums\UserType;
use Illuminate\Http\JsonResponse;

class UserTypeService extends BaseService
{
	/**
	 * List user types
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(): JsonResponse
	{
		$userTypes = UserType::all();
		
		$message = empty($userTypes) ? t('no_user_types_found') : null;
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => $userTypes,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Get user type
	 *
	 * @urlParam id int required The user type's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id): JsonResponse
	{
		$userType = UserType::find($id);
		
		abort_if(empty($userType), 404, t('user_type_not_found'));
		
		$data = [
			'success' => true,
			'result'  => $userType,
		];
		
		return apiResponse()->json($data);
	}
}
