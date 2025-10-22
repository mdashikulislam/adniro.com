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

namespace App\Services\Auth\Traits\Custom\RecognizedUser\FindPostAuthor;

use App\Models\Post;
use App\Models\User;

trait FillMissingPostData
{
	/**
	 * After Email or Phone verification (from new Listing creation),
	 * Match the user's listings (posted as guest) & the user's data (if missed)
	 *
	 * @param \App\Models\Post $post
	 * @param \App\Models\User|null $user
	 * @return void
	 */
	protected function fillMissingPostData(Post $post, ?User $user = null): void
	{
		if (empty($user)) {
			return;
		}
		
		// If the listing email address or phone number is verified
		// and that also the case for the account related to them (even the account user is not logged)
		// Then, attribute this listing to the user that has the same email address or the same phone number
		// And fill the listing's missing contact information with the account data.
		if (empty($post->user_id)) {
			$post->user_id = $user->id;
		}
		if (empty($post->email)) {
			$post->email = $user->email;
		}
		if (empty($post->phone)) {
			$post->phone = $user->phone;
		}
		if ($post->isDirty()) {
			$post->save();
		}
	}
}
