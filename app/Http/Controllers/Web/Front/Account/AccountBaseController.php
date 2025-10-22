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

use App\Http\Controllers\Web\Front\FrontController;
use App\Services\UserService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Illuminate\Support\Collection;

abstract class AccountBaseController extends FrontController
{
	protected UserService $userService;
	
	/**
	 * @param \App\Services\UserService $userService
	 */
	public function __construct(UserService $userService)
	{
		parent::__construct();
		
		$this->userService = $userService;
		
		if (auth()->check()) {
			$this->leftMenuInfo();
		}
		
		// Get Page Current Path
		$pagePath = (request()->segment(1) == 'account') ? request()->segment(3) : '';
		$pagePath = (request()->segment(2) == 'saved-posts') ? 'saved-posts' : $pagePath;
		view()->share('pagePath', $pagePath);
		
		// Breadcrumb
		BreadcrumbFacade::setHome(t('home'), url('/'))
			->add(t('my_account'), urlGen()->accountOverview());
	}
	
	/**
	 * @return void
	 */
	public function leftMenuInfo(): void
	{
		$authUser = auth()->user();
		if (empty($authUser)) return;
		
		// Get user's stats
		$data = getServiceData($this->userService->stats($authUser->getAuthIdentifier()));
		
		// Retrieve the user's stats
		$userStats = data_get($data, 'result');
		
		// Format the account's sidebar menu
		$accountMenu = collect();
		if (isset($this->userMenu)) {
			$accountMenu = $this->userMenu->groupBy('group');
			$accountMenu = $accountMenu->map(function ($group) use ($userStats) {
				return $group->map(function ($item) use ($userStats) {
					$isActive = (isset($item['isActive']) && $item['isActive']);
					$countVar = isset($item['countVar']) ? data_get($userStats, $item['countVar']) : null;
					$cssClass = !empty($item['countCustomClass']) ? $item['countCustomClass'] . ' hide' : '';
					
					$item['isActive'] = $isActive;
					$item['countVar'] = $countVar;
					$item['cssClass'] = $cssClass;
					
					return $item;
				})->reject(function ($item) {
					return (is_numeric($item['countVar']) && $item['countVar'] < 0);
				});
			})->reject(function ($group) {
				return ($group instanceof Collection) ? $group->isEmpty() : empty($group);
			});
		}
		
		// Export data to views
		view()->share('userStats', $userStats);
		view()->share('accountMenu', $accountMenu);
	}
}
