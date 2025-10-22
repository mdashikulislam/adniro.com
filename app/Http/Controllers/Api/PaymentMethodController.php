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

namespace App\Http\Controllers\Api;

use App\Services\PaymentMethodService;
use Illuminate\Http\JsonResponse;

/**
 * @group Payment Methods
 */
class PaymentMethodController extends BaseController
{
	protected PaymentMethodService $paymentMethodService;
	
	/**
	 * @param \App\Services\PaymentMethodService $paymentMethodService
	 */
	public function __construct(PaymentMethodService $paymentMethodService)
	{
		parent::__construct();
		
		$this->paymentMethodService = $paymentMethodService;
	}
	
	/**
	 * List payment methods
	 *
	 * @queryParam countryCode string Country code. Select only the payment methods related to a country. Example: US
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: lft. Example: -lft
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'countryCode' => request()->input('countryCode'),
		];
		
		return $this->paymentMethodService->getEntries($params);
	}
	
	/**
	 * Get payment method
	 *
	 * @urlParam $id int required Can be the ID (int) or name (string) of the payment method. Example: 1
	 *
	 * @param int|string $nameOrId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(int|string $nameOrId): JsonResponse
	{
		return $this->paymentMethodService->getEntry($nameOrId);
	}
}
