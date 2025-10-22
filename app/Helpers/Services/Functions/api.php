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

use App\Http\Controllers\Api\PostController as ApiPostController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\MultiSteps\Create\PostController as WebMsCreatePostController;
use App\Http\Controllers\Web\Front\Post\CreateOrEdit\SingleStep\CreateController as WebSsCreatePostController;
use Illuminate\Http\Request;

/**
 * Generate a Token for API calls
 *
 * @return string
 */
function generateApiToken(): string
{
	return base64_encode(createRandomString(32));
}

/**
 * Check if the current request is from the API
 *
 * @param \Illuminate\Http\Request|null $request
 * @return bool
 */
function isFromApi(?Request $request = null): bool
{
	if (!$request instanceof Request) {
		$request = request();
	}
	
	return (
		str_starts_with($request->path(), 'api/')
		|| $request->is('api/*')
		|| $request->segment(1) == 'api'
		|| ($request->hasHeader('X-API-CALLED') && $request->header('X-API-CALLED'))
	);
}

/**
 * Does the (current) request is from a Web Application?
 * Check if the current request is made from the official(s) web version(s) of the app
 *
 * Info: This function allows applying web features during API code execution
 * Note: This assumes the "X-AppType=web" header is sent from the web application
 *
 * @param \Illuminate\Http\Request|null $request
 * @return bool
 */
function doesRequestIsFromWebClient(?Request $request = null): bool
{
	if (!$request instanceof Request) {
		$request = request();
	}
	
	if (!isFromApi($request)) return true;
	
	return (isFromApi($request) && $request->header('X-AppType') == 'web');
}

/**
 * @param $paginatedCollection
 * @return mixed
 */
function setPaginationBaseUrl($paginatedCollection)
{
	// If the request is made from the app's Web environment,
	// use the Web URL as the pagination's base URL
	if (doesRequestIsFromWebClient()) {
		if (request()->hasHeader('X-WEB-REQUEST-URL')) {
			if (method_exists($paginatedCollection, 'setPath')) {
				$paginatedCollection->setPath(request()->header('X-WEB-REQUEST-URL'));
			}
		}
	}
	
	return $paginatedCollection;
}

/**
 * @return bool
 */
function isPostCreationRequest(): bool
{
	if (isFromApi()) {
		$isPostCreationRequest = (routeActionHas(ApiPostController::class . '@store'));
	} else {
		$isNewEntryUri = (
			(isMultipleStepsFormEnabled() && request()->segment(2) == 'create')
			|| (isSingleStepFormEnabled() && request()->segment(1) == 'create')
		);
		
		$isPostCreationRequest = (
			$isNewEntryUri
			|| routeActionHas(getClassNamespaceName(WebMsCreatePostController::class))
			|| routeActionHas(WebSsCreatePostController::class)
		);
	}
	
	return $isPostCreationRequest;
}
