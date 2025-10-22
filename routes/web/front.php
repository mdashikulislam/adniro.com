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

use App\Http\Controllers\Web\Front\Browsing\Category\CategoryController as BrowsingCategoryController;
use App\Http\Controllers\Web\Front\Browsing\Location\AutoCompleteController;
use App\Http\Controllers\Web\Front\Browsing\Location\ModalController;
use App\Http\Controllers\Web\Front\Browsing\Location\SelectBoxController;
use App\Http\Controllers\Web\Front\CountriesController;
use App\Http\Controllers\Web\Front\FileController;
use App\Http\Controllers\Web\Front\HomeController;
use App\Http\Controllers\Web\Front\Locale\LocaleController;
use App\Http\Controllers\Web\Front\Page\PageController;
use App\Http\Controllers\Web\Front\Page\ContactController;
use App\Http\Controllers\Web\Front\Page\PricingController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\FinishController as CreateFinishController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\PaymentController as CreatePaymentController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\PhotoController as CreatePhotoController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\PostController as CreatePostController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Edit\PaymentController as EditPaymentController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Edit\PhotoController as EditPhotoController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Edit\PostController as EditPostController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\SingleStep\CreateController as SingleCreateController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\SingleStep\EditController as SingleEditController;
use App\Http\Controllers\Web\Front\Post\ReportController;
use App\Http\Controllers\Web\Front\Post\Show\ShowController;
use App\Http\Controllers\Web\Front\Search\CategoryController;
use App\Http\Controllers\Web\Front\Search\CityController;
use App\Http\Controllers\Web\Front\Search\SearchController;
use App\Http\Controllers\Web\Front\Search\TagController;
use App\Http\Controllers\Web\Front\Search\UserController;
use App\Http\Controllers\Web\Front\SitemapController;
use App\Http\Controllers\Web\Front\SitemapsController;
use Illuminate\Support\Facades\Route;

$isDomainmappingAvailable = (plugin_exists('domainmapping') && plugin_installed_file_exists('domainmapping'));

// ACCOUNT
$accountBasePath = urlGen()->getAccountBasePath();
Route::namespace('Account')->prefix($accountBasePath)->group(__DIR__ . '/front/account.php');

// Select Language
Route::namespace('Locale')
	->group(function ($router) {
		Route::get('locale/{code}', [LocaleController::class, 'setLocale'])->name('set.locale');
	});

// FILES
Route::controller(FileController::class)
	->prefix('common')
	->group(function ($router) {
		Route::get('file', 'watchMediaContent');
		Route::get('js/fileinput/locales/{code}.js', 'bootstrapFileinputLocales');
		Route::get('css/style.css', 'cssStyle');
	});

if (!$isDomainmappingAvailable) {
	// SITEMAPS (XML)
	Route::get('sitemaps.xml', [SitemapsController::class, 'getAllCountriesSitemapIndex']);
}

// Impersonate (As admin user, login as another user)
Route::middleware(['auth'])
	->group(function ($router) {
		Route::impersonate();
	});


// HOMEPAGE
if (!doesCountriesPageCanBeHomepage()) {
	Route::get('/', [HomeController::class, 'index'])->name('homepage');
	Route::get(dynamicRoute('routes.countries'), CountriesController::class)->name('country.list');
} else {
	Route::get('/', CountriesController::class)->name('country.list.as.homepage');
}


// POSTS
Route::namespace('Post')
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		
		$hidPrefix = config('larapen.core.hashableIdPrefix');
		if (is_string($hidPrefix) && !empty($hidPrefix)) {
			$router->pattern('hashableId', '([0-9]+)?(' . $hidPrefix . '[a-z0-9A-Z]{11})?');
		} else {
			$router->pattern('hashableId', '([0-9]+)?([a-z0-9A-Z]{11})?');
		}
		
		// $router->pattern('slug', '.*');
		$bannedSlugs = regexSimilarRoutesPrefixes();
		if (!empty($bannedSlugs)) {
			/*
			 * NOTE:
			 * '^(?!companies|users)$' : Don't match 'companies' or 'users'
			 * '^(?=.*)$'              : Match any character
			 * '^((?!\/).)*$'          : Match any character, but don't match string with '/'
			 */
			$router->pattern('slug', '^(?!' . implode('|', $bannedSlugs) . ')(?=.*)((?!\/).)*$');
		} else {
			$router->pattern('slug', '^(?=.*)((?!\/).)*$');
		}
		
		// SingleStep Listing creation
		Route::namespace('CreateOrEdit\SingleStep')
			->controller(SingleCreateController::class)
			->group(function ($router) {
				Route::get('create', 'showForm')->name('listing.create.ss.showForm');
				Route::post('create', 'postForm')->name('listing.create.ss.postForm');
				Route::get('create/finish', 'finish')->name('listing.create.ss.finish');
				
				// Payment Gateway Success & Cancel
				Route::get('create/payment/success', 'paymentConfirmation')->name('listing.create.ss.paymentConfirmation');
				Route::get('create/payment/cancel', 'paymentCancel')->name('listing.create.ss.paymentCancel');
				Route::post('create/payment/success', 'paymentConfirmation')->name('listing.create.ss.paymentConfirmation.post');
			});
		
		// MultiSteps Listing creation
		Route::namespace('CreateOrEdit\MultiSteps')
			->group(function ($router) {
				Route::controller(CreatePostController::class)
					->group(function ($router) {
						Route::get('posts/create', 'showForm')->name('listing.create.ms.showForm');
						Route::post('posts/create', 'postForm')->name('listing.create.ms.postForm');
					});
				
				Route::controller(CreatePhotoController::class)
					->group(function ($router) {
						Route::get('posts/create/photos', 'showForm')->name('listing.create.ms.photos.showForm');
						Route::post('posts/create/photos', 'postForm')->name('listing.create.ms.photos.postForm');
						Route::post('posts/create/photos/{photoId}/delete', 'removePicture')->name('listing.create.ms.photos.delete');
						Route::post('posts/create/photos/reorder', 'reorderPictures')->name('listing.create.ms.photos.reorder');
					});
				
				Route::controller(CreatePaymentController::class)
					->group(function ($router) {
						Route::get('posts/create/payment', 'showForm')->name('listing.create.ms.payment.showForm');
						Route::post('posts/create/payment', 'postForm')->name('listing.create.ms.payment.postForm');
						
						// Payment Gateway Success & Cancel
						Route::get('posts/create/payment/success', 'paymentConfirmation')->name('listing.create.ms.paymentConfirmation');
						Route::post('posts/create/payment/success', 'paymentConfirmation')->name('listing.create.ms.paymentConfirmation.post');
						Route::get('posts/create/payment/cancel', 'paymentCancel')->name('listing.create.ms.paymentCancel');
					});
				
				Route::post('posts/create/finish', CreateFinishController::class)->name('listing.create.ms.finish.post');
				Route::get('posts/create/finish', CreateFinishController::class)->name('listing.create.ms.finish');
			});
		
		Route::middleware(['auth'])
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				// SingleStep Listing edition
				Route::namespace('CreateOrEdit\SingleStep')
					->controller(SingleEditController::class)
					->group(function ($router) {
						Route::get('edit/{id}', 'showForm')->name('listing.edit.ss.showForm');
						Route::put('edit/{id}', 'postForm')->name('listing.edit.ss.postForm');
						
						// Payment Gateway Success & Cancel
						Route::get('edit/{id}/payment/success', 'paymentConfirmation')->name('listing.edit.ss.paymentConfirmation');
						Route::get('edit/{id}/payment/cancel', 'paymentCancel')->name('listing.edit.ss.paymentCancel');
						Route::post('edit/{id}/payment/success', 'paymentConfirmation')->name('listing.edit.ss.paymentConfirmation.post');
					});
				
				// MultiSteps Listing Edition
				Route::namespace('CreateOrEdit\MultiSteps')
					->group(function ($router) {
						Route::controller(EditPostController::class)
							->group(function ($router) {
								Route::get('posts/{id}/details', 'showForm')->name('listing.edit.ms.showForm');
								Route::put('posts/{id}/details', 'postForm')->name('listing.edit.ms.postForm');
							});
						
						Route::controller(EditPhotoController::class)
							->group(function ($router) {
								Route::get('posts/{id}/photos', 'showForm')->name('listing.edit.ms.photos.showForm');
								Route::post('posts/{id}/photos', 'postForm')->name('listing.edit.ms.photos.postForm');
								Route::post('posts/{id}/photos/{photoId}/delete', 'delete')->name('listing.edit.ms.photos.delete');
								Route::post('posts/{id}/photos/reorder', 'reorder')->name('listing.edit.ms.photos.reorder');
							});
						
						Route::controller(EditPaymentController::class)
							->group(function ($router) {
								Route::get('posts/{id}/payment', 'showForm')->name('listing.edit.ms.payment.showForm');
								Route::post('posts/{id}/payment', 'postForm')->name('listing.edit.ms.payment.postForm');
								
								// Payment Gateway Success & Cancel
								Route::get('posts/{id}/payment/success', 'paymentConfirmation')->name('listing.edit.ms.paymentConfirmation');
								Route::post('posts/{id}/payment/success', 'paymentConfirmation')->name('listing.edit.ms.paymentConfirmation.post');
								Route::get('posts/{id}/payment/cancel', 'paymentCancel')->name('listing.edit.ms.paymentCancel');
							});
					});
			});
		
		// Post's Details
		Route::controller(ShowController::class)
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				Route::get(dynamicRoute('routes.post'), 'index');
				Route::post('posts/{id}/phone', 'getPhone');
			});
		
		// Send report abuse
		Route::controller(ReportController::class)
			->group(function ($router) {
				Route::get('posts/{hashableId}/report', 'showForm')->name('listing.report.showForm');
				Route::post('posts/{hashableId}/report', 'postForm')->name('listing.report.postForm');
			});
	});


// BROWSING
Route::namespace('Browsing')
	->prefix('browsing')
	->group(function ($router) {
		// Categories
		Route::controller(BrowsingCategoryController::class)
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				Route::post('categories/select', 'getCategoriesHtml'); // To remove!
				Route::get('categories/select', 'getCategoriesHtml');
				Route::post('categories/{id}/fields', 'getCustomFieldsHtml');
			});
		
		// Location
		Route::namespace('Location')
			->group(function ($router) {
				$router->pattern('countryCode', getCountryCodeRoutePattern());
				Route::post('countries/{countryCode}/cities/autocomplete', AutoCompleteController::class);
				Route::controller(SelectBoxController::class)
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						Route::get('countries/{countryCode}/admins/{adminType}', 'getAdmins');
						Route::get('countries/{countryCode}/admins/{adminType}/{adminCode}/cities', 'getCities');
						Route::get('countries/{countryCode}/cities/{id}', 'getSelectedCity');
					});
				Route::controller(ModalController::class)
					->group(function ($router) {
						Route::post('locations/{countryCode}/admins/{adminType}', 'getAdmins');
						Route::post('locations/{countryCode}/admins/{adminType}/{adminCode}/cities', 'getCities');
						Route::post('locations/{countryCode}/cities', 'getCities');
					});
			});
	});


// FEEDS
Route::feeds();


if (!$isDomainmappingAvailable) {
	// SITEMAPS (XML)
	Route::controller(SitemapsController::class)
		->group(function ($router) {
			$router->pattern('countryCode', getCountryCodeRoutePattern());
			Route::get('{countryCode}/sitemaps.xml', 'getSitemapIndexByCountry')->name('xml.sitemaps.all');
			Route::get('{countryCode}/sitemaps/pages.xml', 'getPagesSitemapByCountry')->name('xml.sitemaps.pages');
			Route::get('{countryCode}/sitemaps/categories.xml', 'getCategoriesSitemapByCountry')->name('xml.sitemaps.categories');
			Route::get('{countryCode}/sitemaps/cities.xml', 'getCitiesSitemapByCountry')->name('xml.sitemaps.cities');
			Route::get('{countryCode}/sitemaps/posts.xml', 'getListingsSitemapByCountry')->name('xml.sitemaps.listings');
		});
}


// PAGES
Route::namespace('Page')
	->group(function ($router) {
		Route::get(dynamicRoute('routes.pricing'), [PricingController::class, 'index'])->name('pricing');
		Route::get(dynamicRoute('routes.pageBySlug'), [PageController::class, 'show'])->name('page.show')->where('slug', '[^/]*');
		Route::controller(ContactController::class)
			->group(function ($router) {
				Route::get(dynamicRoute('routes.contact'), 'showForm')->name('contact.showForm');
				Route::post(dynamicRoute('routes.contact'), 'postForm')->name('contact.postForm');
			});
	});

// SITEMAP (HTML)
Route::get(dynamicRoute('routes.sitemap'), SitemapController::class)->name('sitemap');

// SEARCH
Route::namespace('Search')
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		$router->pattern('username', '[a-zA-Z0-9]+');
		Route::get(dynamicRoute('routes.search'), [SearchController::class, 'index'])->name('browse.listings');
		Route::get(dynamicRoute('routes.searchPostsByUserId'), [UserController::class, 'index'])->name('browse.listings.byUserId');
		Route::get(dynamicRoute('routes.searchPostsByUsername'), [UserController::class, 'profile'])->name('browse.listings.byUsername');
		Route::get(dynamicRoute('routes.searchPostsByTag'), [TagController::class, 'index'])->name('browse.listings.byTag');
		Route::get(dynamicRoute('routes.searchPostsByCity'), [CityController::class, 'index'])->name('browse.listings.byCity');
		Route::get(dynamicRoute('routes.searchPostsBySubCat'), [CategoryController::class, 'index'])->name('browse.listings.bySubCategory');
		Route::get(dynamicRoute('routes.searchPostsByCat'), [CategoryController::class, 'index'])->name('browse.listings.byCategory');
	});
