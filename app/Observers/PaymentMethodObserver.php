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

namespace App\Observers;

use App\Models\PaymentMethod;
use App\Models\Scopes\ActiveScope;

class PaymentMethodObserver extends BaseObserver
{
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param PaymentMethod $paymentMethod
	 * @return void
	 */
	public function deleting(PaymentMethod $paymentMethod)
	{
		/*
		// Delete the payments of this PaymentMethod
		$payments = Payment::query()
			->withoutGlobalScope(StrictActiveScope::class)
			->where('payment_method_id', $paymentMethod->id);
		
		if ($payments->count() > 0) {
			foreach ($payments->cursor() as $payment) {
				// NOTE: Take account the payment plugins install/uninstall
				$payment->delete();
			}
		}
		*/
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param PaymentMethod $paymentMethod
	 * @return void
	 */
	public function saved(PaymentMethod $paymentMethod)
	{
		// ...
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param PaymentMethod $paymentMethod
	 * @return void
	 */
	public function deleted(PaymentMethod $paymentMethod)
	{
		// ...
	}
}
