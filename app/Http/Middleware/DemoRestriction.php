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

namespace App\Http\Middleware;

use App\Http\Controllers\Api\ContactController as ApiContactController;
use App\Http\Controllers\Api\PostController as ApiPostController;
use App\Http\Controllers\Api\ThreadController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Web\Admin\ActionController;
use App\Http\Controllers\Web\Admin\BackupController;
use App\Http\Controllers\Web\Admin\BlacklistController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\InlineRequestController;
use App\Http\Controllers\Web\Admin\LanguageController;
use App\Http\Controllers\Web\Admin\MenuController;
use App\Http\Controllers\Web\Admin\MenuItemController;
use App\Http\Controllers\Web\Admin\PermissionController;
use App\Http\Controllers\Web\Admin\PluginController;
use App\Http\Controllers\Web\Admin\RoleController;
use App\Http\Controllers\Web\Admin\SectionController;
use App\Http\Controllers\Web\Admin\SettingController;
use App\Http\Controllers\Web\Front\Account\ClosingController;
use App\Http\Controllers\Web\Front\Account\MessagesController;
use App\Http\Controllers\Web\Front\Account\PostsController;
use App\Http\Controllers\Web\Front\Account\PreferencesController;
use App\Http\Controllers\Web\Front\Account\ProfileController;
use App\Http\Controllers\Web\Front\Account\SecurityController;
use App\Http\Controllers\Web\Front\Page\ContactController;
use App\Http\Controllers\Web\Front\Post\ReportController;
use Closure;
use Illuminate\Http\Request;
use Larapen\Impersonate\Controllers\ImpersonateController;

class DemoRestriction
{
	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if (!isDemoDomain()) {
			return $next($request);
		}
		
		if (!$this->isRestricted()) {
			return $next($request);
		}
		
		$message = t('demo_mode_message');
		
		if (isFromApi()) {
			
			$result = [
				'success' => false,
				'message' => $message,
				'result'  => null,
			];
			
			return response()->json($result, 403, [], JSON_UNESCAPED_UNICODE);
			
		} else {
			if (isFromAjax($request)) {
				$result = [
					'success' => false,
					'error'   => $message,
				];
				
				return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
			} else {
				notification($message, 'info');
				
				return redirect()->back();
			}
		}
	}
	
	/**
	 * @return bool
	 */
	private function isRestricted(): bool
	{
		$isRestricted = false;
		
		$frontRoutesRestricted = $this->frontRoutesRestricted();
		foreach ($frontRoutesRestricted as $route) {
			if (routeActionHas($route)) {
				$isRestricted = true;
				break;
			}
		}
		
		$guard = getAuthGuard();
		$authUser = auth($guard)->check() ? auth($guard)->user() : null;
		
		if (!empty($authUser)) {
			if (
				doesUserHaveStaffPermission($authUser)
				&& isDemoSuperAdmin($authUser)
			) {
				return false;
			}
			
			$adminRoutesRestricted = $this->adminRoutesRestricted();
			foreach ($adminRoutesRestricted as $route) {
				if (
					(
						str_starts_with($route, '@')
						&& routeActionHas(getClassNamespaceName(DashboardController::class))
						&& routeActionHas($route)
					)
					|| (
						!str_starts_with($route, '@')
						&& routeActionHas($route)
					)
				) {
					$isRestricted = true;
					break;
				}
			}
			
			if (isDemoEmailAddress($authUser->email ?? null)) {
				$demoUsersRoutesRestricted = $this->demoUsersRoutesRestricted();
				foreach ($demoUsersRoutesRestricted as $route) {
					if (routeActionHas($route)) {
						$isRestricted = true;
						break;
					}
				}
			}
		}
		
		return $isRestricted;
	}
	
	/**
	 * @return string[]
	 */
	private function frontRoutesRestricted(): array
	{
		return [
			// api
			ApiContactController::class . '@sendForm',
			ApiContactController::class . '@sendReport',
			ApiContactController::class . '@submitForm',
			ApiContactController::class . '@submitReport',
			// ThreadController::class . '@store',
			
			// web
			ContactController::class . '@postForm',
			ContactController::class . '@submitForm',
			ReportController::class . '@sendReport',
			ReportController::class . '@submitReport',
			// MessagesController::class . '@store',
		];
	}
	
	/**
	 * @return string[]
	 */
	private function adminRoutesRestricted(): array
	{
		return [
			// admin
			'@store',
			'@update',
			'@destroy',
			'@saveReorder',
			'@resendEmailVerification',
			'@resendPhoneVerification',
			'@seedPredefinedPermissions',
			'@rebuildNestedSetNodes',
			RoleController::class . '@store',
			RoleController::class . '@update',
			RoleController::class . '@destroy',
			PermissionController::class . '@store',
			PermissionController::class . '@update',
			PermissionController::class . '@destroy',
			ActionController::class,
			BackupController::class . '@create',
			BackupController::class . '@download',
			BackupController::class . '@delete',
			BlacklistController::class . '@banUser',
			InlineRequestController::class,
			LanguageController::class . '@syncFilesLines',
			LanguageController::class . '@update',
			LanguageController::class . '@updateTexts',
			MenuController::class . '@store',
			MenuController::class . '@update',
			MenuController::class . '@destroy',
			MenuController::class . '@reset',
			MenuItemController::class . '@store',
			MenuItemController::class . '@update',
			MenuItemController::class . '@destroy',
			MenuItemController::class . '@reset',
			PluginController::class . '@install',
			PluginController::class . '@installWithCode',
			PluginController::class . '@installWithoutCode',
			PluginController::class . '@uninstall',
			PluginController::class . '@delete',
			SectionController::class . '@resetAll',
			SettingController::class . '@reset',
			
			// impersonate
			ImpersonateController::class,
			
			// plugins:domainmapping
			'domainmapping\app\Http\Controllers\Web\Admin\DomainController@createBulkCountriesSubDomain',
			'domainmapping\app\Http\Controllers\Web\Admin\DomainMetaTagController@generate',
			'domainmapping\app\Http\Controllers\Web\Admin\DomainMetaTagController@reset',
			'domainmapping\app\Http\Controllers\Web\Admin\DomainSectionController@generate',
			'domainmapping\app\Http\Controllers\Web\Admin\DomainSectionController@reset',
			'domainmapping\app\Http\Controllers\Web\Admin\DomainSettingController@generate',
			'domainmapping\app\Http\Controllers\Web\Admin\DomainSettingController@reset',
		];
	}
	
	/**
	 * @return string[]
	 */
	private function demoUsersRoutesRestricted(): array
	{
		return [
			// api
			UserController::class . '@update',
			UserController::class . '@updatePhoto',
			UserController::class . '@removePhoto',
			UserController::class . '@changePassword',
			UserController::class . '@setupTwoFactor',
			UserController::class . '@updatePreferences',
			UserController::class . '@saveThemePreference',
			UserController::class . '@destroy',
			// ---
			ApiPostController::class . '@destroy',
			
			// web
			ProfileController::class . '@updateDetails',
			ProfileController::class . '@updatePhoto',
			ProfileController::class . '@deletePhoto',
			SecurityController::class . '@changePassword',
			SecurityController::class . '@setupTwoFactor',
			PreferencesController::class . '@updatePreferences',
			PreferencesController::class . '@saveThemePreference',
			ClosingController::class . '@postForm',
			// ---
			PostsController::class . '@destroy',
		];
	}
}
