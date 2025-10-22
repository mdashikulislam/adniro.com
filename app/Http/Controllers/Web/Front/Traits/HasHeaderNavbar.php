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

namespace App\Http\Controllers\Web\Front\Traits;

use App\Http\Controllers\Web\Front\HomeController;

trait HasHeaderNavbar
{
	/**
	 * Configure the navbar (default) options from context
	 *
	 * Use the static-sections' pages' navbar options as the default navbar options
	 * based on the current context and page type
	 *
	 * @return void
	 */
	protected function configureNavbarOptionsFromContext()
	{
		// Skip navbar configuration if request is coming from admin panel
		if (isFromAdminPanel()) {
			return;
		}
		
		// Check if we're on a sectionable page (i.e. page with dynamic sections)
		// For now, only HomeController view has dynamic sections (so is sectionable)
		$onSectionablePage = (routeActionHas(HomeController::class));
		
		// Only apply static navbar configuration for pages with static sections
		if ($onSectionablePage) {
			return;
		}
		
		$header = config('settings.header');
		$header = ensureCastedToArray($header);
		
		// Get original default options
		$defaultOptions = collect($header)
			->filter(fn ($item, $key) => str_starts_with($key, 'default_'))
			->toArray();
		
		// Convert static options to use them as default options
		$staticToDefaultOptions = collect($header)
			->filter(function ($item, $key) {
				return (
					str_starts_with($key, 'static_')
					&& !str_ends_with($key, 'recopy_default')
				);
			})
			->mapWithKeys(function ($item, $key) {
				$key = str($key)->replaceFirst('static_', 'default_')->toString();
				
				return [$key => $item];
			})
			->toArray();
		
		// Get invalid options
		$invalidOptions = array_diff_key($defaultOptions, $staticToDefaultOptions);
		$invalidOptionKeys = !empty($invalidOptions) ? array_keys($invalidOptions) : [];
		
		// Merge the converted options to $header by excluding invalid keys
		$header = array_merge($header, $staticToDefaultOptions);
		$header = collect($header)->reject(fn ($item, $key) => in_array($key, $invalidOptionKeys))->toArray();
		
		// Update the header settings
		config()->set('settings.header', $header);
	}
}
