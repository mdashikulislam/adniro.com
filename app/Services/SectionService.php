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

use App\Http\Resources\EntityCollection;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Services\Section\SectionDataTrait;
use App\Services\Section\SectionSettingTrait;
use Illuminate\Http\JsonResponse;
use Throwable;

class SectionService extends BaseService
{
	use SectionDataTrait, SectionSettingTrait;
	
	/**
	 * List sections
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSections(array $params = []): JsonResponse
	{
		$countryCode = config('country.code');
		$locale = config('app.locale') ?? 'en';
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'  => 'get.sections',
			'country' => $countryCode,
			'locale'  => $locale,
		]);
		
		// Get all homepage sections
		$sections = caching()->remember(Section::class, $cacheParams, function () use ($countryCode) {
			return Section::getFormattedSections($countryCode);
		});
		
		$sectionsList = [];
		if ($sections->count() > 0) {
			foreach ($sections as $name => $section) {
				$method = str($name)->lower()->camel()->toString();
				
				// Check if key exists
				if (!method_exists($this, $method)) {
					continue;
				}
				
				$belongsTo = $section->belongs_to;
				$parameters = $section->field_values ?? [];
				
				/*
				 * Get the section method result & cache it
				 * (since some methods can execute queries)
				 */
				try {
					$resultData = $this->{$method}($parameters);
				} catch (Throwable $e) {
					return apiResponse()->error($e->getMessage());
				}
				
				// Update the parameters/options (if parameters-updater exists for this method)
				$settingMethod = $method . 'Settings';
				if (method_exists($this, $settingMethod)) {
					$parameters = $this->{$settingMethod}($parameters);
				}
				
				// Save the section required information
				$sectionArray = [
					'belongs_to' => $belongsTo,
					'name'       => $name,
					'data'       => $resultData,
					'options'    => $parameters,
					'lft'        => $section->lft ?? 0,
				];
				
				// Save in the list
				$sectionsList[$name] = $sectionArray;
			}
		}
		
		$resourceCollection = new EntityCollection(SectionResource::class, $sectionsList, $params);
		
		return apiResponse()->withCollection($resourceCollection);
	}
	
	/**
	 * Get section
	 *
	 * Get category by its unique name or ID.
	 *
	 * @param string $name
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSectionByKey(string $name, array $params = []): JsonResponse
	{
		$countryCode = config('country.code');
		$locale = config('app.locale') ?? 'en';
		$isUnactivatedIncluded = castIntToBool($params['unactivatedIncluded'] ?? 0);
		$dataCanBeFetched = castIntToBool($params['fetchData'] ?? 0);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'  => 'get.sections',
			'name'    => $name,
			'country' => $countryCode,
			'locale'  => $locale,
		]);
		
		// Get all homepage sections
		$section = caching()->remember(Section::class, $cacheParams, function () use ($countryCode, $name, $isUnactivatedIncluded) {
			return Section::getSectionByName($name, $countryCode, $isUnactivatedIncluded);
		});
		
		abort_if(empty($section), 404, t('section_not_found'));
		
		// Clear key name
		$name = str_replace(strtolower($countryCode) . '_', '', $section->name);
		$method = str($name)->lower()->camel()->toString();
		
		// Check if key exists
		abort_if(!method_exists($this, $method), 404, t('section_not_found'));
		
		$belongsTo = $section->belongs_to;
		$parameters = $section->field_values ?? [];
		
		/*
		 * Get the section method result & cache it
		 * (since some methods can execute queries)
		 */
		$resultData = null;
		try {
			if ($dataCanBeFetched) {
				$resultData = $this->{$method}($parameters);
			}
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
		
		// Update the parameters/options (if parameters-updater exists for this method)
		$settingMethod = $method . 'Settings';
		if (method_exists($this, $settingMethod)) {
			$parameters = $this->{$settingMethod}($parameters);
		}
		
		// Save the section required information
		$sectionArray = [
			'belongsTo' => $belongsTo,
			'name'      => $name,
			'data'      => $resultData,
			'options'   => $parameters,
			'lft'       => $section->lft ?? 0,
		];
		
		$resource = new SectionResource($sectionArray, $params);
		
		return apiResponse()->withResource($resource);
	}
}
