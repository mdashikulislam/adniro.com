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

namespace App\Observers;

use App\Models\UserSocialLogin;

class UserSocialLoginObserver extends BaseObserver
{
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param UserSocialLogin $socialLogin
	 * @return void
	 */
	public function deleting(UserSocialLogin $socialLogin)
	{
		$userId = $socialLogin->user_id ?? -1;
		$otherSocialLoginsDontExist = UserSocialLogin::query()->where('user_id', $userId)->doesntExist();
		
		/*
		 * Delete the user himself:
		 * - If the user has no other services connected
		 * - and if his password is not set
		 */
		if ($otherSocialLoginsDontExist) {
			/** @var \App\Models\User|null $user */
			$user = $socialLogin->user ?? null;
			if (!empty($user)) {
				if (empty($user->password)) {
					// $user->delete(); // Disabled for the moment!
				}
			}
		}
	}
}
