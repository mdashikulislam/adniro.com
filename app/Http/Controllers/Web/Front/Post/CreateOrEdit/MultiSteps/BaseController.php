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

namespace App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps;

use App\Http\Controllers\Web\Front\FrontController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Traits\WizardTrait;
use App\Services\Payment\HasPaymentReferrers;
use App\Services\PostService;
use Illuminate\Support\Collection;

class BaseController extends FrontController
{
	use WizardTrait;
	use HasPaymentReferrers;
	
	protected PostService $postService;
	
	protected array $rawNavItems = [];
	protected array $navItems = [];
	protected int $stepsSegment = 3;
	protected array $allowedQueries = [];
	protected Collection $companies;
	
	/**
	 * @param \App\Services\PostService $postService
	 */
	public function __construct(PostService $postService)
	{
		parent::__construct();
		
		$this->postService = $postService;
		
		$this->commonQueries();
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = ['listing.form.type.check'];
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	private function commonQueries(): void
	{
		// Set the payment global settings
		$this->getPaymentReferrersData();
	}
}
