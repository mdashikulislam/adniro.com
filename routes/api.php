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

use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\SocialController;
use App\Http\Controllers\Api\Auth\VerificationController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\GenderController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PictureController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostTypeController;
use App\Http\Controllers\Api\ReportTypeController;
use App\Http\Controllers\Api\SavedPostController;
use App\Http\Controllers\Api\SavedSearchController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SubAdmin1Controller;
use App\Http\Controllers\Api\SubAdmin2Controller;
use App\Http\Controllers\Api\ThreadController;
use App\Http\Controllers\Api\ThreadMessageController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// auth
Route::namespace('Auth')
	->prefix('auth')
	->group(function ($router) {
		
		$router->pattern('userId', '[0-9]+');
		
		Route::controller(LoginController::class)
			->group(function ($router) {
				Route::post('login', 'login')->name('api.auth.login');
				Route::get('logout/{userId}', 'logout')->name('api.auth.logout');
			});
		
		Route::controller(ForgotPasswordController::class)
			->group(function ($router) {
				Route::post('password/forgot', 'sendResetLinkOrCode')->name('api.auth.password.forgot');
			});
		
		Route::controller(ResetPasswordController::class)
			->group(function ($router) {
				Route::post('password/reset', 'reset')->name('api.auth.password.reset');
			});
		
		Route::controller(SocialController::class)
			->group(function ($router) {
				$router->pattern('provider', 'facebook|linkedin|twitter-oauth-2|google');
				Route::get('connect/{provider}', 'getProviderTargetUrl')->name('api.auth.social.connect');
				Route::get('connect/{provider}/callback', 'handleProviderCallback')->name('api.auth.social.connect.callback');
			});
		
		// verification
		Route::controller(VerificationController::class)
			->prefix('verify')
			->group(function ($router) {
				// User, Post, Password - Email Address or Phone Number verification
				// Note: The re-send feature in not implemented for password forgot
				// ---
				// Important: Make sure that the 'entityMetadataKey' possible values match with
				// $entitiesMetadata key in the 'app/Services/Auth/Traits/VerificationTrait.php' file
				$router->pattern('entityMetadataKey', 'users|posts|password');
				$router->pattern('field', 'email|phone');
				$router->pattern('token', '.*');
				$router->pattern('entityId', '[0-9]+');
				
				Route::get('{entityMetadataKey}/{entityId}/resend/email', 'resendEmailVerification')->name('api.auth.verify.resend.link');
				Route::get('{entityMetadataKey}/{entityId}/resend/sms', 'resendPhoneVerification')->name('api.auth.verify.resend.code');
				Route::get('{entityMetadataKey}/{field}/{token?}', 'verification')->name('api.auth.verify.linkOrOtp');
			});
		
	});


// genders
Route::prefix('genders')
	->controller(GenderController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		Route::get('/', 'index')->name('api.genders.index');
		Route::get('{id}', 'show')->name('api.genders.show');
	});

// postTypes
Route::prefix('postTypes')
	->controller(PostTypeController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		Route::get('/', 'index')->name('api.postTypes.index');
		Route::get('{id}', 'show')->name('api.postTypes.show');
	});

// reportTypes
Route::prefix('reportTypes')
	->controller(ReportTypeController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		Route::get('/', 'index')->name('api.reportTypes.index');
		Route::get('{id}', 'show')->name('api.reportTypes.show');
	});

// userTypes
Route::prefix('userTypes')
	->controller(UserTypeController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		Route::get('/', 'index')->name('api.userTypes.index');
		Route::get('{id}', 'show')->name('api.userTypes.show');
	});

// categories
Route::prefix('categories')
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		
		Route::controller(CategoryController::class)
			->group(function ($router) {
				$router->pattern('slugOrId', '[^/]+');
				Route::get('/', 'index')->name('api.categories.index');
				Route::get('{slugOrId}', 'show')->name('api.categories.show');
				
				// Get custom fields (to complete form fields)
				Route::get('{id}/fields', 'getCustomFields')->name('api.categories.fields'); // Not used due to big JSON data sending
				Route::post('{id}/fields', 'getCustomFields')->name('api.categories.fields.post');
			});
		
		Route::controller(PostController::class)
			->group(function ($router) {
				// Get custom fields values related to a listing (to display fields data in the listing details)
				$router->pattern('postId', '[0-9]+');
				Route::get('{id}/fields/post/{postId}', 'getFieldsValues')->name('api.categories.fields.values');
			});
	});

// countries
Route::prefix('countries')
	->group(function ($router) {
		Route::controller(CountryController::class)
			->group(function ($router) {
				$router->pattern('code', '[a-zA-Z]{2}');
				Route::get('/', 'index')->name('api.countries.index');
				Route::get('{code}', 'show')->name('api.countries.show');
			});
		
		$router->pattern('countryCode', '[a-zA-Z]{2}');
		Route::get('{countryCode}/subAdmins1', [SubAdmin1Controller::class, 'index'])->name('api.subAdmins1.index');
		Route::get('{countryCode}/subAdmins2', [SubAdmin2Controller::class, 'index'])->name('api.subAdmins2.index');
		Route::get('{countryCode}/cities', [CityController::class, 'index'])->name('api.cities.index');
	});

// subAdmins1
Route::prefix('subAdmins1')
	->controller(SubAdmin1Controller::class)
	->group(function ($router) {
		$router->pattern('code', '[^/]+');
		Route::get('{code}', 'show')->name('api.subAdmins1.show');
	});

// subAdmins2
Route::prefix('subAdmins2')
	->controller(SubAdmin2Controller::class)
	->group(function ($router) {
		$router->pattern('code', '[^/]+');
		Route::get('{code}', 'show')->name('api.subAdmins2.show');
	});

// cities
Route::prefix('cities')
	->controller(CityController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		Route::get('{id}', 'show')->name('api.cities.show');
	});

// users
Route::prefix('users')
	->controller(UserController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		
		Route::get('/', 'index')->name('api.users.index');
		Route::get('{id}', 'show')->name('api.users.show');
		Route::post('/', 'store')->name('api.users.store');
		Route::middleware(['auth:sanctum'])
			->group(function ($router) {
				Route::get('{id}/stats', 'stats')->name('api.users.stats');
				
				// Removal (fake deletion) of the user's photo
				// Note: The user's photo is stored as a file path in a column instead of entry row.
				// So the HTTP's GET method can be used to empty the photo column and its file.
				Route::get('{id}/photo/delete', 'removePhoto')->name('api.users.photo.delete');
				Route::put('{id}/photo', 'updatePhoto')->name('api.users.photo.update');
				Route::put('{id}/security', 'updateSecuritySettings')->name('api.users.security.settings');
				Route::put('{id}/preferences', 'updatePreferences')->name('api.users.preferences');
				Route::put('{id}/save-theme-preference', 'saveThemePreference')->name('api.users.themePreference.update');
				
				// Update User (with its photo)
				Route::put('{id}', 'update')->name('api.users.update');
			});
		Route::delete('{id}', 'destroy')->name('api.users.destroy');
	});

// posts
Route::prefix('posts')
	->controller(PostController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		
		Route::get('/', 'index')->name('api.posts.index');
		Route::get('{id}', 'show')->name('api.posts.show');
		Route::post('/', 'store')->name('api.posts.store');
		Route::middleware(['auth:sanctum'])
			->group(function ($router) {
				$router->pattern('ids', '[0-9,]+');
				Route::put('{id}/offline', 'offline')->name('api.posts.offline');
				Route::put('{id}/repost', 'repost')->name('api.posts.repost');
				Route::put('{id}', 'update')->name('api.posts.update');
				Route::delete('{ids}', 'destroy')->name('api.posts.destroy');
			});
	});

// menus
/*
Route::middleware(['auth:sanctum', 'permission:manage-menus'])->group(function () {
	Route::apiResource('menus', MenuController::class);
	Route::post('menus/{menu}/items', [MenuController::class, 'createItem']);
	Route::put('menus/{menu}/items/{item}', [MenuController::class, 'updateItem']);
	Route::delete('menus/{menu}/items/{item}', [MenuController::class, 'deleteItem']);
	Route::post('menus/{menu}/reorder', [MenuController::class, 'reorderItems']);
	Route::post('menus/clear-cache', [MenuController::class, 'clearCache']);
});
*/

// savedPosts
Route::prefix('savedPosts')
	->controller(SavedPostController::class)
	->group(function ($router) {
		Route::post('/', 'store')->name('api.savedPosts.store');
		Route::middleware(['auth:sanctum'])
			->group(function ($router) {
				$router->pattern('ids', '[0-9,]+');
				Route::get('/', 'index')->name('api.savedPosts.index');
				Route::delete('{ids}', 'destroy')->name('api.savedPosts.destroy');
			});
	});

// savedSearches
Route::prefix('savedSearches')
	->controller(SavedSearchController::class)
	->group(function ($router) {
		Route::post('/', 'store')->name('api.savedSearches.store');
		Route::middleware(['auth:sanctum'])
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				$router->pattern('ids', '[0-9,]+');
				Route::get('/', 'index')->name('api.savedSearches.index');
				Route::get('{id}', 'show')->name('api.savedSearches.show');
				Route::delete('{ids}', 'destroy')->name('api.savedSearches.destroy');
			});
	});

// pictures
Route::prefix('pictures')
	->controller(PictureController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		
		Route::get('{id}', 'show')->name('api.pictures.show');
		Route::post('/', 'store')->name('api.pictures.store');
		Route::delete('{id}', 'destroy')->name('api.pictures.destroy');
		Route::post('reorder', 'reorder')->name('api.pictures.reorder'); // Bulk Update
	});
Route::prefix('posts')
	->controller(PictureController::class)
	->group(function ($router) {
		$router->pattern('postId', '[0-9]+');
		Route::get('{postId}/pictures', 'index')->name('api.posts.pictures');
	});

// packages (promotion|subscription)
Route::prefix('packages')
	->controller(PackageController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		Route::get('promotion', 'index')->name('api.packages.promotion.index');
		Route::get('subscription', 'index')->name('api.packages.subscription.index');
		Route::get('{id}', 'show')->name('api.packages.show');
	});

// paymentMethods
Route::prefix('paymentMethods')
	->controller(PaymentMethodController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9a-z]+');
		Route::get('/', 'index')->name('api.paymentMethods.index');
		Route::get('{id}', 'show')->name('api.paymentMethod.show');
	});

// payments (promotion|subscription)
Route::prefix('payments')
	->controller(PaymentController::class)
	->group(function ($router) {
		Route::middleware(['auth:sanctum'])
			->group(function ($router) {
				// promotion
				Route::prefix('promotion')
					->group(function ($router) {
						Route::get('/', 'index')->name('api.payments.promotion.index');
						
						Route::prefix('posts')
							->group(function ($router) {
								$router->pattern('postId', '[0-9]+');
								Route::get('{postId}/payments', 'index')->name('api.posts.payments');
							});
					});
				
				// subscription
				Route::prefix('subscription')
					->group(function ($router) {
						Route::get('/', 'index')->name('api.payments.subscription.index');
						
						Route::prefix('users')
							->group(function ($router) {
								$router->pattern('userId', '[0-9]+');
								Route::get('{userId}/payments', 'index')->name('api.users.payments');
							});
					});
				
				// show
				$router->pattern('id', '[0-9]+');
				Route::get('{id}', 'show')->name('api.payments.show');
			});
		
		Route::post('/', 'store')->name('api.payments.store');
	});

// threads
Route::prefix('threads')
	->group(function ($router) {
		Route::post('/', [ThreadController::class, 'store'])->name('api.threads.store');
		
		Route::middleware(['auth:sanctum'])
			->group(function ($router) {
				Route::controller(ThreadController::class)
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						$router->pattern('ids', '[0-9,]+');
						
						Route::get('/', 'index')->name('api.threads.index');
						Route::get('{id}', 'show')->name('api.threads.show');
						Route::put('{id}', 'update')->name('api.threads.update');
						Route::delete('{ids}', 'destroy')->name('api.threads.destroy');
						
						Route::post('bulkUpdate/{ids?}', 'bulkUpdate')->name('api.threads.bulkUpdate'); // Bulk Update
					});
				
				// threadMessages
				Route::controller(ThreadMessageController::class)
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						$router->pattern('threadId', '[0-9]+');
						Route::get('{threadId}/messages', 'index')->name('api.threadMessages.index');
						Route::get('{threadId}/messages/{id}', 'show')->name('api.threadMessages.show');
					});
			});
	});

// pages
Route::prefix('pages')
	->controller(PageController::class)
	->group(function ($router) {
		$router->pattern('slugOrId', '[^/]+');
		Route::get('/', 'index')->name('api.pages.index');
		Route::get('{slugOrId}', 'show')->name('api.pages.show');
	});

// contact
Route::prefix('contact')
	->controller(ContactController::class)
	->group(function ($router) {
		Route::post('/', 'sendForm')->name('api.contact');
	});
Route::prefix('posts')
	->controller(ContactController::class)
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		Route::post('{id}/report', 'sendReport')->name('api.posts.report');
	});

// languages
Route::prefix('languages')
	->controller(LanguageController::class)
	->group(function ($router) {
		$router->pattern('code', '[^/]+');
		Route::get('/', 'index')->name('api.languages.index');
		Route::get('{code}', 'show')->name('api.languages.show');
	});

// settings
Route::prefix('settings')
	->controller(SettingController::class)
	->group(function ($router) {
		$router->pattern('key', '[^/]+');
		Route::get('/', 'index')->name('api.settings.index');
		Route::get('{key}', 'show')->name('api.settings.show');
	});

// sections
Route::prefix('sections')
	->controller(SectionController::class)
	->group(function ($router) {
		$router->pattern('method', '[^/]+');
		Route::get('/', 'index')->name('api.sections.index');
		Route::get('{method}', 'show')->name('api.sections.show');
	});

// captcha
Route::prefix('captcha')
	->controller(CaptchaController::class)
	->group(function ($router) {
		Route::get('/', 'getCaptcha')->name('api.captcha.getCaptcha');
	});

// fallback
// catch all routes where the path does not start with 'plugins'
// regex: ^(?!plugins).*$
Route::any('{any}', function () {
	return response()->json([
		'success' => false,
		'message' => 'API endpoint not found.',
	], 404);
})->where('any', '^(?!plugins).*$')->name('api.any.other');
