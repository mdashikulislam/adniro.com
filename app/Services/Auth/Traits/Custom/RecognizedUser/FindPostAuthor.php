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

namespace App\Services\Auth\Traits\Custom\RecognizedUser;

use App\Models\User;
use App\Services\Auth\Traits\Custom\RecognizedUser\FindPostAuthor\FillMissingPostData;
use App\Services\Auth\Traits\Custom\RecognizedUser\FindPostAuthor\FillMissingUserData;

trait FindPostAuthor
{
	use FillMissingUserData, FillMissingPostData;
	
	/**
	 * After Email or Phone verification (from new Listing creation),
	 * Match the user's listings (posted as guest) & the user's data (if missed)
	 *
	 * @param $post
	 * @return User|null
	 */
	protected function findPostAuthor($post): ?User
	{
		if (empty($post)) {
			return null;
		}
		
		$user = null;
		
		// Get (verified) user by (verified) email
		$isVerifiedEmail = (
			config('settings.mail.email_verification') == '1'
			&& !empty($post->email)
			&& !empty($post->email_verified_at)
		);
		if ($isVerifiedEmail) {
			$user = User::query()->where('email', $post->email)->first();
		}
		
		// Get (verified) user by (verified) phone number
		$isVerifiedPhone = (
			config('settings.sms.phone_verification') == '1'
			&& !empty($post->phone)
			&& !empty($post->phone_verified_at)
		);
		if ($isVerifiedPhone) {
			if (empty($user)) {
				$user = User::query()->where('phone', $post->phone)->first();
			}
		}
		
		return $user;
	}
}
