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

use App\Models\Blacklist;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BannedUser
{
	protected string $message = 'account_suspended_due_to_violation';
	
	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		// Exception for Install & Upgrade Routes
		if (isFromInstallOrUpgradeProcess()) {
			return $next($request);
		}
		
		$guard = getAuthGuard();
		$authUser = auth($guard)->check() ? auth($guard)->user() : null;
		
		if (empty($authUser)) {
			return $next($request);
		}
		
		$this->message = t($this->message);
		
		// Block the access if a User is suspended (as registered User)
		if ($this->doesUserIsSuspended($request, $authUser)) {
			if (isFromApi()) {
				return apiResponse()->forbidden($this->message);
			}
			
			if (isFromAjax($request)) {
				return ajaxResponse()->text($this->message, Response::HTTP_UNAUTHORIZED);
			}
			
			notification($this->message, 'error');
			
			return redirect()->guest(urlGen()->signIn());
		}
		
		// Block & Delete the access if a User is banned (from Blacklist with its email address)
		if ($this->doesUserIsBanned($request, $authUser)) {
			if (isFromApi()) {
				return apiResponse()->forbidden($this->message);
			}
			
			if (isFromAjax($request)) {
				return ajaxResponse()->text($this->message, Response::HTTP_UNAUTHORIZED);
			}
			
			notification($this->message, 'error');
			
			return redirect()->guest(urlGen()->signIn());
		}
		
		return $next($request);
	}
	
	/**
	 * Check if the user is suspended
	 * Block the access if User is suspended (as registered User)
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param $authUser
	 * @return bool
	 */
	private function doesUserIsSuspended(Request $request, $authUser): bool
	{
		return !empty($authUser->suspended_at);
	}
	
	/**
	 * Check if the user is banned
	 * Block & Delete the access if a User is banned (from Blacklist with its email address)
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param $authUser
	 * @return bool
	 */
	private function doesUserIsBanned(Request $request, $authUser): bool
	{
		// Cache Parameters
		$cacheParams = [
			'action' => 'get.blacklist',
			'type'   => 'email',
			'entry'  => $authUser->email,
		];
		
		// Check if the user's email address has been banned
		$bannedUser = caching()->remember(Blacklist::class, $cacheParams, function () use ($authUser) {
			return Blacklist::ofType('email')->where('entry', $authUser->email)->first();
		});
		
		if (empty($bannedUser)) return false;
		
		$user = User::find($authUser->id);
		if (empty($user)) return false;
		
		$user->delete();
		
		return true;
	}
}
