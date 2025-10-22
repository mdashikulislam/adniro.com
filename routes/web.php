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

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::namespace('Setup')->group(__DIR__ . '/web/setup.php');

Route::middleware(['installed'])
	->group(function () {
		$authBasePath = urlGen()->getAuthBasePath();
		$adminBasePath = urlGen()->getAdminBasePath();
		
		Route::namespace('Auth')->prefix($authBasePath)->group(__DIR__ . '/web/auth.php');
		Route::namespace('Admin')->prefix($adminBasePath)->group(__DIR__ . '/web/admin.php');
		Route::namespace('Front')->group(__DIR__ . '/web/front.php');
	});
