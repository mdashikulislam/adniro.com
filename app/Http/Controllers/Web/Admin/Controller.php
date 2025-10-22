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

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Front\Traits\CommonTrait;
use App\Http\Controllers\Web\Front\Traits\RobotsTxtTrait;
use App\Models\Setting;
use Illuminate\Http\Request;
use Throwable;

class Controller extends \App\Http\Controllers\Controller
{
	use RobotsTxtTrait, CommonTrait;
	
	public Request $request;
	
	/**
	 * Controller constructor.
	 */
	public function __construct()
	{
		// Set the storage disk
		$this->setStorageDisk();
		
		// Check & Change the App Key (If needed)
		$this->checkAndGenerateAppKey();
		
		// Get Settings (for Sidebar Menu)
		$this->getSettings();
		
		// Generated the robots.txt file (If not exists)
		$this->checkRobotsTxtFile();
	}
	
	/**
	 * Get Settings (for Sidebar Menu)
	 *
	 * @return void
	 */
	private function getSettings(): void
	{
		$settings = collect();
		
		if (config('settings.app.general_settings_as_submenu_in_sidebar')) {
			$selectColumns = ['id', 'name', 'label'];
			
			// Cache Parameters
			$cacheParams = [
				'action'        => 'get.settings',
				'selectColumns' => implode(',', $selectColumns),
				'orderBy'       => 'lft',
			];
			
			try {
				$settings = caching()->remember(Setting::class, $cacheParams, function () use ($selectColumns) {
					return Setting::query()->orderBy('lft')->get($selectColumns);
				});
			} catch (Throwable $e) {
			}
		}
		
		view()->share('settings', $settings);
	}
}
