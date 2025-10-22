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
use App\Exceptions\Custom\ModelOperationCancelledException;
use App\Helpers\Common\Arr;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Post;
use App\Models\SavedPost;
use App\Models\SavedSearch;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\ThreadMessage;
use App\Models\ThreadParticipant;
use App\Models\User;
use App\Observers\Traits\HasImageWithThumbs;
use App\Services\Auth\App\Notifications\AccountActivated;
use App\Services\Auth\App\Notifications\AccountSuspended;
use App\Services\Auth\App\Notifications\NewUserRegistered;
use App\Services\Auth\App\Notifications\TwoFactorSetup;
use App\Services\Auth\Traits\Custom\Verification\Metadata;
use extras\plugins\reviews\app\Models\Review;
use Illuminate\Support\Facades\Notification;
use Throwable;

class UserObserver extends BaseObserver
{
	use Metadata, HasImageWithThumbs;
	
	/**
	 * Listen to the Entry created event.
	 *
	 * @param User $user
	 * @return void
	 */
	public function created(User $user)
	{
		// Send Admin Notification Email
		if (config('settings.mail.admin_notification') == '1') {
			try {
				// Get all admin users
				$admins = User::permission(Permission::getStaffPermissions())->get();
				if ($admins->count() > 0) {
					Notification::send($admins, new NewUserRegistered($user));
				}
			} catch (Throwable $t) {
			}
		}
	}
	
	/**
	 * Listen to the Entry updating event.
	 *
	 * @param User $user
	 * @return void
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function updating(User $user)
	{
		// Delete the user's old photo/avatar if new file is filled
		if ($user->isDirty('photo_path')) {
			// Get the original object values
			$original = $user->getOriginal();
			$oldFilePath = $original['photo_path'] ?? null;
			
			if (!empty($oldFilePath)) {
				$defaultPicture = config('larapen.media.avatar');
				$this->removePictureWithItsThumbs($oldFilePath, $defaultPicture);
			}
		}
		
		// Send Two-Factor Authentication notification
		if (Arr::keyExists('two_factor_enabled', $user)) {
			if ($user->isDirty('two_factor_enabled')) {
				$isTwoFactorEnabled = ($user->two_factor_enabled == 1);
				try {
					$user->notify(new TwoFactorSetup($user, enable: $isTwoFactorEnabled));
				} catch (Throwable $e) {
					// Disable the 2FA option for the user and Clear any existing OTP,
					// when the Mail or the SMS sending option is not properly configured to allow the OTP sending to the user
					if ($isTwoFactorEnabled) {
						$user->two_factor_enabled = 0;
						$user->resetTwoFactorCode();
					}
					
					throw new CustomException($e->getMessage());
				}
				
				// Clear any existing OTP, when user disable the 2FA option
				if (!$isTwoFactorEnabled) {
					$user->resetTwoFactorCode();
				}
			}
		}
		
		// Send user suspension notification
		if (Arr::keyExists('suspended_at', $user)) {
			if ($user->isDirty('suspended_at')) {
				$isSuspended = !empty($user->suspended_at);
				if ($isSuspended) {
					$sendNotificationOnUserSuspension = config('settings.auth.send_notification_on_user_suspension');
					$sendNotificationOnUserSuspension = castToString($sendNotificationOnUserSuspension, 'none');
					if (in_array($sendNotificationOnUserSuspension, ['send', 'forceToSend'])) {
						try {
							$user->notify(new AccountSuspended($user));
						} catch (\Throwable $e) {
							if ($sendNotificationOnUserSuspension == 'forceToSend') {
								throw new CustomException($e->getMessage());
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param User $user
	 * @return void
	 * @throws \App\Exceptions\Custom\ModelOperationCancelledException
	 */
	public function deleting(User $user)
	{
		// Prevent admin user to delete his account himself
		if (isFromAdminPanel()) {
			$message = null;
			$authUser = auth()->user();
			if (!empty($authUser)) {
				if (doesUserHaveStaffPermission($authUser)) {
					if ($authUser->getAuthIdentifier() == $user->id) {
						$message = trans('admin.your_account_cannot_be_deleted_by_yourself');
					}
				} else {
					$message = trans('admin.forbidden');
				}
			} else {
				$message = trans('admin.unauthorized');
			}
			if (isDemoDomain()) {
				if (in_array($user->email, getDemoEmailAddresses())) {
					$message = t('demo_mode_message');
				}
			}
			if (!empty($message)) {
				// Use Exceptions to propagate error message in the Controller
				// Or use "return false;" to cancel operation without message.
				throw new ModelOperationCancelledException($message);
			}
		}
		
		// Revoke all the user's tokens
		try {
			$user->tokens()->delete();
		} catch (Throwable $e) {
		}
		
		// Revoke all the user's social logins
		try {
			$user->socialLogins()->delete();
		} catch (Throwable $e) {
		}
		
		// Delete the user's photo
		if (!empty($user->photo_path)) {
			$defaultPicture = config('larapen.media.avatar');
			$this->removePictureWithItsThumbs($user->photo_path, $defaultPicture);
		}
		
		// Delete all user's Posts
		$posts = Post::query()
			->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
			->where('user_id', $user->id);
		if ($posts->count() > 0) {
			foreach ($posts->cursor() as $post) {
				$post->delete();
			}
		}
		
		// Delete all user's Messages
		$messages = ThreadMessage::where('user_id', $user->id);
		if ($messages->count() > 0) {
			foreach ($messages->cursor() as $message) {
				$message->forceDelete();
			}
		}
		
		// Delete all user as Participant
		$participants = ThreadParticipant::where('user_id', $user->id);
		if ($participants->count() > 0) {
			foreach ($participants->cursor() as $participant) {
				$participant->forceDelete();
			}
		}
		
		// Delete all user's Saved Posts
		$savedPosts = SavedPost::where('user_id', $user->id);
		if ($savedPosts->count() > 0) {
			foreach ($savedPosts->cursor() as $savedPost) {
				$savedPost->delete();
			}
		}
		
		// Delete all user's Saved Searches
		$savedSearches = SavedSearch::where('user_id', $user->id);
		if ($savedSearches->count() > 0) {
			foreach ($savedSearches->cursor() as $savedSearch) {
				$savedSearch->delete();
			}
		}
		
		// Delete the Payment(s) of this User
		$payments = Payment::query()
			->withoutGlobalScopes([ValidPeriodScope::class, StrictActiveScope::class])
			->whereMorphedTo('payable', $user)
			->get();
		if ($payments->count() > 0) {
			foreach ($payments as $payment) {
				$payment->delete();
			}
		}
		
		// Check the Reviews Plugin
		if (config('plugins.reviews.installed')) {
			try {
				// Delete the reviews of this User
				$reviews = Review::where('user_id', $user->id);
				if ($reviews->count() > 0) {
					foreach ($reviews->cursor() as $review) {
						$review->delete();
					}
				}
			} catch (Throwable $e) {
			}
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param User $user
	 * @return void
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function saved(User $user)
	{
		$this->sendNotification($user);
		
		// Create a new email token if the user's email is marked as unverified
		if (empty($user->email_verified_at)) {
			if (empty($user->email_token)) {
				$user->email_token = generateToken(hashed: true);
				$user->saveQuietly();
			}
		}
		
		// Create a new phone token if the user's phone number is marked as unverified
		if (empty($user->phone_verified_at)) {
			if (empty($user->phone_token)) {
				$user->phone_token = generateOtp(defaultOtpLength());
				$user->saveQuietly();
			}
		}
	}
	
	/**
	 * Send Notification,
	 *
	 * - If the user's email address or phone number was not verified and has just been verified
	 *   (including when the user was recently created)
	 *
	 * @param User $user
	 * @return void
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	private function sendNotification(User $user): void
	{
		try {
			if ($user->wasRecentlyCreated) {
				$userWasNotVerified = true;
			} else {
				$original = $user->getOriginal();
				$userEmailWasNotVerified = ($user->wasChanged('email_verified_at') && empty(data_get($original, 'email_verified_at')));
				$userPhoneWasNotVerified = ($user->wasChanged('phone_verified_at') && empty(data_get($original, 'phone_verified_at')));
				$userWasNotVerified = ($userEmailWasNotVerified || $userPhoneWasNotVerified);
			}
			$userIsVerified = (!empty($user->email_verified_at) && !empty($user->phone_verified_at));
			$userHasJustBeenVerified = ($userIsVerified && $userWasNotVerified);
			
			if ($userHasJustBeenVerified) {
				$user->notify(new AccountActivated($user));
			}
		} catch (Throwable $e) {
			throw new CustomException($e->getMessage());
		}
	}
}
