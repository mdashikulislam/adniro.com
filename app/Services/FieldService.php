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

use App\Models\CategoryField;
use Illuminate\Http\JsonResponse;

class FieldService extends BaseService
{
	/**
	 * @param $categoryId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCategoryFields($categoryId, array $params = []): JsonResponse
	{
		$languageCode = castToStringOrNull($params['languageCode'] ?? config('app.locale'));
		$postId = $params['postId'] ?? null;
		
		// Custom Fields vars
		$errors = $params['errors'] ?? null;
		$errors = convertUTF8HtmlToAnsi($errors); // Convert UTF-8 HTML to ANSI
		$errors = stripslashes($errors);
		$errors = collect(json_decode($errors, true));
		// ...
		$oldInput = $params['oldInput'] ?? null;
		$oldInput = convertUTF8HtmlToAnsi($oldInput); // Convert UTF-8 HTML to ANSI
		$oldInput = stripslashes($oldInput);
		$oldInput = json_decode($oldInput, true);
		
		// Get the Category's Custom Fields buffer
		$fields = CategoryField::getFields($categoryId, $postId, $languageCode);
		
		$success = ($errors->count() <= 0);
		
		// Get Result's Data
		$data = [
			'success' => $success,
			'result'  => $fields->toArray(),
			'extra'   => [
				'errors'   => $errors->toArray(),
				'oldInput' => $oldInput,
			],
		];
		
		return apiResponse()->json($data);
	}
}
