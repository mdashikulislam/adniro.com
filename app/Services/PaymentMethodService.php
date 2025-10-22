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
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;

class PaymentMethodService extends BaseService
{
	/**
	 * List payment methods
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$plugins = array_keys((array)config('plugins.installed'));
		$countryCode = castToStringOrNull($params['countryCode'] ?? null);
		$sort = $params['sort'] ?? [];
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'  => 'get.paymentMethods',
			'plugins' => implode(',', $plugins),
		]);
		
		// Cached Query
		$paymentMethods = caching()->remember(PaymentMethod::class, $cacheParams, function () use ($plugins, $countryCode, $sort) {
			$paymentMethods = PaymentMethod::query()->whereIn('name', $plugins);
			
			if (!empty($countryCode)) {
				$countryCode = strtolower($countryCode);
				$findInSet = 'FIND_IN_SET("' . $countryCode . '", LOWER(countries)) > 0';
				
				$paymentMethods->where(function ($query) use ($findInSet) {
					$query->whereRaw($findInSet)->orWhere(fn ($query) => $query->columnIsEmpty('countries'));
				});
			}
			
			// Sorting
			$paymentMethods = $this->applySorting($paymentMethods, ['lft'], $sort);
			
			return $paymentMethods->get();
		});
		
		$resourceCollection = new EntityCollection(PaymentMethodResource::class, $paymentMethods, $params);
		
		$message = ($paymentMethods->count() <= 0) ? t('no_payment_methods_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get payment method
	 *
	 * @param int|string $nameOrId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(int|string $nameOrId, array $params = []): JsonResponse
	{
		$identifierColumn = is_numeric($nameOrId) ? 'id' : 'name';
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'           => 'get.paymentMethod',
			'identifierColumn' => $identifierColumn,
			'identifierValue'  => $nameOrId,
		]);
		
		$paymentMethod = caching()->remember(PaymentMethod::class, $cacheParams, function () use ($identifierColumn, $nameOrId) {
			$paymentMethod = PaymentMethod::query()->where($identifierColumn, $nameOrId);
			
			return $paymentMethod->first();
		});
		
		abort_if(empty($paymentMethod), 404, t('payment_method_not_found'));
		
		$resource = new PaymentMethodResource($paymentMethod, $params);
		
		return apiResponse()->withResource($resource);
	}
}
