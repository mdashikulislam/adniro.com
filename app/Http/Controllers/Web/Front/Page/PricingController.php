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

namespace App\Http\Controllers\Web\Front\Page;

use App\Http\Controllers\Web\Front\FrontController;
use App\Services\PackageService;
use Larapen\LaravelMetaTags\Facades\MetaTag;
use Throwable;

class PricingController extends FrontController
{
	protected PackageService $packageService;
	
	/**
	 * @param \App\Services\PackageService $packageService
	 */
	public function __construct(PackageService $packageService)
	{
		parent::__construct();
		
		$this->packageService = $packageService;
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		// Get Listings' Promo Packages
		$promoPackagesData = $this->getPromotionPackages();
		$promoPackagesErrorMessage = data_get($promoPackagesData, 'message');
		$promoPackages = data_get($promoPackagesData, 'result.data');
		
		// Get Subscriptions Packages
		$subsPackagesData = $this->getSubscriptionPackages();
		$subsPackagesErrorMessage = data_get($subsPackagesData, 'message');
		$subsPackages = data_get($subsPackagesData, 'result.data');
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('pricing');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		// Open Graph
		try {
			$this->og->title($title)->description($description)->type('website');
		} catch (Throwable $e) {
		}
		view()->share('og', $this->og);
		
		return view(
			'front.pages.pricing',
			compact(
				'subsPackages',
				'subsPackagesErrorMessage',
				'promoPackages',
				'promoPackagesErrorMessage'
			)
		);
	}
	
	/**
	 * @return array
	 */
	private function getPromotionPackages(): array
	{
		// Get promotion packages
		$queryParams = [
			'embed'       => 'currency',
			'packageType' => 'promotion',
			'sort'        => '-lft',
		];
		$data = getServiceData($this->packageService->getEntries($queryParams));
		
		// Select a Package and go to previous URL
		// Add Listing possible URLs
		$addListingUriArray = [
			'create',
			'post\/create',
			'post\/create\/[^\/]+\/photos',
		];
		// Default Add Listing URL
		$addListingUrl = urlGen()->addPost();
		
		if (request()->filled('from')) {
			$path = request()->input('from');
			if (!empty($path) && is_string($path)) {
				foreach ($addListingUriArray as $uriPattern) {
					if (preg_match('#' . $uriPattern . '#', $path)) {
						$addListingUrl = url($path);
						break;
					}
				}
			}
		}
		
		view()->share('addListingUrl', $addListingUrl);
		
		return $data;
	}
	
	/**
	 * @return array
	 */
	private function getSubscriptionPackages(): array
	{
		// Get subscription packages
		$queryParams = [
			'embed'       => 'currency',
			'packageType' => 'subscription',
			'sort'        => '-lft',
		];
		
		return getServiceData($this->packageService->getEntries($queryParams));
	}
}
