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

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Picture;
use App\Models\Post;
use App\Models\PostValue;
use App\Models\SavedPost;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Thread;
use App\Models\User;
use App\Notifications\PostActivated;
use App\Notifications\PostNotification;
use App\Notifications\PostReviewed;
use extras\plugins\reviews\app\Models\Review;
use Illuminate\Support\Facades\Notification;
use Throwable;

class PostObserver extends BaseObserver
{
	/**
	 * Listen to the Entry created event.
	 *
	 * @param Post $post
	 * @return void
	 */
	public function created(Post $post)
	{
		// Send Admin Notification Email
		if (config('settings.mail.admin_notification') == '1') {
			try {
				// Get all admin users
				$admins = User::permission(Permission::getStaffPermissions())->get();
				if ($admins->count() > 0) {
					Notification::send($admins, new PostNotification($post));
				}
			} catch (Throwable $e) {
			}
		}
	}
	
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param Post $post
	 * @return void
	 */
	public function deleting(Post $post)
	{
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		// Delete all the Post's Custom Fields Values
		$postValues = PostValue::where('post_id', $post->id)->get();
		if ($postValues->count() > 0) {
			foreach ($postValues as $postValue) {
				$postValue->delete();
			}
		}
		
		// Delete all Threads
		$messages = Thread::where('post_id', $post->id);
		if ($messages->count() > 0) {
			foreach ($messages->cursor() as $message) {
				$message->forceDelete();
			}
		}
		
		// Delete all Saved Posts
		$savedPosts = SavedPost::where('post_id', $post->id);
		if ($savedPosts->count() > 0) {
			foreach ($savedPosts->cursor() as $savedPost) {
				$savedPost->delete();
			}
		}
		
		// Delete all Pictures
		$pictures = Picture::where('post_id', $post->id);
		if ($pictures->count() > 0) {
			foreach ($pictures->cursor() as $picture) {
				$picture->delete();
			}
		}
		
		// Delete the Payment(s) of this Post
		$payments = Payment::query()
			->withoutGlobalScopes([ValidPeriodScope::class, StrictActiveScope::class])
			->whereMorphedTo('payable', $post)
			->get();
		if ($payments->count() > 0) {
			foreach ($payments as $payment) {
				$payment->delete();
			}
		}
		
		// Check Reviews plugin
		if (config('plugins.reviews.installed')) {
			try {
				// Delete the reviews of this Post
				$reviews = Review::where('post_id', $post->id);
				if ($reviews->count() > 0) {
					foreach ($reviews->cursor() as $review) {
						$review->delete();
					}
				}
			} catch (Throwable $e) {
			}
		}
		
		// Remove the listing media folder
		if (!empty($post->country_code) && !empty($post->id)) {
			$directoryPath = 'files/' . strtolower($post->country_code) . '/' . $post->id;
			
			if ($disk->exists($directoryPath)) {
				$disk->deleteDirectory($directoryPath);
			}
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Post $post
	 * @return void
	 */
	public function saved(Post $post)
	{
		$this->sendNotification($post);
		
		// Create a new email token if the post's email is marked as unverified
		if (empty($post->email_verified_at)) {
			if (empty($post->email_token)) {
				$post->email_token = generateToken(hashed: true);
				$post->saveQuietly();
			}
		}
		
		// Create a new phone token if the post's phone number is marked as unverified
		if (empty($post->phone_verified_at)) {
			if (empty($post->phone_token)) {
				$post->phone_token = generateOtp(defaultOtpLength());
				$post->saveQuietly();
			}
		}
	}
	
	/**
	 * Send Notification,
	 *
	 * - If the user's email address or phone number was not verified and has just been verified
	 *   (including when the user was recently created)
	 * - If the listing was not reviewed and has just been reviewed
	 *  (including when the listing was recently created)
	 *
	 * @param Post $post
	 * @return void
	 */
	private function sendNotification(Post $post)
	{
		try {
			if ($post->wasRecentlyCreated) {
				// verified
				$postWasNotVerified = true;
				
				// reviewed
				$postWasNotReviewed = true;
			} else {
				$original = $post->getOriginal();
				
				// verified
				$postEmailWasNotVerified = ($post->wasChanged('email_verified_at') && empty(data_get($original, 'email_verified_at')));
				$postPhoneWasNotVerified = ($post->wasChanged('phone_verified_at') && empty(data_get($original, 'phone_verified_at')));
				$postWasNotVerified = ($postEmailWasNotVerified || $postPhoneWasNotVerified);
				
				// reviewed
				$postWasNotReviewed = ($post->wasChanged('reviewed_at') && empty(data_get($original, 'reviewed_at')));
			}
			// verified
			$postIsVerified = (!empty($post->email_verified_at) && !empty($post->phone_verified_at));
			$postHasJustBeenVerified = ($postIsVerified && $postWasNotVerified);
			
			// reviewed
			$postIsReviewed = (!empty($post->reviewed_at));
			$postHasJustBeenReviewed = ($postIsReviewed && $postWasNotReviewed);
			
			if ($postIsVerified) {
				if (config('settings.listing_form.listings_review_activation') == '1') {
					if ($postHasJustBeenReviewed) {
						$post->notify(new PostReviewed($post));
					} else {
						if ($postHasJustBeenVerified) {
							$post->notify(new PostActivated($post));
						}
					}
				} else {
					if ($postHasJustBeenVerified) {
						$post->notify(new PostReviewed($post));
					}
				}
			}
		} catch (Throwable $e) {
			abort(500, $e->getMessage());
		}
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param Post $post
	 * @return void
	 */
	public function deleted(Post $post)
	{
		// ...
	}
}
