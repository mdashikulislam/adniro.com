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

namespace App\Http\Controllers\Web\Front\Payment;

use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\Request;

trait HasPaymentRedirection
{
	/**
	 * Retrieve the payable model by ID (only when payment is triggered)
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param int|null $payableId
	 * @return Post|User|null
	 */
	protected function retrievePayableModel(Request $request, ?int $payableId = null): User|Post|null
	{
		// Get the package type relating to the current request
		$packageType = getRequestPackageType();
		
		$areRequiredDataFilled = $request->filled(['package_id', 'payment_method_id']);
		
		if (empty($packageType) || empty($payableId) || !$areRequiredDataFilled) {
			return null;
		}
		
		$isPromoting = ($packageType === 'promotion');
		$isSubscripting = ($packageType === 'subscription');
		
		$payable = null;
		if ($isPromoting) {
			$payable = Post::withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->with(['payment' => fn ($query) => $query->with('package')])
				->where('id', $payableId)
				->first();
		}
		if ($isSubscripting) {
			$payable = User::withoutGlobalScopes([VerifiedScope::class])
				->with(['payment' => fn ($query) => $query->with('package')])
				->where('id', $payableId)
				->first();
		}
		
		return $payable;
	}
}
