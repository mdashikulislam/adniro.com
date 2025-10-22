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

namespace App\Services\Post\Show;

use App\Helpers\Common\Date;
use App\Helpers\Common\VideoEmbedder;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostFieldResource;
use App\Models\CategoryField;
use App\Models\FieldOption;
use Illuminate\Http\JsonResponse;

trait ShowFieldValueTrait
{
	/**
	 * Get Post's Custom Fields Values
	 *
	 * Note: Called when displaying the Post's details
	 *
	 * @param $categoryId
	 * @param $postId
	 * @return array|\Illuminate\Http\JsonResponse
	 */
	protected function getFieldsValues($categoryId, $postId): array|JsonResponse
	{
		// Get the Post's Custom Fields by its Parent Category
		$customFields = CategoryField::getFields($categoryId, $postId);
		
		// Get the Post's Custom Fields that have a value
		$fieldValues = [];
		if ($customFields->count() > 0) {
			foreach ($customFields as $key => $field) {
				if (empty($field->default_value)) {
					continue;
				}
				
				$iField = [
					'id'    => $field->id,
					'name'  => $field->name,
					'type'  => $field->type,
					'value' => $field->default_value,
				];
				
				if (in_array($field->type, ['radio', 'select'])) {
					$optionId = $field->default_value;
					if (is_numeric($optionId)) {
						// Cache Parameters
						$cacheParams = [
							'action' => 'get.fieldOption',
							'find'   => $optionId,
						];
						
						// Cached Query
						$option = caching()->remember(FieldOption::class, $cacheParams, function () use ($optionId) {
							return FieldOption::find($optionId);
						});
						
						if (!empty($option)) {
							$iField['value'] = $option->value;
						}
					}
				}
				
				if (!is_array($field->default_value)) {
					if ($field->type == 'checkbox') {
						$iField['value'] = ($field->default_value == 1) ? t('Yes') : t('No');
					}
					
					if ($field->type == 'video') {
						$value = $field->default_value;
						if (doesRequestIsFromWebClient()) {
							$value = VideoEmbedder::getEmbedCode($value);
						}
						$iField['value'] = $value;
					}
					
					if ($field->type == 'file') {
						$iField['value'] = privateFileUrl($field->default_value, null);
					}
					
					if ($field->type == 'url') {
						$value = addHttp($field->default_value);
						if (doesRequestIsFromWebClient()) {
							$value = '<a href="' . $value . '" target="_blank" rel="nofollow">' . $value . '</a>';
						}
						$iField['value'] = $value;
					}
					
					if ($field->type == 'date') {
						$iField['value'] = Date::format(Date::forDisplay($field->default_value));
					}
					
					if ($field->type == 'date_time') {
						$iField['value'] = Date::format(Date::forDisplay($field->default_value), 'datetime');
					}
					
					if ($field->type == 'date_range') {
						$iField['value'] = str($field->default_value)
							->explode('-')
							->map(function ($item) {
								$item = Date::forDisplay(trim($item));
								
								return Date::format($item);
							})
							->implode(' - ');
					}
				} else {
					if ($field->type == 'checkbox_multiple') {
						$fValues = [];
						foreach ($field->default_value as $iKey => $iValue) {
							if (empty($iValue)) continue;
							$fValues[$iKey] = $iValue->value;
						}
						$iField['value'] = $fValues;
					}
				}
				
				$fieldValues[$key] = $iField;
			}
		}
		
		$fieldValues = collect($fieldValues);
		
		// api/categories/{id}/fields/post/{postId}
		$endpointResponseIsNeed = (
			request()->segment(2) == 'categories'
			&& request()->segment(4) == 'fields'
			&& request()->segment(5) == 'post'
		);
		
		if ($endpointResponseIsNeed) {
			/*
			$data = [
				'success' => true,
				'result'  => new PostFieldResource($fieldValues),
			];
			
			return apiResponse()->json($data);
			*/
			
			$resourceCollection = new EntityCollection(PostFieldResource::class, $fieldValues);
			$message = ($fieldValues->count() <= 0) ? t('no_post_values_found') : null;
			
			return apiResponse()->withCollection($resourceCollection, $message);
		} else {
			// Get Result's Data
			return $fieldValues->toArray();
		}
	}
}
