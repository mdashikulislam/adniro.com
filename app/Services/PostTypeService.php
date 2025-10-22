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

use App\Enums\PostType;
use Illuminate\Http\JsonResponse;

class PostTypeService extends BaseService
{
	/**
	 * List listing types
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(): JsonResponse
	{
		$postTypes = PostType::all();
		
		$message = empty($postTypes) ? t('no_post_types_found') : null;
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => $postTypes,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Get listing type
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id): JsonResponse
	{
		$postType = PostType::find($id);
		
		abort_if(empty($postType), 404, t('post_type_not_found'));
		
		$data = [
			'success' => true,
			'result'  => $postType,
		];
		
		return apiResponse()->json($data);
	}
}
