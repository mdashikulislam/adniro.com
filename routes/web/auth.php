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

use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\LogoutController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Auth\SocialController;
use App\Http\Controllers\Web\Auth\ToolsController;
use App\Http\Controllers\Web\Auth\TwoFactorController;
use App\Http\Controllers\Web\Auth\VerificationController;
use Illuminate\Support\Facades\Route;

// AUTH
Route::middleware(['guest', 'no.http.cache'])
	->group(function ($router) {
		// Registration Routes...
		Route::controller(RegisterController::class)
			->group(function ($router) {
				Route::get('register', 'showForm')->name('auth.register.showForm');
				Route::post('register', 'postForm')->name('auth.register.postForm');
				Route::get('register/finished', 'finished')->name('auth.register.finished');
			});
		
		// Authentication Routes...
		Route::controller(LoginController::class)
			->middleware(['guest'])
			->group(function ($router) {
				Route::get('login', 'showForm')->name('auth.login.showForm');
				Route::post('login', 'postForm')->name('auth.login.postForm');
			});
		
		// Password Forgot Routes...
		Route::controller(ForgotPasswordController::class)
			->group(function ($router) {
				Route::get('password/forgot', 'showForm')->name('auth.forgot.password.showForm');
				Route::post('password/forgot', 'postForm')->name('auth.forgot.password.postForm');
			});
		
		Route::controller(ResetPasswordController::class)
			->group(function ($router) {
				/*
				 * Reset Password using Link (a part of the core routes)
				 * Show token form when the {token?} variable is empty
				 * - Natively the {token} variable was required.
				 * - $token is saved as hidden field in the reset password form to be used for security
				 */
				Route::get('password/reset/{token?}', 'showForm')->name('auth.reset.password.showForm');
				Route::post('password/reset', 'postForm')->name('auth.reset.password.postForm');
			});
		
		// Social Authentication
		// Old routes:
		// auth/{provider}
		// auth/{provider}/callback
		Route::controller(SocialController::class)
			->group(function ($router) {
				$router->pattern('provider', 'facebook|linkedin|twitter-oauth-2|twitter|google');
				Route::get('connect/{provider}', 'redirectToProvider')->name('auth.social.connect');
				Route::get('connect/{provider}/callback', 'handleProviderCallback')->name('auth.social.connect.callback');
			});
	});

// Two-Factor Authentication (2FA)
Route::controller(TwoFactorController::class)
	->group(function ($router) {
		Route::get('two-factor/verify', 'showForm')->name('auth.2fa.verify.showForm');
		Route::post('two-factor/verify', 'postForm')->name('auth.2fa.verify.postForm');
		Route::get('two-factor/resend', 'resendCode')->name('auth.2fa.resendCode');
	});

// Logout
Route::get('logout', [LogoutController::class, 'logout'])->middleware(['auth'])->name('auth.logout');

// VERIFICATION
Route::controller(VerificationController::class)
	->prefix('verify')
	->group(function ($router) {
		// Email Address or Phone Number verification
		// ---
		// Important: Make sure that the 'entityMetadataKey' possible values match with
		// $entitiesMetadata key in the 'app/Services/Auth/Traits/VerificationTrait.php' file
		// Note: No support for email or SMS resending for the password forgot feature,
		//       since user can effortlessly re-do the action.
		$router->pattern('entityMetadataKey', 'users|posts|password');
		$router->pattern('entityMetadataKeyForReSend', 'users|posts');
		$router->pattern('field', 'email|phone');
		$router->pattern('token', '.*');
		$router->pattern('entityId', '[0-9]+');
		
		Route::get('{entityMetadataKey}/{entityId}/resend/email', 'resendEmailVerification')->name('auth.verify.resend.link');
		Route::get('{entityMetadataKey}/{entityId}/resend/sms', 'resendPhoneVerification')->name('auth.verify.resend.code');
		Route::get('{entityMetadataKey}/{field}/{token?}', 'verifyOrShowOtpVerificationForm')->name('auth.verify.verifyEntityOrShowOtpForm');
		Route::post('{entityMetadataKey}/{field}/{token?}', 'postOtpVerificationForm')->name('auth.verify.submitOtpForm');
	});

// TOOLS/FILES
Route::controller(ToolsController::class)
	->prefix('common')
	->group(function ($router) {
		Route::get('css/skin.css', 'skinCss');
	});
