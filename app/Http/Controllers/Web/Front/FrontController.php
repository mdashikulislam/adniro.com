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

namespace App\Http\Controllers\Web\Front;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Front\Traits\CommonTrait;
use App\Http\Controllers\Web\Front\Traits\EnvFileTrait;
use App\Http\Controllers\Web\Front\Traits\HasHeaderNavbar;
use App\Http\Controllers\Web\Front\Traits\RobotsTxtTrait;
use App\Http\Controllers\Web\Front\Traits\SettingsTrait;
use App\Services\MenuItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FrontController extends Controller
{
	use HasHeaderNavbar, SettingsTrait, EnvFileTrait, RobotsTxtTrait, CommonTrait;
	
	public Request $request;
	public array $data = [];
	protected Collection $userMenu;
	
	public function __construct()
	{
		// Configure navbar options based on the current context and page type
		$this->configureNavbarOptionsFromContext();
		
		// Set the storage disk
		$this->setStorageDisk();
		
		// Check & Change the App Key (If needed)
		$this->checkAndGenerateAppKey();
		
		// Check & Update the '/.env' file
		$this->checkDotEnvEntries();
		
		// Check & Update the '/public/robots.txt' file
		$this->checkRobotsTxtFile();
		
		// Load Localization Data first
		// Check out the SetCountryLocale Middleware
		$this->applyFrontSettings();
		
		// Share Global Menus Entries
		$this->shareGlobalMenus();
		
		// Get & Share Users Menu
		$this->userMenu = $this->getUserMenu();
		view()->share('userMenu', $this->userMenu);
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = [];
		
		// Add the 'Currency Exchange' plugin middleware
		if (config('plugins.currencyexchange.installed')) {
			$array[] = 'currencies';
			$array[] = 'currencyExchange';
		}
		
		// Add the 'Domain Mapping' plugin middleware
		if (config('plugins.domainmapping.installed')) {
			$array[] = 'domain.verification';
		}
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * @return void
	 */
	private function shareGlobalMenus(): void
	{
		if (request()->expectsJson()) {
			return;
		}
		
		$menuLocation = [
			'header' => 'headerMenu',
			'footer' => 'footerMenu',
		];
		
		$params = [
			'embed' => 'menu,parent,ancestors,children',
			'sort'  => '-lft',
		];
		
		foreach ($menuLocation as $location => $locationVar) {
			$data = [];
			try {
				$data = getServiceData((new MenuItemService())->getEntries(location: $location, params: $params));
			} catch (\Throwable $e) {
			}
			
			$locationVarValue = data_get($data, 'result') ?? [];
			view()->share($locationVar, $locationVarValue);
		}
	}
	
	/**
	 * @return \Illuminate\Support\Collection
	 */
	private function getUserMenu(): Collection
	{
		if (!auth()->check()) {
			return collect();
		}
		
		if (request()->expectsJson()) {
			return collect();
		}
		
		$authUser = auth()->user();
		
		$menuArray = [
			[
				'name'       => t('my_listings'),
				'url'        => url(urlGen()->getAccountBasePath() . '/posts/list'),
				'icon'       => 'fa-solid fa-list',
				'group'      => t('my_listings'),
				'countVar'   => 'posts.published',
				'inDropdown' => true,
				'isActive'   => (request()->segment(3) == 'list'),
			],
			[
				'name'       => t('pending_approval'),
				'url'        => url(urlGen()->getAccountBasePath() . '/posts/pending-approval'),
				'icon'       => 'bi bi-hourglass-split',
				'group'      => t('my_listings'),
				'countVar'   => 'posts.pendingApproval',
				'inDropdown' => true,
				'isActive'   => (request()->segment(3) == 'pending-approval'),
			],
			[
				'name'       => t('archived_listings'),
				'url'        => url(urlGen()->getAccountBasePath() . '/posts/archived'),
				'icon'       => 'bi bi-calendar-x',
				'group'      => t('my_listings'),
				'countVar'   => 'posts.archived',
				'inDropdown' => false,
				'isActive'   => (request()->segment(3) == 'archived'),
			],
			[
				'name'       => t('favourite_listings'),
				'url'        => url(urlGen()->getAccountBasePath() . '/saved-posts'),
				'icon'       => 'bi bi-bookmarks',
				'group'      => t('my_listings'),
				'countVar'   => 'posts.favourite',
				'inDropdown' => true,
				'isActive'   => (request()->segment(2) == 'saved-posts'),
			],
			[
				'name'             => t('messenger'),
				'url'              => url(urlGen()->getAccountBasePath() . '/messages'),
				'icon'             => 'bi bi-chat-text',
				'group'            => t('my_listings'),
				'countVar'         => 0,
				'countCustomClass' => ' count-threads-with-new-messages',
				'inDropdown'       => true,
				'isActive'         => (request()->segment(2) == 'messages'),
			],
			[
				'name'       => t('saved_searches'),
				'url'        => url(urlGen()->getAccountBasePath() . '/saved-searches'),
				'icon'       => 'bi bi-bell fs-6',
				'group'      => t('my_listings'),
				'countVar'   => 'savedSearch',
				'inDropdown' => false,
				'isActive'   => (request()->segment(2) == 'saved-searches'),
			],
			[
				'name'       => t('promotion'),
				'url'        => url(urlGen()->getAccountBasePath() . '/transactions/promotion'),
				'icon'       => 'fa-solid fa-coins',
				'group'      => t('Transactions'),
				'countVar'   => 'transactions.promotion',
				'inDropdown' => false,
				'isActive'   => (request()->segment(2) == 'transactions' && request()->segment(3) == 'promotion'),
			],
			[
				'name'       => t('subscription'),
				'url'        => url(urlGen()->getAccountBasePath() . '/transactions/subscription'),
				'icon'       => 'fa-solid fa-coins',
				'group'      => t('Transactions'),
				'countVar'   => 'transactions.subscription',
				'inDropdown' => false,
				'isActive'   => (request()->segment(2) == 'transactions' && request()->segment(3) == 'subscription'),
			],
			[
				'name'       => t('my_account'),
				'url'        => urlGen()->accountOverview(),
				'icon'       => 'bi bi-person-lines-fill',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => true,
				'isActive'   => (
					(request()->segment(1) == urlGen()->getAccountBasePath() && request()->segment(2) == null)
					|| request()->segment(2) == 'overview'
				),
			],
			[
				'name'       => trans('auth.profile'),
				'url'        => urlGen()->accountProfile(),
				'icon'       => 'bi bi-person-circle',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => true,
				'isActive'   => (request()->segment(2) == 'profile'),
			],
			[
				'name'       => trans('auth.security'),
				'url'        => urlGen()->accountSecurity(),
				'icon'       => 'bi bi-shield-lock',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => true,
				'isActive'   => (request()->segment(2) == 'security'),
			],
			[
				'name'       => trans('auth.preferences'),
				'url'        => urlGen()->accountPreferences(),
				'icon'       => 'bi bi-sliders',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => true,
				'isActive'   => (request()->segment(2) == 'preferences'),
			],
		];
		
		$connections = [];
		if (socialLogin()->isEnabled()) {
			$connections = [
				'name'       => trans('auth.linked_accounts'),
				'url'        => urlGen()->accountLinkedAccounts(),
				'icon'       => 'bi bi-plugin',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => false,
				'isActive'   => (request()->segment(2) == 'linked-accounts'),
			];
		}
		
		if (app('impersonate')->isImpersonating()) {
			$logOut = [
				'name'       => t('Leave'),
				'url'        => route('impersonate.leave'),
				'icon'       => 'bi bi-box-arrow-right',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => true,
				'isActive'   => false,
			];
		} else {
			$logOut = [
				'name'       => trans('auth.log_out'),
				'url'        => urlGen()->signOut(),
				'icon'       => 'bi bi-box-arrow-right',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => true,
				'isActive'   => false,
			];
		}
		
		$closeAccount = [];
		if (isAccountClosureEnabled()) {
			$closeAccount = [
				'name'       => t('close_account'),
				'url'        => urlGen()->accountClosing(),
				'icon'       => 'bi bi-person-x fs-6',
				'group'      => t('my_account'),
				'countVar'   => null,
				'inDropdown' => false,
				'isActive'   => (request()->segment(2) == 'closing'),
			];
		}
		
		$adminPanel = [];
		if (doesUserHaveStaffPermission($authUser)) {
			$adminPanel = [
				'name'       => t('admin_panel'),
				'url'        => urlGen()->adminUrl('/'),
				'icon'       => 'fa-solid fa-gears',
				'group'      => t('admin_panel'),
				'countVar'   => null,
				'inDropdown' => true,
				'isActive'   => false,
			];
		}
		
		// Merge all arrays
		array_push($menuArray, $connections, $logOut, $closeAccount, $adminPanel);
		
		// Set missed information
		return collect($menuArray)
			->reject(fn ($item) => empty($item))
			->map(function ($item) {
				// countCustomClass
				$item['countCustomClass'] = $item['countCustomClass'] ?? '';
				
				$accountBasePath = urlGen()->getAccountBasePath();
				
				// path
				// |(account.*)|ui
				$matches = [];
				preg_match('|(' . $accountBasePath . '.*)|ui', $item['url'], $matches);
				$item['path'] = $matches[1] ?? '-1';
				$item['path'] = str_replace([$accountBasePath, '/'], '', $item['path']);
				
				return $item;
			});
	}
}
