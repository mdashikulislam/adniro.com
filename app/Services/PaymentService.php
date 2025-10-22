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

use App\Helpers\Common\PaginationHelper;
use App\Http\Requests\Front\PackageRequest;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use App\Services\Payment\Promotion\MultiStepsPayment;
use App\Services\Payment\Subscription\SubscriptionPayment;
use Illuminate\Http\JsonResponse;

class PaymentService extends BaseService
{
	use MultiStepsPayment;
	use SubscriptionPayment;
	
	/**
	 * List payments
	 *
	 * @param int|null $payableId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(?int $payableId = null, array $params = []): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$perPage = getNumberOfItemsPerPage('payments', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$isValid = castIntToBool($params['isValid'] ?? 0);
		$isActive = castIntToBool($params['isActive'] ?? 0);
		$sort = $params['sort'] ?? [];
		$paymentType = $params['paymentType'] ?? null;
		
		abort_if(empty($paymentType), 400, 'Payment type not found.');
		
		$isPromoting = ($paymentType == 'promotion');
		$isSubscripting = ($paymentType == 'subscription');
		
		$payments = Payment::query()
			->withoutGlobalScopes([ValidPeriodScope::class, StrictActiveScope::class]);
		
		if (!empty($payableId)) {
			$payments->$paymentType()->where('payable_id', $payableId);
		}
		
		if ($isPromoting) {
			$payments->whereHasMorph('payable', Post::class, function ($query) use ($authUser) {
				$query->inCountry();
				$query->whereHas('user', fn ($q) => $q->where('user_id', $authUser->getAuthIdentifier()));
			});
		}
		if ($isSubscripting) {
			$payments->whereHasMorph('payable', User::class, function ($query) use ($authUser) {
				$query->where('id', $authUser->getAuthIdentifier());
			});
			if (in_array('posts', $embed)) {
				$postScopes = [VerifiedScope::class, ReviewedScope::class];
				$payments->with(['posts' => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived()]);
			}
		}
		
		if (in_array('payable', $embed)) {
			$payments->with('payable');
		}
		if (in_array('paymentMethod', $embed)) {
			$payments->with('paymentMethod');
		}
		if (in_array('package', $embed)) {
			if (in_array('currency', $embed)) {
				$payments->with(['package' => fn ($query) => $query->with('currency')]);
			} else {
				$payments->with('package');
			}
		}
		
		$payments->when($isValid, fn ($query) => $query->valid());
		$payments->when($isActive, fn ($query) => $query->active());
		
		// Sorting
		$payments = $this->applySorting($payments, ['created_at'], $sort);
		
		$payments = $payments->paginate($perPage);
		$payments = PaginationHelper::adjustSides($payments);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$payments = setPaginationBaseUrl($payments);
		
		$collection = new EntityCollection(PaymentResource::class, $payments, $params);
		
		$message = ($payments->count() <= 0)
			? ($isSubscripting) ? t('no_subscriptions_found') : t('no_payments_found')
			: null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Get payment
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id, array $params = []): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		$payment = Payment::query()->where('id', $id);
		
		if (in_array('payable', $embed)) {
			$payment->with('payable');
		}
		if (in_array('paymentMethod', $embed)) {
			$payment->with('paymentMethod');
		}
		if (in_array('package', $embed)) {
			if (in_array('currency', $embed)) {
				$payment->with(['package' => fn ($query) => $query->with('currency')]);
			} else {
				$payment->with('package');
			}
		}
		
		$payment = $payment->first();
		
		abort_if(empty($payment), 404, t('payment_not_found'));
		
		$resource = new PaymentResource($payment, $params);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Store payment
	 *
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(PackageRequest $request, array $params = []): JsonResponse
	{
		/*
		 * IMPORTANT: Du to possible payment gateways' redirections,
		 * the API payment storage's endpoint is not call from the app's web version.
		 */
		$payableType = $params['payableType'] ?? null;
		$payableType = $request->input('payable_type', $payableType);
		$isPromoting = (str_ends_with($payableType, 'Post'));
		$isSubscripting = (str_ends_with($payableType, 'User'));
		
		// promotion
		if ($isPromoting) {
			/*
			 * Prevent developers to call the API payment endpoint to store payment
			 * from the web version of the app when the Single-Step Form is enabled.
			 */
			if (doesRequestIsFromWebClient()) {
				if (isSingleStepFormEnabled()) {
					$message = 'This endpoint cannot be called from the app\'s web version when the Single-Step Form is enabled.';
					abort(400, $message);
				}
			}
			
			/*
			 * The same way to store payment is use both API call and the Web Multi-Steps Form process
			 * i.e.: The payable ID and type are required
			 */
			$this->setPaymentSettingsForPromotion();
			
			return $this->multiStepsPaymentStore($request);
		}
		
		// subscription
		if ($isSubscripting) {
			$this->setPaymentSettingsForSubscription();
			
			return $this->storeSubscriptionPayment($request);
		}
		
		abort(400, 'Payable type not found.');
	}
}
