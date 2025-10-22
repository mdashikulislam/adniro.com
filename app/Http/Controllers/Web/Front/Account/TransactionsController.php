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

namespace App\Http\Controllers\Web\Front\Account;

use App\Services\PaymentService;
use App\Services\UserService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class TransactionsController extends AccountBaseController
{
	protected PaymentService $paymentService;
	
	/**
	 * @param \App\Services\UserService $userService
	 * @param \App\Services\PaymentService $paymentService
	 */
	public function __construct(UserService $userService, PaymentService $paymentService)
	{
		parent::__construct($userService);
		
		$this->paymentService = $paymentService;
	}
	
	/**
	 * Promotions Transactions List
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		$paymentType = request()->segment(3);
		
		$isPromoting = ($paymentType == 'promotion');
		$isSubscripting = ($paymentType == 'subscription');
		
		// Get Payments
		$otherEmbed = $isSubscripting ? ',posts' : '';
		$queryParams = [
			'embed'       => 'payable,paymentMethod,package,currency' . $otherEmbed,
			'paymentType' => $paymentType,
			'sort'        => 'created_at',
		];
		$data = getServiceData($this->paymentService->getEntries(null, $queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		$appName = config('settings.app.name', 'Site Name');
		$title = ($isSubscripting) ? t('my_subs_transactions') : t('my_promo_transactions');
		$title = $title . ' - ' . $appName;
		$description = t('my_transactions_on', ['appName' => config('settings.app.name')]);
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		
		// Breadcrumb
		BreadcrumbFacade::add(t('Transactions'));
		
		return view(
			'front.account.transactions',
			compact('paymentType', 'isPromoting', 'isSubscripting', 'apiResult', 'apiMessage')
		);
	}
}
