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

use App\Exceptions\Custom\CustomException;
use App\Models\Blacklist;
use App\Models\Post;
use App\Models\User;
use App\Services\Auth\App\Notifications\AccountBanned;

class BlacklistObserver extends BaseObserver
{
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Blacklist $blacklist
	 * @return void
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function saved(Blacklist $blacklist)
	{
		// Check if an email address has been banned
		if (
			!empty($blacklist->type)
			&& !empty($blacklist->entry)
			&& $blacklist->type == 'email'
		) {
			// Check if it is a valid email address
			if (filter_var($blacklist->entry, FILTER_VALIDATE_EMAIL)) {
				$exceptEmailDomains = [getDomain(), 'domain.tld', 'demosite.com', 'larapen.com'];
				$blacklistEmailDomain = str($blacklist->entry)->after('@');
				
				// Don't remove banned email address data for the "except" domains
				if (!in_array($blacklistEmailDomain, $exceptEmailDomains)) {
					// Delete the banned user related to the email address
					$user = User::query()->where('email', $blacklist->entry)->first();
					$userExistsAndIsNotAdmin = !doesUserHaveStaffPermission($user);
					if (!empty($user) && $userExistsAndIsNotAdmin) {
						
						// Send a notification to the user
						$sendNotificationOnUserBan = config('settings.auth.send_notification_on_user_ban');
						$sendNotificationOnUserBan = castToString($sendNotificationOnUserBan, 'none');
						if (in_array($sendNotificationOnUserBan, ['send', 'forceToSend'])) {
							try {
								$user->notify(new AccountBanned($user));
							} catch (\Throwable $e) {
								if ($sendNotificationOnUserBan == 'forceToSend') {
									throw new CustomException($e->getMessage());
								}
							}
						}
						
						// Delete the user
						$user->delete();
						
					}
					
					// Delete the banned user's listings related to the email address
					if (empty($user) || $userExistsAndIsNotAdmin) {
						$posts = Post::query()->where('email', $blacklist->entry);
						if ($posts->count() > 0) {
							foreach ($posts->cursor() as $post) {
								$post->delete();
							}
						}
					}
				}
			}
		}
		
		// Check if a phone number has been banned
		if (
			!empty($blacklist->type)
			&& !empty($blacklist->entry)
			&& $blacklist->type == 'phone'
		) {
			// Delete the banned user related to the phone number
			$user = User::query()->where('phone', $blacklist->entry)->first();
			$userExistsAndIsNotAdmin = !doesUserHaveStaffPermission($user);
			if (!empty($user) && $userExistsAndIsNotAdmin) {
				
				// Send a notification to the user
				$sendNotificationOnUserBan = config('settings.auth.send_notification_on_user_ban');
				$sendNotificationOnUserBan = castToString($sendNotificationOnUserBan, 'none');
				if (in_array($sendNotificationOnUserBan, ['send', 'forceToSend'])) {
					try {
						$user->notify(new AccountBanned($user));
					} catch (\Throwable $e) {
						if ($sendNotificationOnUserBan == 'forceToSend') {
							throw new CustomException($e->getMessage());
						}
					}
				}
				
				// Delete the user
				$user->delete();
				
			}
			
			// Delete the banned user's listings related to the phone number
			if (empty($user) || $userExistsAndIsNotAdmin) {
				$posts = Post::query()->where('phone', $blacklist->entry);
				if ($posts->count() > 0) {
					foreach ($posts->cursor() as $post) {
						$post->delete();
					}
				}
			}
		}
	}
}
