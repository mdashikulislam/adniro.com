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

namespace App\Http\Controllers\Web\Setup\Update;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../../../../Helpers/Common/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

use App\Exceptions\Custom\AppVersionNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Setup\Update\Traits\ApiTrait;
use App\Http\Controllers\Web\Setup\Update\Traits\CleanUpTrait;
use App\Http\Controllers\Web\Setup\Update\Traits\DbTrait;
use App\Http\Controllers\Web\Setup\Update\Traits\EnvTrait;
use App\Http\Controllers\Web\Setup\Update\Traits\LanguageTrait;
use App\Http\Controllers\Web\Setup\Update\Traits\RoutesTrait;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class UpdateController extends Controller
{
	use CleanUpTrait, DbTrait, EnvTrait, RoutesTrait, LanguageTrait, ApiTrait;
	
	protected static bool $useNonSecureUpgrade = false;
	
	/**
	 * UpdateController constructor.
	 *
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function __construct()
	{
		$this->setDatabaseConfigIfMissing();
		
		self::$useNonSecureUpgrade = self::useNonSecureUpgrade(self::$useNonSecureUpgrade);
	}
	
	/**
	 * Get the middleware that should be assigned to the controller.
	 */
	public static function middleware(): array
	{
		/*
		 * Check if non-secure upgrade is allowed
		 * ---
		 * Note: The middleware() method is called before the constructor, so
		 * we need to useNonSecureUpgrade() method here and in the constructor
		 */
		$isNonSecuredUpgradeAllowed = self::useNonSecureUpgrade(self::$useNonSecureUpgrade);
		
		$array = [];
		if (!$isNonSecuredUpgradeAllowed) {
			$array[] = 'admin';
		}
		
		return array_merge(parent::middleware(), $array);
	}
	
	/**
	 * @param bool $useNonSecureUpgrade
	 * @return bool
	 */
	private static function useNonSecureUpgrade(bool $useNonSecureUpgrade = false): bool
	{
		// Allow non-secure upgrade, when the database is not up-to-date
		if (!self::areDatabaseUpToDate()) {
			$useNonSecureUpgrade = true;
		}
		
		// Allow non-secure upgrade, when admin user(s) cannot be found
		if (!self::isAdminUserCanBeFound()) {
			$useNonSecureUpgrade = true;
		}
		
		// Force non-secure upgrade with FORCE_NON_SECURE_UPGRADE=true
		// in the /.env file through the 'larapen.core.nonSecureUpgrade' config (return boolean or null)
		$forceNonSecureUpgrade = config('larapen.core.forceNonSecureUpgrade');
		
		return ($forceNonSecureUpgrade === true) ? true : $useNonSecureUpgrade;
	}
	
	/**
	 * Start Upgrade
	 * URL: /upgrade
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index(): View
	{
		return view('setup.update');
	}
	
	/**
	 * Run the Upgrade
	 * URL: /upgrade/run
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \App\Exceptions\Custom\AppVersionNotFoundException
	 */
	public function run(): RedirectResponse
	{
		// Lunch the installation if the /.env file doesn't exist
		if (!appEnvFileExists()) {
			return redirect()->to('/install');
		}
		
		// Get eventual new version value & the current (installed) version value
		$lastVersion = getLatestVersion();
		$currentVersion = getCurrentVersion();
		
		// All is up-to-date
		if (version_compare($lastVersion, $currentVersion, '<=')) {
			// If non-secured upgrade is allowed due to Admin User permission issue,
			// then, fix the Admin User Permissions
			if (self::$useNonSecureUpgrade) {
				$this->fixAdminUserPermissions();
			}
			
			$appName = config('larapen.core.item.name', 'App');
			
			$message = 'You website is up-to-date! ';
			$message .= "$appName v$lastVersion is currently the newest version available.";
			flash($message)->info();
			
			return redirect()->to('/');
		}
		
		// Installed version number is NOT found
		if (version_compare('1.0.0', $currentVersion, '>')) {
			$message = 'Cannot find the app\'s current version number in the "/.env" file.';
			throw new AppVersionNotFoundException($message);
		}
		
		// Go to maintenance with DOWN status
		Artisan::call('down');
		
		// Perform the application cleanup
		$this->performApplicationCleanup();
		
		// Update files & Upgrade the database
		$this->applyUpdateChanges($lastVersion, $currentVersion);
		
		// If non-secured upgrade is allowed due to Admin User permission issue,
		// then, fix the Admin User Permissions
		if (self::$useNonSecureUpgrade) {
			$this->fixAdminUserPermissions();
		}
		
		// (Try to) Sync. the multi-country URLs with the dynamics routes
		$this->syncMultiCountryUrlsAndRoutes();
		
		// Update the current version to last version
		$this->setCurrentVersion($lastVersion);
		
		// (Try to) Fill the missing lines in all languages files
		$this->syncLanguageFilesLines();
		
		// Check the Purchase Code
		$this->checkPurchaseCode();
		
		// Perform the application cleanup
		$this->performApplicationCleanup();
		
		// Restore system UP status
		Artisan::call('up');
		
		// Success message
		$successMessage = '<strong>Congratulations!</strong> Your website has been upgraded to v' . $lastVersion;
		flash($successMessage)->success();
		
		// Warning message
		$warningMessage = 'IMPORTANT: If you have installed plugins, you should also upgrade them to their latest version.';
		flash($warningMessage)->warning();
		
		// Redirection
		$defaultCountryCode = $this->getDefaultCountryCode();
		if (empty($defaultCountryCode)) {
			if (doesCountriesPageCanBeHomepage()) {
				return redirect()->to('/');
			} else {
				$countryListPageUri = config('larapen.localization.countries_list_uri', '/');
				$countryListPageUri = castToString($countryListPageUri, '/');
				
				return redirect()->to($countryListPageUri);
			}
		} else {
			return redirect()->to('/');
		}
	}
	
	/**
	 * Get the app's default country
	 *
	 * @return string|null
	 */
	private function getDefaultCountryCode(): ?string
	{
		$defaultCountryCode = config('settings.localization.default_country_code');
		
		try {
			$setting = Setting::query()
				->active()
				->where('name', '=', 'localization')
				->first();
			
			if (!empty($setting)) {
				$fieldValues = $setting->field_values ?? [];
				$defaultCountryCode = $fieldValues['default_country_code'] ?? $defaultCountryCode;
			}
		} catch (Throwable $e) {
		}
		
		return $defaultCountryCode;
	}
}
