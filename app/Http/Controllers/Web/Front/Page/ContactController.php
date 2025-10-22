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

namespace App\Http\Controllers\Web\Front\Page;

use App\Http\Controllers\Web\Front\FrontController;
use App\Http\Requests\Front\ContactRequest;
use App\Services\CityService;
use App\Services\ContactService;
use Illuminate\Http\RedirectResponse;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ContactController extends FrontController
{
	protected CityService $cityService;
	protected ContactService $contactService;
	
	public function __construct(CityService $cityService, ContactService $contactService)
	{
		parent::__construct();
		
		$this->cityService = $cityService;
		$this->contactService = $contactService;
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showForm()
	{
		$city = null;
		if (config('services.google_maps_platform.maps_javascript_api_key')) {
			// Get the Country's largest city for Google Maps
			$countryCode = config('country.code');
			$queryParams = ['firstOrderByPopulation' => 'desc'];
			$data = getServiceData($this->cityService->getEntries($countryCode, $queryParams));
			
			$message = data_get($data, 'message');
			$city = data_get($data, 'result');
		}
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('contact');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return view('front.pages.contact', compact('city'));
	}
	
	/**
	 * @param \App\Http\Requests\Front\ContactRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm(ContactRequest $request): RedirectResponse
	{
		// Add required data in the request for API
		$request->merge([
			'country_code' => config('country.code'),
			'country_name' => config('country.name'),
		]);
		
		// Submit the form
		$data = getServiceData($this->contactService->submitContactForm($request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back()->withErrors(['error' => $message])->withInput();
		}
		
		return redirect()->to(urlGen()->contact());
	}
}
