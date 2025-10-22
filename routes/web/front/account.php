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

use App\Http\Controllers\Web\Front\Account\ClosingController;
use App\Http\Controllers\Web\Front\Account\LinkedAccountsController;
use App\Http\Controllers\Web\Front\Account\MessagesController;
use App\Http\Controllers\Web\Front\Account\OverviewController;
use App\Http\Controllers\Web\Front\Account\PostsController;
use App\Http\Controllers\Web\Front\Account\PreferencesController;
use App\Http\Controllers\Web\Front\Account\ProfileController;
use App\Http\Controllers\Web\Front\Account\SavedPostsController;
use App\Http\Controllers\Web\Front\Account\SavedSearchesController;
use App\Http\Controllers\Web\Front\Account\SecurityController;
use App\Http\Controllers\Web\Front\Account\SubscriptionController;
use App\Http\Controllers\Web\Front\Account\TransactionsController;
use Illuminate\Support\Facades\Route;

$accountMiddlewares = ['auth', 'twoFactor', 'banned.user', 'no.http.cache'];

Route::middleware($accountMiddlewares)
	->group(function ($router) {
		$disableImpersonation = ['impersonate.protect'];
		
		// Users
		Route::get('overview', [OverviewController::class, 'index'])->name('account.overview');
		
		Route::controller(ProfileController::class)
			->prefix('profile')
			->group(function ($router) use ($disableImpersonation) {
				Route::get('/', 'index')->name('account.profile');
				Route::middleware($disableImpersonation)
					->group(function ($router) {
						Route::put('/', 'updateDetails')->name('account.profile.update.details');
						Route::put('photo', 'updatePhoto')->name('account.profile.update.photo');
						Route::put('photo/delete', 'deletePhoto')->name('account.profile.delete.photo');
					});
			});
		
		Route::prefix('security')
			->group(function ($router) use ($disableImpersonation) {
				Route::get('/', [SecurityController::class, 'index'])->name('account.security');
				Route::put('password', [SecurityController::class, 'changePassword'])
					->middleware($disableImpersonation)
					->name('account.security.password');
				Route::put('two-factor', [SecurityController::class, 'setupTwoFactor'])
					->middleware($disableImpersonation)
					->name('account.security.twoFactor');
			});
		
		Route::controller(PreferencesController::class)
			->group(function ($router) use ($disableImpersonation) {
				Route::get('preferences', 'index')->name('account.preferences');
				Route::middleware($disableImpersonation)
					->group(function ($router) {
						Route::put('preferences', 'updatePreferences')->name('account.preferences.update');
						Route::post('save-theme-preference', 'saveThemePreference')->name('account.preferences.saveTheme');
					});
			});
		
		Route::controller(LinkedAccountsController::class)
			->prefix('linked-accounts')
			->group(function ($router) use ($disableImpersonation) {
				Route::get('/', 'index')->name('account.linkedAccounts');
				Route::get('{provider}/disconnect', 'disconnect')
					->middleware($disableImpersonation)
					->name('account.linkedAccounts.disconnect');
			});
		
		Route::controller(ClosingController::class)
			->group(function ($router) use ($disableImpersonation) {
				Route::get('closing', 'showForm')->name('account.closing.showForm');
				Route::post('closing', 'postForm')->middleware($disableImpersonation)->name('account.closing.postForm');
			});
		
		// Subscription
		Route::controller(SubscriptionController::class)
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				Route::get('subscription', 'showForm')->name('account.subscription.showForm');
				Route::post('subscription', 'postForm')->name('account.subscription.postForm');
				
				// Payment Gateway Success & Cancel
				Route::get('{id}/payment/success', 'paymentConfirmation')->name('account.subscription.paymentConfirmation');
				Route::post('{id}/payment/success', 'paymentConfirmation')->name('account.subscription.paymentConfirmation.post');
				Route::get('{id}/payment/cancel', 'paymentCancel')->name('account.subscription.paymentCancel');
			});
		
		// Transactions
		Route::namespace('Transactions')
			->prefix('transactions')
			->group(function ($router) {
				Route::get('promotion', [TransactionsController::class, 'index'])->name('account.transactions.promotion');
				Route::get('subscription', [TransactionsController::class, 'index'])->name('account.transactions.subscription');
			});
	});

Route::middleware($accountMiddlewares)
	->group(function ($router) {
		// Posts
		Route::controller(PostsController::class)
			->prefix('posts')
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				// Activated Posts
				Route::get('list', 'onlinePosts')->name('account.listings.online');
				Route::get('list/{id}/offline', 'takePostOffline')->name('account.listings.takeOffline');
				Route::get('list/{id}/delete', 'destroy')->name('account.listings.delete');
				Route::post('list/delete', 'destroy')->name('account.listings.delete.bulk');
				
				// Archived Posts
				Route::get('archived', 'archivedPosts')->name('account.listings.archived');
				Route::get('archived/{id}/repost', 'repostPost')->name('account.listings.archived.repost');
				Route::get('archived/{id}/delete', 'destroy')->name('account.listings.archived.delete');
				Route::post('archived/delete', 'destroy')->name('account.listings.archived.delete.bulk');
				
				// Pending Approval Posts
				Route::get('pending-approval', 'pendingApprovalPosts')->name('account.listings.pendingApproval');
				Route::get('pending-approval/{id}/delete', 'destroy')->name('account.listings.pendingApproval.delete');
				Route::post('pending-approval/delete', 'destroy')->name('account.listings.pendingApproval.delete.bulk');
			});
		
		// Saved Posts
		Route::controller(SavedPostsController::class)
			->prefix('saved-posts')
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				Route::post('toggle', 'toggle')->name('account.savedListings.toggle');
				Route::get('/', 'index')->name('account.savedListings');
				Route::get('{id}/delete', 'destroy')->name('account.savedListings.delete');
				Route::post('delete', 'destroy')->name('account.savedListings.delete.bulk');
			});
		
		// Saved Searches
		Route::controller(SavedSearchesController::class)
			->prefix('saved-searches')
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				Route::post('store', 'store')->name('account.savedSearches.store');
				Route::get('/', 'index')->name('account.savedSearches');
				Route::get('{id}', 'show')->name('account.savedSearches.show');
				Route::get('{id}/delete', 'destroy')->name('account.savedSearches.delete');
				Route::post('delete', 'destroy')->name('account.savedSearches.delete.bulk');
			});
	});

// Messenger
// Contact Post's Author
Route::post('messages/posts/{id}', [MessagesController::class, 'store']);

// Messenger Chat
Route::middleware($accountMiddlewares)
	->group(function ($router) {
		Route::controller(MessagesController::class)
			->prefix('messages')
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				Route::post('check-new', 'checkNew')->name('account.messages.checkNew');
				Route::get('/', 'index')->name('account.messages');
				Route::post('/', 'store')->name('account.messages.store');
				Route::get('{id}', 'show')->name('account.messages.show');
				Route::put('{id}', 'update')->name('account.messages.update');
				Route::get('{id}/actions', 'actions')->name('account.messages.actions');
				Route::post('actions', 'actions')->name('account.messages.actions.bulk');
				Route::get('{id}/delete', 'destroy')->name('account.messages.delete');
				Route::post('delete', 'destroy')->name('account.messages.delete.bulk');
			});
	});
