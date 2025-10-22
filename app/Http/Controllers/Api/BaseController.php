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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Base\SettingsTrait;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Front\Traits\CommonTrait;
use App\Http\Controllers\Web\Front\Traits\EnvFileTrait;

class BaseController extends Controller
{
	use CommonTrait, SettingsTrait, EnvFileTrait;
	
	public ?string $locale = null;
	public ?string $countryCode = null;
	
	public int $cacheExpiration = 3600; // In minutes (e.g. 60 * 60 for 1h)
	public int $perPage = 10;
	
	public function __construct()
	{
		// CommonTrait: Set the storage disk
		$this->setStorageDisk();
		
		// SettingsTrait
		$this->applyFrontSettings();
		
		// CommonTrait: Check & Change the App Key (If needed)
		$this->checkAndGenerateAppKey();
		
		// EnvFileTrait: Check & Update the /.env file
		$this->checkDotEnvEntries();
		
		// Items per page
		$this->perPage = getNumberOfItemsPerPage(null, request()->integer('perPage'));
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		$array = [];
		
		// Add the 'Currency Exchange' plugin middleware
		if (config('plugins.currencyexchange.installed')) {
			$array[] = 'currencies';
			$array[] = 'currencyExchange';
		}
		
		return array_merge(parent::middleware(), $array);
	}
}
