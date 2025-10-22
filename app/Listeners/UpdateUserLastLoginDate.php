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

namespace App\Listeners;

use App\Events\UserWasLogged;
use App\Helpers\Common\Date\TimeZoneManager;
use Illuminate\Support\Carbon;
use Throwable;

class UpdateUserLastLoginDate
{
	/**
	 * Create the event listener.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}
	
	/**
	 * Handle the event.
	 *
	 * @param UserWasLogged $event
	 * @return void
	 */
	public function handle(UserWasLogged $event)
	{
		$this->updateLastLoginDate($event->user);
	}
	
	/**
	 * @param $user
	 * @return void
	 */
	private function updateLastLoginDate($user): void
	{
		try {
			$user->last_login_at = now();
			$user->save();
		} catch (Throwable $e) {
		}
	}
}
