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

use App\Http\Controllers\Web\Admin\UserController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class Clearance
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$versionIntroducingAcl = (config('larapen.core.item.slug') == 'jobclass') ? '4.0.0' : '5.2.0';
		if (currentVersionIsLt($versionIntroducingAcl)) {
			return $next($request);
		}
		
		// If user has this //permission
		if (userHasSuperAdminPermissions()) {
			return $next($request);
		}
		
		$authUser = auth()->check() ? auth()->user() : null;
		
		// Get all admin panel routes that have permissions
		$routesPermissions = Permission::parseAdminPanelRoutesPermissions();
		if (!empty($routesPermissions)) {
			foreach ($routesPermissions as $route) {
				if (!isset($route['uri']) || !isset($route['permission']) || !isset($route['methods'])) {
					continue;
				}
				
				// If the current route found, ...
				if ($request->is($route['uri']) && in_array($request->method(), $route['methods'])) {
					
					// Check if user has permission to perform this action
					if (!doesUserHavePermission($authUser, $route['permission'])) {
						return $this->forbidden($request);
					}
					
				}
			}
		}
		
		// If the logged admin user has permissions to manage users and has not 'super-admin' role,
		// don't allow him to manage 'super-admin' role's users.
		if (isAdminPanel() && !empty($authUser)) {
			$userController = UserController::class;
			if (
				routeActionHas($userController . '@edit')
				|| routeActionHas($userController . '@update')
				|| routeActionHas($userController . '@show')
				|| routeActionHas($userController . '@destroy')
			) {
				// Get the current possible super-admin user ID
				$userId = request()->segment(3);
				if (!empty($userId) && is_numeric($userId)) {
					// If the logged admin user has not 'Role::getSuperAdminRole()' role...
					if (!doesUserHaveSuperAdminPermission($authUser)) {
						try {
							$user = User::query()
								->withoutGlobalScopes([VerifiedScope::class])
								->where('id', $userId)
								->role(Role::getSuperAdminRole())
								->first(['id', 'created_at']);
							
							// If the current User ID is for a user that has the 'Role::getSuperAdminRole()' role,
							// don't allow the logged user (admin) to manage him
							// (since he doesn't have 'Role::getSuperAdminRole()' role).
							if (!empty($user)) {
								return $this->forbidden($request);
							}
						} catch (Throwable $e) {
						}
					}
				}
			}
		}
		
		return $next($request);
	}
	
	/**
	 * Forbidden Access
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
	 */
	private function forbidden(Request $request)
	{
		$message = trans('admin.forbidden');
		
		$previousUrl = url()->previous();
		$previousUrl = urlBuilder($previousUrl)->removeAllParameters()->toString();
		
		$currentUrl = url()->current();
		$currentUrl = urlBuilder($currentUrl)->removeAllParameters()->toString();
		
		$loginUrl = urlGen()->signIn();
		
		$doesUserNeedToBeLogout = ($previousUrl == $currentUrl || $previousUrl == $loginUrl);
		if ($doesUserNeedToBeLogout) {
			$previousUrl = urlBuilder($loginUrl)->setParameters([
				'permission' => 'forbidden',
				'uid'        => uniqid('', true),
			]);
			
			if (!isFromAjax($request)) {
				logoutSession();
			}
		}
		
		if (isFromAjax($request)) {
			return ajaxResponse()->text($message, Response::HTTP_FORBIDDEN);
		}
		
		notification($message, 'error');
		
		return redirect()->to($previousUrl);
	}
}
