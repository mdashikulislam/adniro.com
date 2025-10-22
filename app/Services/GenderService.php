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

use App\Enums\Gender;
use Illuminate\Http\JsonResponse;

class GenderService extends BaseService
{
	/**
	 * List genders
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(): JsonResponse
	{
		$genders = Gender::all('title');
		
		$message = empty($genders) ? t('no_genders_found') : null;
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => $genders,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Get gender
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id): JsonResponse
	{
		$gender = Gender::find($id);
		
		abort_if(empty($gender), 404, t('gender_not_found'));
		
		$data = [
			'success' => true,
			'result'  => $gender,
		];
		
		return apiResponse()->json($data);
	}
}
