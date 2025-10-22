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

use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;

trait MatchAuthorPosts
{
	/**
	 * After Email or Phone verification (from new user registration),
	 * Match the user's listings (that he was published as guest)
	 *
	 * WARNING: For security reasons, never call this method if the email and|or phone number verification is not enabled
	 *
	 * @param $user
	 * @return void
	 */
	protected function matchAuthorPosts($user): void
	{
		if (empty($user)) {
			return;
		}
		
		// Update listings created with this email
		$isVerifiedEmail = (
			config('settings.mail.email_verification') == '1'
			&& !empty($user->email)
			&& !empty($user->email_verified_at)
		);
		if ($isVerifiedEmail) {
			Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('email', $user->email)
				->update(['user_id' => $user->id]);
		}
		
		// Update listings created with this phone number (for this country)
		$isVerifiedPhone = (
			config('settings.sms.phone_verification') == '1'
			&& !empty($user->phone)
			&& !empty($user->phone_verified_at)
		);
		if ($isVerifiedPhone) {
			Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('phone', $user->phone)
				->update(['user_id' => $user->id]);
		}
	}
}
