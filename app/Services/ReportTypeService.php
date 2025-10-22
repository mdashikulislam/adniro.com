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
use App\Http\Resources\ReportTypeResource;
use App\Models\ReportType;
use Illuminate\Http\JsonResponse;

class ReportTypeService extends BaseService
{
	/**
	 * List report types
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$sort = $params['sort'] ?? [];
		
		$reportTypes = ReportType::query();
		
		// Sorting
		$reportTypes = $this->applySorting($reportTypes, ['name'], $sort);
		
		$reportTypes = $reportTypes->get();
		
		$resourceCollection = new EntityCollection(ReportTypeResource::class, $reportTypes, $params);
		
		$message = ($reportTypes->count() <= 0) ? t('no_report_types_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get report type
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id): JsonResponse
	{
		$reportType = ReportType::query()->where('id', $id)->first();
		
		abort_if(empty($reportType), 404, t('report_type_not_found'));
		
		$resource = new ReportTypeResource($reportType);
		
		return apiResponse()->withResource($resource);
	}
}
