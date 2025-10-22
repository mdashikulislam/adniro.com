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

use App\Enums\BootstrapColor;
use App\Helpers\Common\Arr;
use App\Helpers\Common\DBUtils;
use App\Helpers\Common\DotenvEditor;
use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Services\Localization\Country as CountryHelper;
use App\Helpers\Services\Localization\Helpers\Country;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Front\Account\AccountBaseController;
use App\Http\Controllers\Web\Front\Post\ReportController;
use App\Http\Controllers\Web\Front\Search\SearchController;
use App\Http\Controllers\Web\Front\SitemapController;
use App\Http\Controllers\Web\Front\SitemapsController;
use App\Http\Controllers\Web\Setup\Install\SiteInfoController;
use App\Http\Controllers\Web\Setup\Update\UpdateController;
use App\Models\Language;
use App\Models\MetaTag;
use App\Models\Package;
use App\Models\Page;
use App\Models\Post;
use App\Models\Section;
use App\Models\User;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Intervention\Image\Laravel\Facades\Image as FacadeImage;
use Larapen\LaravelDistance\Helper;
use Larapen\TextToImage\Facades\TextToImage;
use Mews\Purifier\Facades\Purifier;

/**
 * Get View Content
 *
 * @param string $view
 * @param array $data
 * @param array $mergeData
 * @return string
 */
function getViewContent(string $view, array $data = [], array $mergeData = []): string
{
	return view($view, $data, $mergeData)->render();
}

/**
 * Get View Content If Exists
 *
 * @param string $view
 * @param array $data
 * @param array $mergeData
 * @return string
 */
function getViewContentIfExists(string $view, array $data = [], array $mergeData = []): string
{
	if (!view()->exists($view)) return '';
	
	return view($view, $data, $mergeData)->render();
}

/**
 * Add options' group's JavaScript Code
 * (for settings models in the Admin Panel)
 *
 * @param string $settingNamespace
 * @param string $groupClassName
 * @param array $fields
 * @param array $data
 * @return array
 */
function addOptionsGroupJavaScript(
	string $settingNamespace,
	string $groupClassName,
	array  $fields,
	array  $data = []
): array
{
	// Normalize plugins namespaces
	$namespaceBase = 'App';
	if (
		str_starts_with($settingNamespace, 'extras\plugins\\')
		&& str_contains($settingNamespace, strtolower($namespaceBase) . '\\')
	) {
		$settingNamespace = str($settingNamespace)
			->after(strtolower($namespaceBase))
			->prepend($namespaceBase)
			->toString();
		$groupClassName = str($groupClassName)
			->after(strtolower($namespaceBase))
			->prepend($namespaceBase)
			->toString();
	}
	
	$parts = explode('\\', $settingNamespace);
	$modelName = $parts[2] ?? '';
	$belongsTo = $parts[3] ?? '';
	
	$modelJsDirName = str($modelName)->kebab()->toString();
	$belongsToJsDirName = !empty($belongsTo) ? '.' . $belongsTo : '';
	$optionsGroupKey = str($groupClassName)->classBasename()->remove($modelName)->kebab()->toString();
	
	$view = 'admin.js.' . $modelJsDirName . $belongsToJsDirName . '.' . $optionsGroupKey;
	
	$js = getViewContentIfExists($view, $data);
	
	return array_merge($fields, [
		[
			'name'  => 'javascript',
			'type'  => 'custom_html',
			'value' => $js,
		],
	]);
}

/**
 * Configuring Eloquent Strictness
 *
 * @param bool $isEnabled
 * @return void
 */
function preventLazyLoadingForModelRelations(bool $isEnabled = true): void
{
	/*
	 * Configuring Eloquent Strictness
	 * - Disable lazy loading (completely) to increase performance optimization
	 * - Prevent silently discarding attributes
	 * WARNING: Never apply that on production to prevent exception errors.
	 */
	if (!appIsBeingInstalledOrUpgraded()) {
		$isEnabled = (
			$isEnabled
			&& !app()->isProduction()
			&& config('larapen.core.performance.preventLazyLoading')
			&& request()->segment(1) != 'feed'
		);
		Model::preventLazyLoading($isEnabled);
	}
}

/**
 * Get all countries from PHP array (umpirsky)
 *
 * @param bool $raw
 * @return array|null
 */
function getCountriesFromArray(bool $raw = false): ?array
{
	$countries = new App\Helpers\Services\Localization\Helpers\Country();
	$countries = $countries->all();
	
	if (empty($countries)) return null;
	
	$arr = [];
	foreach ($countries as $code => $value) {
		if (!$raw) {
			$filePath = storage_path('database/geonames/countries/' . strtolower($code) . '.sql');
			if (!file_exists($filePath)) {
				continue;
			}
		}
		$row = ['value' => $code, 'text' => $value];
		$arr[] = $row;
	}
	
	return $arr;
}

/**
 * Get all countries from DB (Geonames) & Translate them
 *
 * @param bool $includeNonActive
 * @return array
 */
function getCountries(bool $includeNonActive = false): array
{
	$arr = [];
	
	// Get installed countries list
	$countries = CountryHelper::getCountries($includeNonActive);
	
	if ($countries->count() > 0) {
		foreach ($countries as $code => $country) {
			// The country entry must be a Laravel Collection object
			if (!$country instanceof Collection) {
				$country = collect($country);
			}
			
			// Get the country data
			$code = $country->has('code') ? $country->get('code') : $code;
			$name = $country->has('name') ? $country->get('name') : '';
			$arr[$code] = $name;
		}
	}
	
	return $arr;
}

/**
 * @return array
 */
function getCountriesCodes(): array
{
	// Get the countries from the umpirsky database
	$countries = getCountriesFromArray(raw: true);
	$umpirskyCodes = collect($countries)->keyBy('value')->keys();
	
	// Get countries from the app's Geonames SQL files
	$filesCodes = collect();
	$filesDirPath = storage_path('database/geonames/countries/');
	if (is_dir($filesDirPath)) {
		$files = array_filter(glob($filesDirPath . '*.sql'), 'is_file');
		$filesCodes = collect($files)
			->map(function ($item) {
				$pathParts = pathinfo($item);
				$code = $pathParts['filename'] ?? null;
				
				return !empty($code) ? strtoupper($code) : null;
			})->flip()->keys();
	}
	
	// All countries codes
	$codes = $umpirskyCodes->merge($filesCodes)->unique();
	
	return $codes->toArray();
}

/**
 * @return string
 */
function getCountryCodeRoutePattern(): string
{
	// Country Code Pattern
	$countriesCodes = collect(getCountriesCodes())->map(fn ($item) => strtolower($item));
	$countryCodePattern = $countriesCodes->isNotEmpty() ? $countriesCodes->join('|') : null;
	$countryCodePattern = !empty($countryCodePattern) ? $countryCodePattern : 'us';
	
	/*
	 * NOTE:
	 * '(?i:foo)' : Make 'foo' case-insensitive
	 */
	
	return '(?i:' . $countryCodePattern . ')';
}

/**
 * @return bool
 */
function doesCountriesPageCanBeHomepage(): bool
{
	return (
		file_exists(storage_path('framework/plugins/domainmapping'))
		&& (config('larapen.core.dmCountriesListAsHomepage') == true)
		&& (getHost() == getHost(config('app.url')))
	);
}

/**
 * @return bool
 */
function doesCountriesPageCanBeLinkedToTheHomepage(): bool
{
	return (
		file_exists(storage_path('framework/plugins/domainmapping'))
		&& (config('larapen.core.dmCountriesListAsHomepage') == true)
		&& (getHost() != getHost(config('app.url')))
	);
}

/**
 * Get URL (based on Country Domain) related to the given country (or country code)
 * This is the url() function to match country domains
 *
 * @param \Illuminate\Support\Collection|string|null $country
 * @param string|null $path
 * @param bool $forceCountry
 * @param bool $forceLocale
 * @return string
 */
function dmUrl(Collection|string|null $country, ?string $path = '/', bool $forceCountry = false, bool $forceLocale = false): string
{
	if (empty($path)) {
		$path = '/';
	}
	
	$country = getValidCountry($country);
	if (empty($country)) {
		return castToString(url($path));
	}
	
	// Clear the path
	$path = ltrim($path, '/');
	
	// Get the country main language code
	$langCode = getCountryMainLangCode($country);
	
	// Get the country main language path
	$langPath = '';
	if ($forceLocale) {
		if (!empty($langCode)) {
			$parseUrl = mb_parse_url(url($path));
			if (!isset($parseUrl['path']) || ($parseUrl['path'] == '/')) {
				$langPath = '/locale/' . $langCode;
			}
			if (isFromUrlAlwaysContainingCountryCode($path)) {
				$langPath = '/' . $langCode;
			}
		}
	}
	
	// Get the country domain data from the Domain Mapping plugin,
	// And get a new URL related to domain, country language & given path
	$domain = collect((array)config('domains'))->firstWhere('country_code', $country->get('code'));
	if (!empty($domain['url'])) {
		$path = preg_replace('#' . $country->get('code') . '/#ui', '', $path, 1);
		
		$url = rtrim($domain['url'], '/') . $langPath;
		$url = $url . (!empty($path) ? '/' . $path : '');
	} else {
		$url = rtrim(config('app.url', ''), '/') . $langPath;
		$url = $url . (!empty($path) ? '/' . $path : '');
		if ($forceCountry) {
			$url = $url . ('?country=' . $country->get('code'));
		}
	}
	
	return $url;
}

/**
 * Get Valid Country's Object (as Laravel Collection)
 *
 * @param \Illuminate\Support\Collection|string|null $country
 * @return \Illuminate\Support\Collection|null
 */
function getValidCountry(Collection|string|null $country): ?Collection
{
	// If given country value is a string & having 2 characters (like country code),
	// Get the country collection by the country code.
	if (is_string($country)) {
		if (strlen($country) == 2) {
			$country = CountryHelper::getCountryInfo($country);
			if ($country->isEmpty() || !$country->has('code')) {
				return null;
			}
		} else {
			return null;
		}
	}
	
	// Country collection is required to continue
	if (!($country instanceof Collection)) {
		return null;
	}
	
	// Country collection code is required to continue
	if (!$country->has('code')) {
		return null;
	}
	
	return $country;
}

/**
 * Get Country Main Language Code
 *
 * @param \Illuminate\Support\Collection|string|null $country
 * @return string|null
 */
function getCountryMainLangCode(Collection|string|null $country): ?string
{
	$country = getValidCountry($country);
	if (empty($country)) {
		return null;
	}
	
	// Get the country main language code
	$langCode = null;
	if ($country->has('lang')) {
		$countryLang = $country->get('lang');
		if ($countryLang instanceof Collection && $countryLang->has('code')) {
			$langCode = $countryLang->get('code');
		}
	} else {
		if ($country->has('languages')) {
			$countryLang = CountryHelper::getLangFromCountry($country->get('languages'));
			if ($countryLang->has('code')) {
				$langCode = $countryLang->get('code');
			}
		} else {
			// From XML Sitemaps
			if ($country->has('locale')) {
				$langCode = $country->get('locale');
			}
		}
	}
	
	return $langCode;
}

/**
 * If the Domain Mapping plugin is installed, apply its configs.
 * NOTE: Don't apply them if the session is shared.
 *
 * @param $countryCode
 * @return void
 */
function applyDomainMappingConfig($countryCode): void
{
	if (empty($countryCode)) {
		return;
	}
	
	if (config('plugins.domainmapping.installed')) {
		/*
		 * When the session is shared, the domain name and logo columns are disabled.
		 * The dashboard per country feature is also disabled.
		 * So, it is recommended to access to the Admin panel through the main URL from the /.env file (i.e.: APP_URL/admin)
		 */
		if (!config('settings.domainmapping.share_session')) {
			$domain = collect((array)config('domains'))->firstWhere('country_code', $countryCode);
			if (!empty($domain)) {
				if (!empty($domain['url'])) {
					//\URL::forceRootUrl($domain['url']);
				}
			}
		}
	}
}

/**
 * Is request from installation or from upgrade process
 *
 * @return bool
 */
function isFromInstallOrUpgradeProcess(): bool
{
	return isFromInstallProcess() || isFromUpgradeProcess();
}

/**
 * Is request from installation process
 *
 * @return bool
 */
function isFromInstallProcess(): bool
{
	return (
		routeActionHas(getClassNamespaceName(SiteInfoController::class))
		&& request()->segment(1) == 'install'
	);
}

/**
 * Is request from upgrade process
 *
 * @return bool
 */
function isFromUpgradeProcess(): bool
{
	return (
		routeActionHas(getClassNamespaceName(UpdateController::class))
		&& request()->segment(1) == 'upgrade'
	);
}

/**
 * Alias of isAdminPanel() function
 *
 * @param $url
 * @return bool
 */
function isFromAdminPanel($url = null): bool
{
	return isAdminPanel($url);
}

/**
 * Check if user is located in the Admin panel
 * NOTE: Please see the provider of the package: lab404/laravel-impersonate
 *
 * @param string|null $url
 * @return bool
 */
function isAdminPanel(string $url = null): bool
{
	if (empty($url)) {
		$isValid = (
			request()->segment(1) == urlGen()->adminUri()
			|| request()->segment(1) == 'impersonate'
			|| routeActionHas(getClassNamespaceName(DashboardController::class))
		);
	} else {
		try {
			$urlPath = str(parse_url($url, PHP_URL_PATH))->start('/')->toString();
			$adminUri = str(urlGen()->adminUri())->start('/')->toString();
			
			$isValid = (
				str_starts_with($urlPath, $adminUri)
				|| str_starts_with($urlPath, '/impersonate')
			);
		} catch (Throwable $e) {
			$isValid = false;
		}
	}
	
	return $isValid;
}

/**
 * Check dev environment
 *
 * @param string|null $url
 * @return bool
 */
function isDevEnv(string $url = null): bool
{
	if (empty($url)) {
		$url = config('app.url');
	}
	
	$domain = getDomain($url);
	
	return (
		str_contains($domain, 'bedigit.local')
		|| str_contains($domain, 'laraclassifier.local')
	);
}

/**
 * Check demo environment
 *
 * @param string|null $url
 * @return bool
 */
function isDemoEnv(string $url = null): bool
{
	if (empty($url)) {
		$url = config('app.url');
	}
	
	return (
		getDomain($url) == config('larapen.core.demo.domain')
		|| in_array(getHost($url), (array)config('larapen.core.demo.hosts'))
	);
}

/**
 * Check the demo website domain
 *
 * @param string|null $url
 * @return bool
 */
function isDemoDomain(string $url = null): bool
{
	$isDemoDomain = isDemoEnv($url);
	
	if (!$isDemoDomain) {
		return false;
	}
	
	$guard = getAuthGuard();
	$authUser = auth($guard)->check() ? auth($guard)->user() : null;
	
	if (!empty($authUser)) {
		if (doesUserHaveStaffPermission($authUser) && isDemoSuperAdmin($authUser)) {
			$isDemoDomain = false;
		}
	}
	
	return $isDemoDomain;
}

/**
 * Check if the filled email is one demo reserved email
 *
 * @param string|null $email
 * @return bool
 */
function isDemoEmailAddress(?string $email): bool
{
	if (empty($email)) return false;
	
	return in_array($email, getDemoEmailAddresses());
}

/**
 * @param $authUser
 * @return bool
 */
function isDemoSuperAdmin($authUser): bool
{
	if (empty($authUser)) return false;
	if (empty($authUser->email)) return false;
	
	return md5($authUser->email) == '5208f2db43b64766fe77fd0047dd9a22';
}

/**
 * Get demo skin color's image
 *
 * @param $skin
 * @return string|null
 */
function getDemoSkinColorImage($skin): ?string
{
	$skinsArray = getCachedReferrerList('skins');
	if (!isset($skinsArray[$skin])) {
		return null;
	}
	
	$skinInfo = $skinsArray[$skin];
	
	$filename = $skin . '.png';
	$filePath = public_path('vendor/demo/preview/images/icons/' . $filename);
	$fileUrl = '/vendor/demo/preview/images/icons/' . $filename;
	
	if (!file_exists($filePath)) {
		try {
			
			// Create a new empty image resource with red background
			$image = FacadeImage::create(70, 35)->fill($skinInfo['color']);
			
			// Save the file in png format
			$image->save($filePath);
			
		} catch (Throwable $e) {
			return null;
		}
	}
	
	return $fileUrl;
}

/**
 * @param string|null $path
 * @return string
 */
function getDemoFilesBasePath(string $path = null): string
{
	$appSlug = config('larapen.core.item.slug');
	$appSlug = !empty($appSlug) ? $appSlug . '/' : '';
	$path = !empty($path) ? $path . '/' : '';
	
	return __DIR__ . '/../../../../../dataFactory/' . $appSlug . $path;
}

/**
 * Get the Country Code from URI Path
 *
 * @return string|null
 */
function getCountryCodeFromPath(): ?string
{
	$countryCode = null;
	
	// With these URLs, the language code and the country code can be available in the segments
	// (If the "Multi-countries URLs" is enabled)
	if (isFromUrlThatCanContainCountryCode()) {
		$countryCode = request()->segment(1);
	}
	
	// With these URLs, the language code and the country code are available in the segments
	if (isFromUrlAlwaysContainingCountryCode()) {
		$countryCode = request()->segment(2);
	}
	
	return $countryCode;
}

/**
 * Check if user is coming from a URL that can contain the country code
 * With these URLs, the language code and the country code can be available in the segments
 * (If the "Multi-countries URLs" is enabled)
 *
 * @return bool
 */
function isFromUrlThatCanContainCountryCode(): bool
{
	if (isMultiCountriesUrlsEnabled()) {
		if (
			routeActionHas(getClassNamespaceName(SearchController::class))
			|| routeActionHas(SitemapController::class)
		) {
			return true;
		}
	}
	
	return false;
}

/**
 * Check if called page can always have the country code
 * With these URLs, the language code and the country code are available in the segments
 *
 * @param string|null $url
 * @return bool
 */
function isFromUrlAlwaysContainingCountryCode(string $url = null): bool
{
	if (empty($url)) {
		$isValid = (
			str_ends_with(request()->url(), '.xml')
			|| routeActionHas(SitemapsController::class)
		);
	} else {
		$isValid = (str_ends_with($url, '.xml'));
	}
	
	return $isValid;
}

/**
 * Is 'utf8mb4' is set as the database Charset
 * and 'utf8mb4_unicode_ci' is set as the database collation
 *
 * @return bool
 */
function isUtf8mb4Available(): bool
{
	// Get the default charset & collation
	$defaultConnection = config('database.default');
	$databaseCharset = config("database.connections.{$defaultConnection}.charset");
	$databaseCollation = config("database.connections.{$defaultConnection}.collation");
	
	// Get the 4-Byte charset & collations
	$configDbEncodingKey = 'larapen.core.database.encoding';
	$fourBytesCharset = config("{$configDbEncodingKey}.default.charset", 'utf8mb4');
	$fourBytesCollations = config("{$configDbEncodingKey}.recommended.{$fourBytesCharset}");
	$fourBytesCollations = $fourBytesCollations ?? ['utf8mb4_unicode_ci'];
	
	// Allow Emojis when the database charset is 'utf8mb4'
	// and the database collation is 'utf8mb4_unicode_ci' or 'utf8mb4_0900_ai_ci'
	if ($databaseCharset == $fourBytesCharset && in_array($databaseCollation, $fourBytesCollations)) {
		return true;
	}
	
	return false;
}

/**
 * Check if UTF-8 4(+)-byte characters is enabled
 *
 * @return bool
 */
function isUtf8mb4Enabled(): bool
{
	return (isUtf8mb4Available() && config('settings.listing_form.utf8mb4_enabled') == '1');
}

/**
 * Check if emojis characters is enabled
 *
 * @return bool
 */
function isEmojisEnabled(): bool
{
	return (isUtf8mb4Enabled() && config('settings.listing_form.allow_emojis') == '1');
}

/**
 * Check if the listing form WYSIWYG editor is enabled
 *
 * @return bool
 */
function isWysiwygEnabled(): bool
{
	return (config('settings.listing_form.wysiwyg_editor') != 'none');
}

/**
 * HTML Purifier cleaner
 *
 * More Info:
 * http://htmlpurifier.org/
 *
 * @param string|null $string
 * @return string
 */
function htmlPurifierCleaner(?string $string): string
{
	if (empty($string)) return '';
	
	if (isWysiwygEnabled()) {
		try {
			$string = Purifier::clean($string);
		} catch (Throwable $e) {
		}
		$string = stripUtf8mb4CharsIfNotEnabled($string);
	} else {
		$string = multiLinesStringCleaner($string);
		
		if (request()->isMethod('get')) {
			$string = nl2br($string);
		}
	}
	
	if (request()->isMethod('get')) {
		$string = urlsToLinks($string);
	}
	
	$string = (isFromApi() && !doesRequestIsFromWebClient())
		? singleLineStringCleaner($string)
		: $string;
	
	return castToString($string);
}

/**
 * Remove 4(+)-byte characters (If it is not enabled)
 *
 * @param string|null $string
 * @return string
 */
function stripUtf8mb4CharsIfNotEnabled(?string $string): string
{
	if (empty($string)) return '';
	
	if (!isUtf8mb4Enabled()) {
		$string = stripUtf8mb4Chars($string);
	} else {
		if (!isEmojisEnabled()) {
			$string = stripEmojis($string);
		}
	}
	
	return castToString($string);
}

/**
 * Tags Cleaner
 *
 * @param $value
 * @param bool $asArray
 * @return array|string|null
 */
function tagCleaner($value, bool $asArray = false): array|string|null
{
	$limit = (int)config('settings.listing_form.tags_limit', 15);
	
	return taggable($value, $limit, $asArray);
}

/**
 * Return an array of spoken languages in the selected country
 *
 * @return array
 */
function getCountrySpokenLanguages(): array
{
	$supportedLanguages = getSupportedLanguages();
	
	$spokenLanguages = config('country.languages');
	$spokenLanguages = explode(',', $spokenLanguages);
	if (config('settings.localization.show_country_spoken_languages') == 'active_with_en') {
		$spokenLanguages[] = 'en';
	}
	if (config('settings.localization.show_country_spoken_languages') == 'active_with_main') {
		$spokenLanguages[] = strtolower(config('appLang.code'));
	}
	
	if (empty($spokenLanguages)) return [];
	
	return collect($spokenLanguages)
		->unique()
		->map(function ($item) use ($supportedLanguages) {
			if (empty($supportedLanguages)) return $item;
			
			foreach ($supportedLanguages as $code => $lang) {
				if (str_starts_with($code, $item)) {
					$item = $lang;
					break; // Important
				}
			}
			
			return $item;
		})
		->filter(fn ($item) => is_array($item))
		->keyBy('code')
		->toArray();
}

/**
 * Return an array of all supported languages
 *
 * @return array
 */
function getSupportedLanguages(): array
{
	// Get supported languages from database
	try {
		// Get all DB Languages
		$cacheParams = [
			'action'  => 'get.languages',
			'active'  => true,
			'orderBy' => 'lft',
		];
		$supportedLanguages = caching()->remember(Language::class, $cacheParams, function () {
			return Language::where('active', 1)->orderBy('lft')->get();
		});
		
		$supportedLanguages = collect($supportedLanguages->toArray());
		
		if ($supportedLanguages->isNotEmpty()) {
			$supportedLanguages = $supportedLanguages->keyBy('code');
		}
	} catch (Throwable $e) {
		/*
		 * Database or tables don't exist.
		 * The script will display an error or will start the installation.
		 * Please don't change anything here.
		 */
		$supportedLanguages = collect();
	}
	
	return $supportedLanguages->toArray();
}

/**
 * Check if language code is available
 *
 * @param string|null $code
 * @return bool
 */
function isAvailableLang(?string $code): bool
{
	$cacheParams = [
		'action' => 'get.language',
		'code'   => $code,
	];
	
	$lang = caching()->remember(Language::class, $cacheParams, function () use ($code) {
		return Language::where('code', '=', $code)->first();
	});
	
	return !empty($lang);
}

/**
 * @return string
 */
function detectLocale(): string
{
	$lang = detectLanguage();
	
	$defaultLocale = 'en_US';
	$locale = !$lang->isEmpty() ? $lang->get('locale') : $defaultLocale;
	
	return castToString($locale, $defaultLocale);
}

/**
 * @return \Illuminate\Support\Collection
 */
function detectLanguage(): Collection
{
	$obj = new App\Helpers\Services\Localization\Language();
	
	return $obj->find();
}

/**
 * Pluralization
 *
 * @param $number
 * @return float|int
 */
function getPlural($number): float|int
{
	return numberPlural($number, config('lang.russian_pluralization'));
}

/**
 * Get URL of Page by page's type
 *
 * @param string|null $type
 * @param string|null $locale
 * @return string
 */
function getUrlPageByType(?string $type, string $locale = null): string
{
	if (is_null($locale)) {
		$locale = config('app.locale');
	}
	
	$cacheParams = [
		'action' => 'get.page',
		'type'   => $type,
		'locale' => $locale,
	];
	
	$page = caching()->remember(Page::class, $cacheParams, function () use ($type, $locale) {
		$page = Page::type($type)->first();
		
		if (!empty($page)) {
			$page->setLocale($locale);
		}
		
		return $page;
	});
	
	$linkTarget = '';
	$linkRel = '';
	if (!empty($page)) {
		if (!empty($page->target)) {
			$linkTarget = ' target="' . $page->target . '"';
		}
		if (!empty($page->external_link)) {
			$linkRel = ' rel="nofollow"';
			$url = $page->external_link;
		} else {
			$url = urlGen()->page($page);
		}
	} else {
		$url = '#';
	}
	$linkClass = ' class="' . linkClass() . '"';
	
	// Get attributes
	return 'href="' . $url . '"' . $linkClass . $linkRel . $linkTarget;
}

/**
 * @return array
 */
function getRecommendedFileFormats(): array
{
	$defaultFileFormats = ['pdf', 'doc', 'docx', 'rtf', 'rtx', 'ppt', 'pptx', 'odt', 'odp', 'wps'];
	$imageInstalledFormats = getServerInstalledImageFormats();
	
	return array_merge($defaultFileFormats, $imageInstalledFormats);
}

/**
 * @return array
 */
function getAllowedFileFormats(): array
{
	$recommendedFormats = getRecommendedFileFormats();
	
	$formatList = config('settings.upload.file_types');
	$formatList = normalizeSeparatedList($formatList);
	
	$formats = explode(',', $formatList);
	$formats = array_filter($formats, fn ($item) => $item !== '');
	
	return !empty($formats) ? $formats : $recommendedFormats;
}

/**
 * @param string|null $typeGroup
 * @return string
 */
function getAllowedFileFormatsHint(?string $typeGroup = 'file'): string
{
	$formats = ($typeGroup == 'image')
		? getServerAllowedImageFormats()
		: getAllowedFileFormats();
	
	return collect($formats)->join(', ', t('_and_'));
}

/**
 * Normalize a string-separated list by replacing unwanted separators and joining elements.
 *
 * @param string|array|null $value
 * @param string|null $separator
 * @param array|string $charsToGuard
 * @return string
 */
function normalizeSeparatedList(array|string|null $value, ?string $separator = ',', array|string $charsToGuard = ''): string
{
	if (empty($value)) return '';
	
	$separator = $separator ?: ',';
	$badSeparators = [t('_and_'), t('_or_'), '|', '-', ';', '.', '/', '_', ' '];
	
	if (is_string($charsToGuard)) {
		$charsToGuard = explode(',', $charsToGuard);
	}
	
	if (is_string($value)) {
		$badSeparators = array_diff($badSeparators, $charsToGuard);
		$value = str_replace($badSeparators, $separator, $value);
		$value = explode($separator, $value);
	}
	
	$value = array_filter($value, fn ($item) => is_string($item) && $item !== '');
	
	return collect($value)->join($separator);
}

/**
 * Get Public File's URL
 *
 * @param string|null $filePath
 * @return string
 */
function fileUrl(?string $filePath): string
{
	// Storage Disk Init.
	$disk = StorageDisk::getDisk();
	
	try {
		$url = $disk->url($filePath);
	} catch (Throwable $e) {
		$url = url('common/file?path=' . $filePath);
	}
	
	return castToString($url);
}

/**
 * Get Private File's URL
 *
 * @param string|null $filePath
 * @param string|null $diskName
 * @return string
 */
function privateFileUrl(?string $filePath, ?string $diskName = 'private'): string
{
	$queryString = 'path=' . $filePath;
	
	// For JC
	if (str_starts_with($filePath, 'resumes/')) {
		$diskName = 'private';
	}
	
	if (!empty($diskName)) {
		$queryString = 'disk=' . $diskName . '&' . $queryString;
	}
	
	$url = url('common/file?' . $queryString);
	
	return castToString($url);
}

/**
 * @param string|null $srcFallback
 * @param string|null $alt
 * @param string|null $srcWebP
 * @param array $attributes
 * @return string
 */
function generateImageHtml(?string $srcFallback, ?string $alt = '', ?string $srcWebP = '', array $attributes = []): string
{
	$srcFallback = strval($srcFallback);
	$alt = strval($alt);
	$srcWebP = strval($srcWebP);
	
	// Initialize the attributes string
	$attributesString = '';
	
	// Loop through the attributes array and build the attributes string
	foreach ($attributes as $key => $value) {
		$attributesString .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($value));
	}
	
	// Check if WebP source is provided
	if (!empty($srcWebP)) {
		// Return the HTML code for <picture> element with WebP support
		return sprintf(
			'<picture>
                <source srcset="%s" type="image/webp">
                <img src="%s" alt="%s"%s>
            </picture>',
			htmlspecialchars($srcWebP),
			htmlspecialchars($srcFallback),
			htmlspecialchars($alt),
			$attributesString
		);
	} else {
		// Return the conventional <img> tag
		return sprintf(
			'<img src="%s" alt="%s"%s>',
			htmlspecialchars($srcFallback),
			htmlspecialchars($alt),
			$attributesString
		);
	}
}

/**
 * Get pictures version
 *
 * @param bool $queryStringExists
 * @return string
 */
function getPictureVersion(bool $queryStringExists = false): string
{
	$pictureVersion = '';
	if (config('larapen.media.versioned') && !empty(config('larapen.media.version'))) {
		$pictureVersion .= ($queryStringExists) ? '&' : '?';
		$pictureVersion .= 'v=' . config('larapen.media.version');
	}
	
	return $pictureVersion;
}

/**
 * Replace global variables patterns from string
 *
 * @param string|null $string
 * @param bool $removeUnmatchedPatterns
 * @return string
 */
function replaceGlobalPatterns(?string $string, bool $removeUnmatchedPatterns = true): string
{
	$string = str_replace('{app.name}', config('app.name'), $string);
	$string = str_replace('{country.name}', config('country.name'), $string);
	$string = str_replace('{country}', config('country.name'), $string);
	
	if (config('settings.app.slogan')) {
		$string = str_replace('{app.slogan}', config('settings.app.slogan'), $string);
	}
	
	if (str_contains($string, '{count.listings}')) {
		try {
			$countPosts = Post::query()->inCountry()->has('country')->unarchived()->count();
		} catch (Throwable $e) {
			$countPosts = 0;
		}
		$string = str_replace('{count.listings}', $countPosts, $string);
	}
	if (str_contains($string, '{count.users}')) {
		try {
			$countUsers = User::query()->count();
		} catch (Throwable $e) {
			$countUsers = 0;
		}
		$string = str_replace('{count.users}', $countUsers, $string);
	}
	
	if ($removeUnmatchedPatterns) {
		$string = removeUnmatchedPatterns($string);
	}
	
	return castToString($string);
}

/**
 * Get meta tag from settings
 *
 * @param string|null $page
 * @return array
 */
function getMetaTag(?string $page): array
{
	$metaTag = ['title' => '', 'description' => '', 'keywords' => ''];
	
	// Check if the Domain Mapping plugin is available
	if (config('plugins.domainmapping.installed')) {
		$domainMappingClass = \extras\plugins\domainmapping\Domainmapping::class;
		if (class_exists($domainMappingClass)) {
			$metaTag = $domainMappingClass::getMetaTag($page);
			if (!empty($metaTag) && !isArrayOfEmptyElements($metaTag)) {
				return $metaTag;
			}
		}
	}
	
	// Get the current Language
	// $languageCode = config('lang.code', config('app.locale'));
	$languageCode = config('app.locale', config('lang.code'));
	
	// Get the Page's MetaTag
	$model = null;
	try {
		$cacheParams = [
			'action' => 'get.metaTag',
			'page'   => $page,
			'locale' => $languageCode,
		];
		
		$model = caching()->remember(MetaTag::class, $cacheParams, function () use ($languageCode, $page) {
			$model = MetaTag::where('page', $page)->first(['title', 'description', 'keywords']);
			
			if (!empty($model)) {
				$model->setLocale($languageCode);
				$model = $model->toArray();
			}
			
			return $model;
		});
	} catch (Throwable $e) {
	}
	
	if (!empty($model)) {
		$metaTag = $model;
		
		$metaTag['title'] = getColumnTranslation($metaTag['title'], $languageCode);
		$metaTag['description'] = getColumnTranslation($metaTag['description'], $languageCode);
		$metaTag['keywords'] = getColumnTranslation($metaTag['keywords'], $languageCode);
		
		$metaTag['title'] = replaceGlobalPatterns($metaTag['title'], false);
		$metaTag['description'] = replaceGlobalPatterns($metaTag['description'], false);
		$metaTag['keywords'] = mb_strtolower(replaceGlobalPatterns($metaTag['keywords'], false));
		
		$metaTag = normalizeMetaTagValues($metaTag);
		
		return array_values($metaTag);
	}
	
	$pagesThatHaveTheirOwnDefaultMetaTags = [
		'search',
		'searchCategory',
		'searchLocation',
		'searchProfile',
		'searchTag',
		'listingDetails',
		'staticPage',
	];
	
	if (!in_array($page, $pagesThatHaveTheirOwnDefaultMetaTags)) {
		if (config('settings.app.slogan')) {
			$metaTag['title'] = config('app.name') . ' - ' . config('settings.app.slogan');
		} else {
			$metaTag['title'] = config('app.name') . ' - ' . config('country.name');
		}
		$metaTag['description'] = $metaTag['title'];
	}
	
	if (!is_array($metaTag)) {
		$metaTag = [];
	}
	$metaTag['title'] = $metaTag['title'] ?? null;
	$metaTag['description'] = $metaTag['description'] ?? null;
	$metaTag['keywords'] = $metaTag['keywords'] ?? null;
	
	$metaTag = normalizeMetaTagValues($metaTag);
	
	return array_values($metaTag);
}

/**
 * @param array $tags
 * @return array
 */
function normalizeMetaTagValues(array $tags): array
{
	return collect($tags)
		->map(fn ($item) => singleLineStringCleanerStrict($item))
		->toArray();
}

/**
 * Get the Distance Calculation Unit
 *
 * @param string|null $countryCode
 * @return string
 */
function getDistanceUnit(string $countryCode = null): string
{
	if (empty($countryCode)) {
		$countryCode = config('country.code');
	}
	$unit = Helper::getDistanceUnit($countryCode);
	$unit = t($unit);
	
	return castToString($unit);
}

/**
 * Get Front Skin
 *
 * @param string|null $skin
 * @return string|null
 */
function getFrontSkin(string $skin = null): ?string
{
	$savedSkin = config('settings.style.skin', 'default');
	
	if (!empty($skin)) {
		$skinsArray = getCachedReferrerList('skins');
		if (!array_key_exists($skin, $skinsArray)) {
			$skin = $savedSkin;
		}
	} else {
		$skin = $savedSkin;
	}
	
	return castToStringOrNull($skin);
}

/**
 * Hashids is a small PHP library to generate YouTube-like ids from numbers.
 * Use it when you don't want to expose your database numeric ids to users
 *
 * @param $in
 * @param bool $toNum
 * @param bool $withPrefix
 * @param int $minHashLength
 * @param string $salt
 * @return array|mixed|string|null
 */
function hashId($in, bool $toNum = false, bool $withPrefix = true, int $minHashLength = 11, string $salt = '')
{
	if (!config('settings.seo.listing_hashed_id_enabled') && !isHashedId($in)) {
		return $in;
	}
	
	$hidPrefix = $withPrefix ? config('larapen.core.hashableIdPrefix') : '';
	$hidPrefix = is_string($hidPrefix) ? $hidPrefix : '';
	
	$hashIds = new Hashids($salt, $minHashLength);
	
	if (!$toNum) {
		$out = $hidPrefix . $hashIds->encode($in);
	} else {
		$in = ltrim($in, $hidPrefix);
		$out = $hashIds->decode($in);
		if (isset($out[0])) {
			$out = $out[0];
		}
	}
	
	return !empty($out) ? $out : null;
}

/**
 * @param $in
 * @param int $minHashLength
 * @return bool
 */
function isHashedId($in, int $minHashLength = 11): bool
{
	$hidPrefix = config('larapen.core.hashableIdPrefix');
	$hidPrefixLength = is_string($hidPrefix) ? strlen($hidPrefix) : 0;
	
	return (
		preg_match('/[a-z0-9A-Z]+/', $in)
		&& (strlen($in) == ($minHashLength + $hidPrefixLength))
	);
}

/**
 * Get routes prefixes to ban to match listing route's path
 *
 * @return array
 */
function regexSimilarRoutesPrefixes(): array
{
	$routes = (array)config('routes');
	if (empty($routes)) return [];
	
	$prefixes = [];
	foreach ($routes as $route) {
		if (!isStringable($route)) continue;
		
		$prefix = head(explode('/', (string)$route));
		if (!str_starts_with($prefix, '{')) {
			$prefixes[] = $prefix;
		}
	}
	
	return array_unique($prefixes);
}

/**
 * Check if the user browser is the given value.
 * The given value can be:
 * 'Firefox', 'Chrome', 'Safari', 'Opera', 'MSIE', 'Trident', 'Edge'
 *
 * Usage: doesUserBrowserIs('Chrome') or doesUserBrowserIs() == 'Chrome'
 *
 * @param string|null $browserName
 * @return bool|string
 */
function doesUserBrowserIs(?string $browserName = null): bool|string
{
	$userAgent = request()->server('HTTP_USER_AGENT');
	$browsers = [
		'Edge'              => 'Edg', // Prioritize Edge detection
		'Opera'             => 'OPR', // Prioritize Opera detection
		'Brave'             => 'Brave', // Prioritize Brave detection
		'Vivaldi'           => 'Vivaldi', // Prioritize Vivaldi detection
		'Samsung Internet'  => 'SamsungBrowser', // Prioritize Samsung Internet detection
		'Chrome'            => 'Chrome', // Chrome must come after Edge, Opera, Brave, and Vivaldi
		'Safari'            => 'Safari', // Safari must come after Chrome
		'Firefox'           => 'Firefox', // Firefox can be checked after Chrome and Safari
		'Internet Explorer' => ['MSIE', 'Trident/7'], // Check IE last
	];
	
	foreach ($browsers as $name => $keywords) {
		$keywords = (array)$keywords; // Ensure the keyword is an array
		foreach ($keywords as $keyword) {
			if (str_contains($userAgent, $keyword)) {
				$detectedBrowser = $name;
				
				return $browserName
					? strcasecmp($detectedBrowser, $browserName) === 0
					: $detectedBrowser;
			}
		}
	}
	
	return 'Unknown';
}

/**
 * Get sitemaps indexes
 *
 * @param bool $htmlFormat
 * @return string
 */
function getSitemapsIndexes(bool $htmlFormat = false): string
{
	$out = '';
	
	$countries = Country::transAll(CountryHelper::getCountries());
	if (!$countries->isEmpty()) {
		if ($htmlFormat) {
			$cmFieldStyle = ($countries->count() > 10) ? ' style="height: 205px; overflow-y: scroll;"' : '';
			$out .= '<ul' . $cmFieldStyle . '>';
		}
		foreach ($countries as $country) {
			if (!$country instanceof Collection) {
				continue;
			}
			
			$country = CountryHelper::getCountryInfo($country->get('code'));
			if ($country->isEmpty()) {
				continue;
			}
			
			/*
			// Get the Country's Language Code
			$countryLang = $country->has('lang') ? $country->get('lang') : collect();
			$countryLang = ($countryLang instanceof Collection) ? $countryLang : collect();
			$countryLangCode = $countryLang->has('code') ? $countryLang->get('code') : config('app.locale');
			*/
			
			// Add the Sitemap Index
			if ($htmlFormat) {
				$out .= '<li>' . dmUrl($country, $country->get('icode') . '/sitemaps.xml') . '</li>';
			} else {
				$out .= 'Sitemap: ' . dmUrl($country, $country->get('icode') . '/sitemaps.xml') . "\n";
			}
		}
		if ($htmlFormat) {
			$out .= '</ul>';
		}
	}
	
	return $out;
}

/**
 * Default robots.txt content
 *
 * @return string
 */
function getDefaultRobotsTxtContent(): string
{
	$out = 'User-agent: *' . "\n";
	$out .= 'Allow: /' . "\n";
	$out .= "\n";
	$out .= 'User-agent: *' . "\n";
	$out .= 'Disallow: /' . urlGen()->adminUri() . '/' . "\n";
	$out .= 'Disallow: /assets/' . "\n";
	$out .= 'Disallow: /css/' . "\n";
	$out .= 'Disallow: /js/' . "\n";
	$out .= 'Disallow: /vendor/' . "\n";
	$out .= 'Disallow: /main.php' . "\n";
	$out .= 'Disallow: /index.php' . "\n";
	$out .= 'Disallow: /mix-manifest.json' . "\n";
	$out .= 'Disallow: /*?display=*' . "\n"; // Listings list display mode
	
	$languages = getSupportedLanguages();
	if (!empty($languages)) {
		foreach ($languages as $code => $lang) {
			$out .= 'Disallow: /locale/' . $code . "\n";
		}
	}
	
	$providers = ['facebook', 'linkedin', 'twitter', 'google'];
	foreach ($providers as $provider) {
		$out .= 'Disallow: /auth/connect/' . $provider . "\n";
	}
	
	return $out;
}

function getCreateListingLinkInfo(): array
{
	$authUser = auth()->check() ? auth()->user() : null;
	
	$linkUrl = urlGen()->addPost();
	$linkAttr = '';
	
	if (config('settings.listing_form.mandatory_package_selection') == '1') {
		if (!empty($authUser)) {
			/*
			 * If the user doesn't have any valid subscription,
			 * Force the user to select a package (on the pricing page) to allow him to create new listing
			 *
			 * IMPORTANT:
			 * To avoid excessive memory consumption that could degrade the application performance,
			 * checking the limitation of the number of listings linked to the users' subscription
			 * will be done downstream (when trying to publish new listings).
			 */
			$authUser->loadMissing('payment');
			if (empty($authUser->payment)) {
				$linkUrl = urlGen()->pricing();
			}
		} else {
			// Force the guest to select a package (on the pricing page) to allow him to create new listing
			$linkUrl = urlGen()->pricing();
		}
	}
	
	// Does guest have ability to create listings?
	if (!doesGuestHaveAbilityToCreateListings($authUser)) {
		$linkUrl = '#quickLogin';
		$linkAttr = ' data-bs-toggle="modal" data-bs-target="#quickLogin"';
	}
	
	return [$linkUrl, $linkAttr];
}

/**
 * Does guest have ability to create listings?
 *
 * @param $authUser
 * @return bool
 */
function doesGuestHaveAbilityToCreateListings($authUser = null): bool
{
	if (empty($authUser)) {
		try {
			$guard = getAuthGuard();
			$authUser = auth($guard)->check() ? auth($guard)->user() : null;
		} catch (Throwable $e) {
		}
	}
	
	return (
		!empty($authUser)
		|| config('settings.listing_form.guest_can_submit_listings') == '1'
	);
}

/**
 * Generate the Email Form button
 *
 * @param null $post
 * @param bool $btnBlock
 * @param bool $iconOnly
 * @return string
 */
function genEmailContactBtn($post = null, bool $btnBlock = false, bool $iconOnly = false): string
{
	$post = is_array($post) ? Arr::toObject($post) : $post;
	
	$out = '';
	
	if (!isVerifiedPost($post)) {
		return $out;
	}
	
	$smsNotificationCanBeSent = (
		isPhoneAsAuthFieldEnabled()
		&& config('settings.sms.messenger_notifications') == '1'
		&& $post->auth_field == 'phone'
		&& !empty($post->phone)
	);
	if (empty($post->email) && !$smsNotificationCanBeSent) {
		if ($iconOnly) {
			$out = '<i class="fa-regular fa-envelope" style="color: #dadada"></i>';
		}
		
		return $out;
	}
	
	$btnLink = '#contactUser';
	$btnClass = '';
	if (!auth()->check()) {
		if (config('settings.listing_page.guest_can_contact_authors') != '1') {
			$btnLink = '#quickLogin';
		}
	}
	
	if ($iconOnly) {
		$out .= '<a href="' . $btnLink . '" data-bs-toggle="modal" class="' . linkClass() . '">';
		$out .= '<i class="fa-regular fa-envelope" data-bs-toggle="tooltip" title="' . t('Send a message') . '"></i>';
	} else {
		if ($btnBlock) {
			$btnClass = $btnClass . ' btn-block';
		}
		
		$out .= '<a href="' . $btnLink . '" data-bs-toggle="modal" class="btn btn-secondary' . $btnClass . '">';
		$out .= '<i class="fa-regular fa-envelope"></i> ';
		$out .= t('Send a message');
	}
	$out .= '</a>';
	
	return $out;
}

/**
 * Generate the Phone Number button
 *
 * @param $post
 * @param bool $btnBlock
 * @return string
 */
function genPhoneNumberBtn($post, bool $btnBlock = false): string
{
	$post = is_array($post) ? Arr::toObject($post) : $post;
	
	// Options
	$isWhatsappBtnEnabled = (config('settings.listing_page.enable_whatsapp_btn') == '1');
	$isPreFilledWhatsappMessageEnabled = (config('settings.listing_page.pre_filled_whatsapp_message') == '1');
	$hidePhoneNumberOption = config('settings.listing_page.hide_phone_number');
	$isHiddenPhoneNumberEnabled = in_array($hidePhoneNumberOption, ['1', '2', '3']);
	$isPhoneNumberToImgEnabled = (config('settings.listing_page.convert_phone_number_to_img') == '1');
	$isSecurityTipsEnabled = (config('settings.listing_page.show_security_tips') == '1');
	$doesGuestCanContactAuthors = (config('settings.listing_page.guest_can_contact_authors') == '1');
	
	$out = '';
	
	if (empty($post->phone_intl) || $post->phone_hidden == 1) {
		return $out;
	}
	
	$dataPostId = ' data-post-id="' . $post->id . '"';
	
	$whatsAppPreFilledMessage = $isPreFilledWhatsappMessageEnabled
		? '?text=' . rawurlencode(t('whatsapp_pre_filled_message', [
			'url'     => urlGen()->post($post),
			'title'   => $post->title,
			'appName' => config('app.name'),
		])) : '';
	$whatsAppLink = 'https://wa.me/' . keepOnlyNumericChars($post->phone) . $whatsAppPreFilledMessage;
	$waBtnClass = '';
	
	$btnLink = 'tel:' . $post->phone;
	$btnAttr = '';
	$btnClass = ' phoneBlock'; /* for the showPhone() JS function */
	$btnHint = t('Click to see');
	$phone = $post->phone_intl;
	
	if ($isHiddenPhoneNumberEnabled) {
		$phoneToHide = normalizePhoneNumber($phone);
		if ($hidePhoneNumberOption == '1') {
			$phone = str($phoneToHide)->mask('X', -str($phoneToHide)->length(), str($phoneToHide)->length() - 3)->toString();
		}
		if ($hidePhoneNumberOption == '2') {
			$phone = str($phoneToHide)->mask('X', 3)->toString();
		}
		if ($hidePhoneNumberOption == '3') {
			$phone = str($phoneToHide)->mask('X', 0)->toString();
		}
		$btnLink = '';
		$btnAttrTooltip = 'data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $btnHint . '"';
		$btnClassTooltip = '';
		
		$btnAttr = $btnAttrTooltip;
		$btnClass = $btnClass . $btnClassTooltip;
		
		$isWhatsappBtnEnabled = false;
	} else {
		$btnClass = '';
		if ($isPhoneNumberToImgEnabled) {
			try {
				$phone = TextToImage::make($phone, config('larapen.core.textToImage'));
			} catch (Throwable $e) {
				$phone = $post->phone;
			}
		}
	}
	
	if ($isSecurityTipsEnabled) {
		/*
		    Set multiple data-bs-toggle for link in Bootstrap
			Tooltip + modal in button - Bootstrap
			
			Usage of '[rel="tooltip"]' as selector instead of '[data-bs-toggle="tooltip"]' for the tooltip,
			and trigger that with on hover event from JS
		*/
		$btnAttrTooltip = 'rel="tooltip" data-bs-placement="bottom" title="' . $btnHint . '"';
		$btnClassTooltip = '';
		$btnAttrModal = 'data-bs-toggle="modal"';
		
		$btnLink = '#securityTips';
		$btnAttr = $btnAttrModal . ' ' . $btnAttrTooltip;
		$btnClass = ' phoneBlock'; /* for the showPhone() JS function */
		if (!$isHiddenPhoneNumberEnabled) {
			$phone = t('phone_number');
		}
		$btnClass = $btnClass . ' ' . $btnClassTooltip;
	}
	
	if (!auth()->check()) {
		if (!$doesGuestCanContactAuthors) {
			$btnAttrModal = 'data-bs-toggle="modal"';
			
			$phone = $btnHint;
			$btnLink = '#quickLogin';
			$btnAttr = $btnAttrModal;
			$btnClass = '';
			
			$isWhatsappBtnEnabled = false;
		}
	}
	
	if ($btnBlock) {
		$waBtnClass = $waBtnClass . ' btn-block';
		$btnClass = $btnClass . ' btn-block';
	}
	
	// Generate the Phone Number button
	$class = 'btn btn-warning' . $btnClass;
	$out .= '<a href="' . $btnLink . '"' . $dataPostId . ' ' . $btnAttr . ' class="' . $class . '">';
	$out .= '<i class="fa-solid fa-mobile-screen-button"></i> ';
	$out .= $phone;
	$out .= '</a>';
	
	if ($isWhatsappBtnEnabled) {
		$waBtnAttr = 'data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . t('chat_on_whatsapp') . '"';
		
		// Generate the WhatsApp button
		$class = 'btn btn-success' . $waBtnClass;
		$out .= '<a href="' . $whatsAppLink . '"' . $dataPostId . ' ' . $waBtnAttr . ' target="_blank" class="' . $class . '">';
		$out .= '<i class="fa-brands fa-whatsapp"></i> ';
		$out .= 'WhatsApp';
		$out .= '</a>';
	}
	
	return $out;
}

/**
 * Set the Backup config vars
 *
 * @param string|null $typeOfBackup
 */
function setBackupConfig(string $typeOfBackup = null): void
{
	// Get the current version value
	$version = preg_replace('/[^\d+]/', '', config('version.app'));
	
	// All backup filename prefix
	config()->set('backup.backup.destination.filename_prefix', 'site-v' . $version . '-');
	
	// Database backup
	if ($typeOfBackup == 'database') {
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-db'               => true,
		]);
		config()->set('backup.backup.destination.filename_prefix', 'database-v' . $version . '-');
	}
	
	// Languages' files backup
	if ($typeOfBackup == 'languages') {
		$include = [
			lang_path(),
		];
		$pluginsDirs = glob(config('larapen.core.plugin.path') . '*', GLOB_ONLYDIR);
		if (!empty($pluginsDirs)) {
			foreach ($pluginsDirs as $pluginDir) {
				$pluginLangFolder = $pluginDir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang';
				if (file_exists($pluginLangFolder)) {
					$include[] = $pluginLangFolder;
				}
			}
		}
		
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-files'            => true,
		]);
		config()->set('backup.backup.source.files.include', $include);
		config()->set('backup.backup.source.files.exclude', [
			//...
		]);
		config()->set('backup.backup.destination.filename_prefix', 'languages-');
	}
	
	// Generated files backup
	if ($typeOfBackup == 'files') {
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-files'            => true,
		]);
		config()->set('backup.backup.source.files.include', [
			base_path('.env'),
			storage_path('app/public'),
			storage_path('installed'),
		]);
		config()->set('backup.backup.source.files.exclude', [
			//...
		]);
		config()->set('backup.backup.destination.filename_prefix', 'files-');
	}
	
	// App files backup
	if ($typeOfBackup == 'app') {
		config()->set('backup.backup.admin_flags', [
			'--disable-notifications' => true,
			'--only-files'            => true,
		]);
		config()->set('backup.backup.source.files.include', [
			base_path(),
			// base_path('.gitattributes'),
			base_path('.gitignore'),
		]);
		config()->set('backup.backup.source.files.exclude', [
			base_path('node_modules'),
			base_path('.git'),
			base_path('.idea'),
			base_path('.env'),
			base_path('bootstrap/cache') . DIRECTORY_SEPARATOR . '*',
			public_path('robots.txt'),
			storage_path('app/backup-temp'),
			storage_path('app/database'),
			storage_path('app/public/app/categories/custom') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/app/ico') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/app/logo') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/app/page') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/files') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/public/temporary') . DIRECTORY_SEPARATOR . '*',
			storage_path('app/purifier') . DIRECTORY_SEPARATOR . '*',
			storage_path('database/demo'),
			storage_path('backups'),
			storage_path('dotenv-editor') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/cache') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/sessions') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/testing') . DIRECTORY_SEPARATOR . '*',
			storage_path('framework/views') . DIRECTORY_SEPARATOR . '*',
			storage_path('installed'),
			storage_path('laravel-backups'),
			storage_path('logs') . DIRECTORY_SEPARATOR . '*',
		]);
		config()->set('backup.backup.destination.filename_prefix', 'app-v' . $version . '-');
	}
}

/**
 * Check if User is online
 *
 * @param $user
 * @return bool
 * @throws \Psr\SimpleCache\InvalidArgumentException
 */
function isUserOnline($user): bool
{
	$user = (is_array($user)) ? Arr::toObject($user) : $user;
	
	$isOnline = false;
	
	if (!empty($user) && isset($user->id)) {
		if (config('settings.optimization.cache_driver') == 'array') {
			$isOnline = $user->p_is_online;
		} else {
			$isOnline = cache()->store('file')->has('user-is-online-' . $user->id);
		}
	}
	
	// Allow only logged users to get the other users status
	$guard = getAuthGuard();
	
	return auth($guard)->check() ? $isOnline : false;
}

/**
 * @param string $key
 * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
 */
function dynamicRoute(string $key)
{
	return config($key);
}

/**
 * Set the Db Fallback Locale
 *
 * @param string|null $locale
 * @return void
 */
function setDbFallbackLocale(?string $locale): void
{
	try {
		DotenvEditor::setKey('FALLBACK_LOCALE_FOR_DB', $locale);
		DotenvEditor::save();
	} catch (Throwable $e) {
	}
}

/**
 * Remove the Db Fallback Locale
 *
 * @return void
 */
function removeDbFallbackLocale(): void
{
	try {
		DotenvEditor::setKey('FALLBACK_LOCALE_FOR_DB', 'null');
		DotenvEditor::save();
	} catch (Throwable $e) {
	}
}

/**
 * @param $locale
 * @return void
 */
function addMissingTranslations($locale): void
{
	$masterLocale = config('appLang.code');
	if (empty($locale) || empty($masterLocale)) {
		return;
	}
	
	if ($locale == $masterLocale) {
		return;
	}
	
	// Update sections translatable options
	$section = Section::where('name', 'search_form')->first();
	if (!empty($section)) {
		$values = $section->field_values;
		
		$masterKey = 'title_' . $masterLocale;
		$localeKey = 'title_' . $locale;
		if (isset($values[$masterKey])) {
			$values[$localeKey] = $values[$masterKey];
		}
		
		$masterKey = 'sub_title_' . $masterLocale;
		$localeKey = 'sub_title_' . $locale;
		if (isset($values[$masterKey])) {
			$values[$localeKey] = $values[$masterKey];
		}
		
		$section->field_values = $values;
		
		if ($section->isDirty()) {
			$section->saveQuietly();
		}
	}
	
	// Update the translatable tables columns
	$modelClasses = DBUtils::getAppModelClasses(translatable: true);
	if (empty($modelClasses)) {
		return;
	}
	
	foreach ($modelClasses as $modelClass) {
		$model = new $modelClass;
		
		// Get the translatable columns
		$columns = method_exists($model, 'getTranslatableAttributes')
			? $model->getTranslatableAttributes()
			: [];
		if (empty($columns)) {
			continue;
		}
		
		$modelCollection = $modelClass::query()->withoutGlobalScopes();
		if ($modelCollection->doesntExist()) {
			continue;
		}
		
		foreach ($modelCollection->cursor() as $item) {
			foreach ($columns as $column) {
				$value = $item->getTranslations($column);
				
				if (isset($value[$masterLocale])) {
					$value[$locale] = $value[$masterLocale];
					
					$item->setTranslations($column, $value)
						->saveQuietly();
				}
			}
		}
	}
}

/**
 * SEO Website Verification using meta tags
 * Allow full HTML tag or content="" value
 *
 * @return string
 */
function seoSiteVerification(): string
{
	$engines = [
		'google' => [
			'name'    => 'google-site-verification',
			'content' => config('settings.seo.google_site_verification'),
		],
		'bing'   => [
			'name'    => 'msvalidate.01',
			'content' => config('settings.seo.msvalidate'),
		],
		'yandex' => [
			'name'    => 'yandex-verification',
			'content' => config('settings.seo.yandex_verification'),
		],
		'alexa'  => [
			'name'    => 'alexaVerifyID',
			'content' => config('settings.seo.alexa_verify_id'),
		],
	];
	
	$out = '';
	foreach ($engines as $engine) {
		if (isset($engine['name'], $engine['content']) && $engine['content']) {
			if (preg_match('|<meta[^>]+>|i', $engine['content'])) {
				$out .= $engine['content'] . "\n";
			} else {
				$out .= '<meta name="' . $engine['name'] . '" content="' . $engine['content'] . '" />' . "\n";
			}
		}
	}
	
	return $out;
}

/**
 * @param string|null $path
 * @return string|null
 */
function relativeAppPath(?string $path): ?string
{
	if (isDemoDomain()) {
		return getRelativePath($path);
	}
	
	return $path;
}

/**
 * @param string|null $url
 * @return string|null
 */
function getFilterClearBtn(?string $url): ?string
{
	$out = '';
	if (!empty($url)) {
		$out .= '<a href="' . $url . '" class="' . linkClass() . '" title="' . t('Remove this filter') . '">';
		$out .= '<i class="bi bi-x-lg"></i>';
		$out .= '</a>';
	}
	
	return $out;
}

/**
 * @param string|null $socialNetwork
 * @param array $settings
 * @return bool
 */
function isOldSocialAuthEnabled(?string $socialNetwork = null, array $settings = []): bool
{
	if (empty($settings)) {
		$settings = config('settings.social_auth');
		if (!is_array($settings)) return false;
	}
	
	$isFacebookOauthEnabled = (data_get($settings, 'facebook_client_id') && data_get($settings, 'facebook_client_secret'));
	$isLinkedInOauthEnabled = (data_get($settings, 'linkedin_client_id') && data_get($settings, 'linkedin_client_secret'));
	$isTwitterOauth2Enabled = (data_get($settings, 'twitter_oauth_2_client_id') && data_get($settings, 'twitter_oauth_2_client_secret'));
	$isTwitterOauth1Enabled = (data_get($settings, 'twitter_client_id') && data_get($settings, 'twitter_client_secret'));
	$isGoogleOauthEnabled = (data_get($settings, 'google_client_id') && data_get($settings, 'google_client_secret'));
	
	$isSocialAuthEnabled = (
		data_get($settings, 'social_login_activation')
		&& (
			$isFacebookOauthEnabled
			|| $isLinkedInOauthEnabled
			|| $isTwitterOauth2Enabled
			|| $isTwitterOauth1Enabled
			|| $isGoogleOauthEnabled
		)
	);
	
	$socialNetworkList = [
		'facebook'      => $isFacebookOauthEnabled,
		'linkedin'      => $isLinkedInOauthEnabled,
		'twitterOauth2' => $isTwitterOauth2Enabled,
		'twitterOauth1' => $isTwitterOauth1Enabled,
		'google'        => $isGoogleOauthEnabled,
	];
	
	if (!empty($socialNetwork)) {
		return (array_key_exists($socialNetwork, $socialNetworkList) && $socialNetworkList[$socialNetwork]);
	}
	
	return $isSocialAuthEnabled;
}

/**
 * @param string|null $socialNetwork
 * @param array $settings
 * @return bool
 */
function isSocialSharesEnabled(?string $socialNetwork = null, array $settings = []): bool
{
	if (empty($settings)) {
		$settings = config('settings.social_share');
		if (!is_array($settings)) return false;
	}
	
	$isFacebookEnabled = (data_get($settings, 'facebook'));
	$isTwitterEnabled = (data_get($settings, 'twitter'));
	$isLinkedInEnabled = (data_get($settings, 'linkedin'));
	$isWhatsappEnabled = (data_get($settings, 'whatsapp'));
	$isTelegramEnabled = (data_get($settings, 'telegram'));
	$isSnapchatEnabled = (data_get($settings, 'snapchat'));
	$isMessengerEnabled = (data_get($settings, 'messenger') && data_get($settings, 'facebook_app_id'));
	$isPinterestEnabled = (data_get($settings, 'pinterest'));
	$isVkEnabled = (data_get($settings, 'vk'));
	$isTumblrEnabled = (data_get($settings, 'tumblr'));
	
	$isSocialSharesEnabled = (
		$isFacebookEnabled
		|| $isTwitterEnabled
		|| $isLinkedInEnabled
		|| $isWhatsappEnabled
		|| $isTelegramEnabled
		|| $isSnapchatEnabled
		|| $isMessengerEnabled
		|| $isPinterestEnabled
		|| $isVkEnabled
		|| $isTumblrEnabled
	);
	
	$socialNetworkList = [
		'facebook'  => $isFacebookEnabled,
		'twitter'   => $isTwitterEnabled,
		'linkedin'  => $isLinkedInEnabled,
		'whatsapp'  => $isWhatsappEnabled,
		'telegram'  => $isTelegramEnabled,
		'snapchat'  => $isSnapchatEnabled,
		'messenger' => $isMessengerEnabled,
		'pinterest' => $isPinterestEnabled,
		'vk'        => $isVkEnabled,
		'tumblr'    => $isTumblrEnabled,
	];
	
	if (!empty($socialNetwork)) {
		return (array_key_exists($socialNetwork, $socialNetworkList) && $socialNetworkList[$socialNetwork]);
	}
	
	return $isSocialSharesEnabled;
}

/**
 * Get Form Border Radius CSS
 *
 * @param $formBorderRadius
 * @param $fieldsBorderRadius
 * @return string
 */
function getFormBorderRadiusCSS($formBorderRadius, $fieldsBorderRadius): string
{
	$searchFormOptions['form_border_radius'] = $formBorderRadius . 'px';
	$searchFormOptions['fields_border_radius'] = $fieldsBorderRadius . 'px';
	
	$out = "\n";
	if (config('lang.direction') == 'rtl') {
		$out .= '#homepage .search-row .search-col:first-child > div {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col:first-child .form-control {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col button {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col .btn {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
	} else {
		$out .= '#homepage .search-row .search-col:first-child > div {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col:first-child .form-control {' . "\n";
		$out .= 'border-top-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-left-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col button {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
		$out .= '#homepage .search-row .search-col .btn {' . "\n";
		$out .= 'border-top-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= 'border-bottom-right-radius: ' . $searchFormOptions['fields_border_radius'] . ' !important;' . "\n";
		$out .= '}' . "\n";
	}
	
	$out .= '@media (max-width: 767px) {' . "\n";
	$out .= '#homepage .search-row .search-col:first-child > div,' . "\n";
	$out .= '#homepage .search-row .search-col:first-child .form-control,' . "\n";
	$out .= '#homepage .search-row .search-col > div,' . "\n";
	$out .= '#homepage .search-row .search-col .form-control,' . "\n";
	$out .= '#homepage .search-row .search-col button,' . "\n";
	$out .= '#homepage .search-row .search-col .btn {' . "\n";
	$out .= 'border-radius: ' . $searchFormOptions['form_border_radius'] . ' !important;' . "\n";
	$out .= '}' . "\n";
	$out .= '}' . "\n";
	
	return $out;
}

/**
 * Get the user's possible subscription features
 *
 * @param $user
 * @param string|null $feature
 * @return int|int[]|null
 */
function getUserSubscriptionFeatures($user, ?string $feature = null): array|int|null
{
	$array = [
		'postsLimit'     => null,
		'picturesLimit'  => null,
		'expirationTime' => null,
	];
	
	if (empty($user)) {
		return empty($feature) ? $array : ($array[$feature] ?? null);
	}
	
	/*
	 * With the 120 seconds of caching, we have to:
	 * - Accept that the current payment will expire 2 minutes later than expected.
	 * - Make sure that a new payment cannot be make in 2 minutes.
	 */
	$seconds = 120;
	$cacheParams = [
		'action' => 'load.payment',
		'with'   => 'package',
	];
	$user = caching()->remember(User::class, $cacheParams, function () use ($user) {
		/*
		 * Important:
		 * The basic packages can be saved as paid in the "payments" table by the OfflinePayment plugin
		 * So, don't apply the fake basic features, so we have to exclude packages whose price is 0.
		 */
		$isNotBasic = fn ($q) => $q->where('price', '>', 0);
		$user->loadMissing(['payment' => fn ($q) => $q->withWhereHas('package', $isNotBasic)]);
		
		return $user;
	}, $seconds);
	
	if (!empty($user->payment) && !empty($user->payment->package)) {
		$basicPostsLimit = config('settings.listing_form.listings_limit', 5);
		$basicPicturesLimit = config('settings.listing_form.pictures_limit', 5);
		$basicExpirationTime = config('settings.cron.activated_listings_expiration', 30);
		
		$postsLimit = $user->payment->package->listings_limit ?? $basicPostsLimit;
		$picturesLimit = $user->payment->package->pictures_limit ?? $basicPicturesLimit;
		$expirationTime = $user->payment->package->expiration_time ?? $basicExpirationTime;
		
		$postsLimit = ($postsLimit > 0) ? $postsLimit : $basicPostsLimit;
		$picturesLimit = ($picturesLimit > 0) ? $picturesLimit : $basicPicturesLimit;
		$expirationTime = ($expirationTime > 0) ? $expirationTime : $basicExpirationTime;
		
		$array['postsLimit'] = $postsLimit;
		$array['picturesLimit'] = $picturesLimit;
		$array['expirationTime'] = $expirationTime;
	}
	
	return empty($feature) ? $array : ($array[$feature] ?? null);
}

/**
 * Get possible promotion features to a listing
 *
 * @param \App\Models\Post $post
 * @param string|null $feature
 * @return int|int[]|null
 */
function getPostPromotionFeatures(Post $post, ?string $feature = null): array|int|null
{
	$array = [
		'picturesLimit'  => null,
		'expirationTime' => null,
	];
	
	/*
	 * Important:
	 * The basic packages can be saved as paid in the "payments" table by the OfflinePayment plugin
	 * So, don't apply the fake basic features, so we have to exclude packages whose price is 0.
	 */
	$isNotBasic = fn ($q) => $q->where('price', '>', 0);
	$post->loadMissing(['payment' => fn ($q) => $q->withWhereHas('package', $isNotBasic)]);
	
	if (!empty($post->payment) && !empty($post->payment->package)) {
		$basicPicturesLimit = config('settings.listing_form.pictures_limit', 5);
		$basicExpirationTime = config('settings.cron.activated_listings_expiration', 30);
		
		$picturesLimit = $post->payment->package->pictures_limit ?? $basicPicturesLimit;
		$expirationTime = $post->payment->package->expiration_time ?? $basicExpirationTime;
		
		$picturesLimit = ($picturesLimit > 0) ? $picturesLimit : $basicPicturesLimit;
		$expirationTime = ($expirationTime > 0) ? $expirationTime : $basicExpirationTime;
		
		$array['picturesLimit'] = $picturesLimit;
		$array['expirationTime'] = $expirationTime;
	}
	
	return empty($feature) ? $array : ($array[$feature] ?? null);
}

/**
 * Get package ID request through POST method (package_id) or GET method (packageId)
 *
 * @return int|null
 */
function requestPackageId(): ?int
{
	$packageId = null;
	
	if (request()->filled('package_id')) {
		$packageId = request()->input('package_id');
	}
	
	if (empty($packageId)) {
		if (request()->filled('packageId')) {
			$packageId = request()->query('packageId');
		}
	}
	
	if (empty($packageId)) {
		$packageId = (int)old('package_id');
		if (!empty($packageId)) {
			if (!request()->has('package_id')) {
				request()->request->add(['package_id' => $packageId]);
			}
			
			return $packageId;
		}
	}
	
	return (int)$packageId;
}

/**
 * Get package by ID
 *
 * @param $packageId
 * @return \App\Models\Package|null
 */
function getPackageById($packageId): ?Package
{
	$cacheParams = [
		'action' => 'get.package',
		'id'     => $packageId,
		'with'   => 'currency',
		'locale' => app()->getLocale(),
	];
	
	return caching()->remember(Package::class, $cacheParams, function () use ($packageId) {
		return Package::with(['currency'])->where('id', $packageId)->first();
	});
}

/**
 * Check if no package is selected or if a premium one is selected from pricing page
 *
 * @param \App\Models\Package|array|null $package
 * @return bool
 */
function doesNoPackageOrPremiumOneSelected(Package|array|null $package = null): bool
{
	if (empty($package)) {
		$packageId = request()->query('packageId');
		$package = !empty($packageId) ? getPackageById($packageId) : null;
	}
	
	$packagePrice = !empty($package) ? data_get($package, 'price') : null;
	
	return (is_null($packagePrice) || (is_numeric($packagePrice) && $packagePrice > 0));
}

/**
 * Get the package type relating to the current request
 *
 * @return string|null
 */
function getRequestPackageType(): ?string
{
	$isPromoting = isFromApi()
		? routeActionHas(PostController::class)
		: (
			routeActionHas(PostController::class)
			|| routeActionHas(getClassNamespaceName(ReportController::class))
		);
	
	$isSubscripting = isFromApi()
		? routeActionHas(UserController::class)
		: (
			routeActionHas(UserController::class)
			|| routeActionHas(getClassNamespaceName(RegisterController::class))
			|| routeActionHas(getClassNamespaceName(AccountBaseController::class))
		);
	
	$type = null;
	if ($isPromoting) {
		$type = 'promotion';
	}
	if ($isSubscripting) {
		$type = 'subscription';
	}
	
	return $type;
}

/**
 * Get the display mode (key => value) list
 *
 * @return array
 */
function getDisplayModeList(): array
{
	return [
		'list'    => 'list-view',
		'compact' => 'compact-view',
		'grid'    => 'grid-view',
	];
}

/**
 * Get a display mode by key
 *
 * @param string|null $key
 * @return string|null
 */
function getDisplayMode(?string $key): ?string
{
	return getDisplayModeList()[$key] ?? null;
}

/**
 * @param string|null $key
 * @return bool
 */
function isValidDisplayModeKey(?string $key): bool
{
	$modes = getDisplayModeList();
	
	return (!empty($key) && !empty($modes[$key]));
}

/**
 * @param string|null $mode
 * @return bool
 */
function isValidDisplayMode(?string $mode): bool
{
	$flipped = array_flip(getDisplayModeList());
	
	return (!empty($mode) && in_array($mode, array_keys($flipped)));
}

/**
 * Country table's 'admin_type' enum column possible values
 *
 * @return array
 */
function enumCountryAdminTypes(): array
{
	return [
		'0' => trans('admin.none'),
		'1' => trans('admin.admin_division1'),
		'2' => trans('admin.admin_division2'),
	];
}

function getCountryFlagShapes(): array
{
	return [
		'rectangle' => 'Rectangle Flags', // Default
		'circle'    => 'Circle Flags',
		'hexagon'   => 'Hexagon Flags',
	];
}

/**
 * @param string|null $countryCode
 * @param int|null $size
 * @return string|null
 */
function getCountryFlagUrl(?string $countryCode, ?int $size = 16): ?string
{
	if (empty($countryCode)) return null;
	$size = !empty($size) ? $size : 16;
	
	$flagUrl = null;
	
	$shape = config('settings.localization.country_flag_shape', 'rectangle');
	if ($shape == 'rectangle') {
		$missingIslandFlags = [
			'BQ' => 'NL', // Bonaire, Sint Eustatius, and Saba (Kingdom of the Netherlands)
			'BV' => 'NO', // Bouvet Island (Norway)
			'GF' => 'FR', // French Guiana (France)
			'GP' => 'FR', // Guadeloupe (France)
			'PM' => 'FR', // Saint Pierre and Miquelon (France)
			'RE' => 'FR', // Réunion (France)
			'SX' => 'NL', // Sint Maarten (Kingdom of the Netherlands)
		];
	} else {
		$missingIslandFlags = [
			'BV' => 'NO', // Bouvet Island (Norway)
			'CW' => 'NL', // Curaçao (Kingdom of the Netherlands)
			'AN' => 'NL', // Netherlands Antilles (Kingdom of the Netherlands)
			'FM' => 'US', // Federated States of Micronesia (US | UN)
			'CC' => 'AU', // Cocos (Keeling) Islands (Australia)
			'AX' => 'FI', // Åland Islands (Finland)
			'IM' => 'GB', // Isle of Man (United Kingdom)
		];
	}
	$code = $missingIslandFlags[$countryCode] ?? $countryCode;
	
	$flagPath = 'images/flags/' . $shape . '/' . $size . '/' . strtolower($code) . '.png';
	if (file_exists(public_path($flagPath))) {
		$flagUrl = url($flagPath) . getPictureVersion();
	}
	
	return castToStringOrNull($flagUrl);
}

/**
 * Get the number of items per page
 *
 * @param string|null $entity
 * @param null $perPage
 * @param int|null $default
 * @return float|int
 */
function getNumberOfItemsPerPage(?string $entity = null, $perPage = null, ?int $default = null): float|int
{
	$entity = castToString($entity);
	
	$isPerPageValid = (is_int($perPage) && $perPage > 0);
	if (!$isPerPageValid) {
		$defaultIntValue = 10;
		$default = (ctype_digit($default) || is_int($default)) ? (int)$default : null;
		$default = (is_int($default) && $default > 0) ? $default : $defaultIntValue;
		
		$perPage = config('settings.pagination.per_page', $default);
		$perPage = config('settings.pagination.' . $entity . '_per_page', $perPage);
		$perPage = (ctype_digit($perPage) || is_int($perPage)) ? (int)$perPage : $default;
		
		$perPage = (is_int($perPage) && $perPage > 0)
			? $perPage
			: $default;
	}
	
	return Number::clamp($perPage, min: 1, max: getMaxItemsPerPage($entity));
}

/**
 * Get the maximum items per page
 *
 * @param string|null $entity
 * @return float|int
 */
function getMaxItemsPerPage(?string $entity = null): float|int
{
	$defaultMaxItemsPerPage = ($entity == 'posts') ? 100 : 500;
	$maxItemsPerPage = ($entity == 'posts')
		? config('larapen.core.maxItemsPerPage.listings', $defaultMaxItemsPerPage)
		: config('larapen.core.maxItemsPerPage.global', $defaultMaxItemsPerPage);
	$maxItemsPerPage = (is_numeric($maxItemsPerPage) && $maxItemsPerPage >= $defaultMaxItemsPerPage)
		? $maxItemsPerPage
		: $defaultMaxItemsPerPage;
	
	$maxItemsPerPage = ($entity == 'subadmin1_select') ? 200 : $maxItemsPerPage;
	$maxItemsPerPage = ($entity == 'subadmin2_select') ? 5000 : $maxItemsPerPage;
	
	return Number::clamp($maxItemsPerPage, min: $maxItemsPerPage, max: 100000);
}

/**
 * Get the number of items to take
 *
 * @param string|null $entity
 * @return float|int
 */
function getNumberOfItemsToTake(?string $entity = null): float|int
{
	$limit = config('settings.pagination.' . $entity . '_limit');
	
	return getNumberOfItemsPerPage($entity, $limit);
}

/**
 * @return bool
 */
function isSubscriptionAvailable(): bool
{
	return (bool)version_compare(getCurrentVersion(), '14.0.0', '>=');
}

/**
 * Get listing's labeled dates
 *
 * @param $post
 * @param string|null $status
 * @return array
 */
function getListingDates($post, ?string $status): array
{
	$dates = [];
	
	if (empty($status)) return $dates;
	
	if (in_array($status, ['list', 'pending-approval'])) {
		$createdAtFormatted = data_get($post, 'created_at_formatted');
		$updatedAtFormatted = data_get($post, 'updated_at_formatted');
		
		if (!empty($updatedAtFormatted)) {
			if ($createdAtFormatted != $updatedAtFormatted) {
				$dates[t('updated_on')] = t('updated_on') . ': ' . $updatedAtFormatted;
			}
		}
		
		if (!empty($createdAtFormatted)) {
			$dates[t('created_on')] = t('created_on') . ': ' . $createdAtFormatted;
		}
	}
	
	if ($status == 'archived') {
		$createdAtFormatted = data_get($post, 'created_at_formatted');
		$archivedManuallyAtFormatted = data_get($post, 'archived_manually_at_formatted');
		$archivedAtFormatted = data_get($post, 'archived_at_formatted');
		
		if (!empty($archivedManuallyAtFormatted)) {
			$dates[t('archived_manually_on')] = t('archived_manually_on')
				. ': ' . $archivedManuallyAtFormatted;
		} else {
			if (!empty($archivedAtFormatted)) {
				$dates[t('archived_on')] = t('archived_on') . ': ' . $archivedAtFormatted;
			}
		}
		
		if (!empty($createdAtFormatted)) {
			$dates[t('created_on')] = t('created_on') . ': ' . $createdAtFormatted;
		}
	}
	
	if ($status == 'favourite') {
		$publishedAtFormatted = data_get($post, 'created_at_formatted');
		$savedAtFormatted = data_get($post, 'saved_at_formatted');
		
		if (!empty($savedAtFormatted)) {
			$dates[t('saved_on')] = t('saved_on') . ': ' . $savedAtFormatted;
		}
		
		if (!empty($publishedAtFormatted)) {
			$dates[t('published_on')] = t('published_on') . ': ' . $publishedAtFormatted;
		}
	}
	
	return $dates;
}

/**
 * Check if the "Multiple-steps form" option is enabled
 *
 * @return bool
 */
function isMultipleStepsFormEnabled(): bool
{
	return (config('settings.listing_form.publication_form_type') == 'multi-steps-form');
}

/**
 * Check if the "Single-step form" option is enabled
 *
 * @return bool
 */
function isSingleStepFormEnabled(): bool
{
	return (config('settings.listing_form.publication_form_type') == 'single-step-form');
}

/**
 * Check if the "Multi-countries URLs" option is enabled
 *
 * @return bool
 */
function isMultiCountriesUrlsEnabled(): bool
{
	return (config('settings.seo.multi_country_urls') == '1');
}

/**
 * Check if file has temporary path
 * Note: Temporary files are stored in: "/storage/app/public/temporary/" directory
 *
 * @param string $filePath
 * @return bool
 */
function hasTemporaryPath(string $filePath): bool
{
	return str_starts_with($filePath, 'temporary' . DIRECTORY_SEPARATOR);
}

/**
 * Get the item's address for Google Maps
 *
 * @param $city
 * @return string|null
 */
function getItemAddressForMap($city): ?string
{
	$admin1 = data_get($city, 'subAdmin1');
	
	$countryName = config('country.name');
	$admin1Name = data_get($admin1, 'name');
	$cityName = data_get($city, 'name');
	
	$cityName = !empty($admin1Name)
		? (!empty($cityName) ? $cityName . ',' . $admin1Name : $admin1Name)
		: $cityName;
	
	$address = !empty($cityName) ? $cityName . ',' . $countryName : $countryName;
	
	return castToStringOrNull($address);
}

/**
 * Get Google Maps API JavaScript's URL
 *
 * https://developers.google.com/maps/documentation/geocoding/overview
 * https://developers.google.com/maps/documentation/geocoding/requests-geocoding
 * https://developers.google.com/maps/documentation/javascript/versions
 *
 * @param string|null $apiKey
 * @param bool $useAsyncGeocoding
 * @param bool $decode
 * @return string|null
 */
function getGoogleMapsApiUrl(
	?string $apiKey,
	bool    $useAsyncGeocoding = false,
	bool    $decode = true
): ?string
{
	if (empty($apiKey)) return null;
	
	$baseUrl = 'https://maps.googleapis.com/maps/api/js';
	$query = [
		'key'      => $apiKey,
		'loading'  => 'async',
		'region'   => config('country.code'),
		'language' => getLangTag(app()->getLocale()),
		'callback' => 'initGoogleMap',
		'v'        => 'weekly',
	];
	if (!$useAsyncGeocoding) {
		$query['v'] = 'beta';
		$query['libraries'] = 'marker';
	}
	
	$url = $baseUrl . '?' . Arr::query($query);
	
	return $decode ? html_entity_decode($url) : $url;
}

/**
 * Get Google Maps Embed URL
 * https://developers.google.com/maps/documentation/embed/get-started
 * https://developers.google.com/maps/documentation/embed/embedding-map
 *
 * Note: The Embed URL is called from an iframe
 *
 * @param string|null $apiKey
 * @param string|null $q
 * @param bool $decode
 * @return string
 */
function getGoogleMapsEmbedApiUrl(?string $apiKey, ?string $q, bool $decode = true): string
{
	$baseUrl = 'https://www.google.com/maps/embed/v1/place';
	$query = [
		'key'      => $apiKey,
		'q'        => $q,
		'zoom'     => 9,         // Values ranging from 0 (the whole world) to 21 (individual buildings)
		'maptype'  => 'roadmap', // roadmap (default) or satellite
		'region'   => config('country.code'),
		'language' => getLangTag(app()->getLocale()),
	];
	$url = $baseUrl . '?' . Arr::query($query);
	
	return $decode ? html_entity_decode($url) : $url;
}

/**
 * Check if the user account closure option is enabled
 *
 * @return bool
 */
function isAccountClosureEnabled(): bool
{
	return (config('settings.other.account_closure_enabled') == '1');
}

/**
 * Get selected (checked) entry ID list
 *
 * @param $entryId
 * @param array|string|null $entryIdList
 * @param bool $asString
 * @return array|string
 */
function getSelectedEntryIds($entryId = null, array|string|null $entryIdList = [], bool $asString = false): array|string
{
	$array = [];
	if (!empty($entryIdList)) {
		if (isStringableStrict($entryIdList)) {
			$entryIdList = explode(',', $entryIdList);
		}
		$array = is_array($entryIdList) ? $entryIdList : [];
	} else {
		if (!empty($entryId)) {
			if (isStringableStrict($entryId)) {
				$array[] = (string)$entryId;
			}
			if (is_array($entryId)) {
				$array = $entryId;
			}
		}
	}
	
	return $asString ? implode(',', $array) : $array;
}

/**
 * @param string|null $color
 * @return string
 */
function linkClass(?string $color = 'primary'): string
{
	$colorClass = BootstrapColor::Link->getColorClass($color);
	$altBaseClass = BootstrapColor::Link->altBase();
	
	return "$colorClass $altBaseClass";
}

/**
 * @return string
 */
function unsavedFormGuard(): string
{
	$isEnabled = (config('settings.security.unsaved_form_guard') == '1');
	
	return $isEnabled ? 'unsaved-guard' : '';
}

/**
 * Get the search results page offcanvas breakpoint data
 *
 * @param string|null $breakpointKey
 * @param bool $isLeftSidebarEnabled
 * @return array
 */
function getSerpOffcanvasBreakpoint(?string $breakpointKey = null, bool $isLeftSidebarEnabled = true): array
{
	$pageBreakpointList = [
		'md'  => [
			'label'                   => trans('admin.on_small_screen'),
			'leftColSize'             => $isLeftSidebarEnabled ? 'col-md-3' : '',
			'rightColSize'            => $isLeftSidebarEnabled ? 'col-md-9' : 'col-md-12',
			'showInlineOnSmallScreen' => ' d-inline-block d-md-none', // Hide on large screens
			'showOnLargeScreen'       => ' d-none d-md-block',        // Hide on small screens
			'size'                    => 768,
		],
		'lg'  => [
			'label'                   => trans('admin.on_medium_screen'),
			'leftColSize'             => $isLeftSidebarEnabled ? 'col-lg-3' : '',
			'rightColSize'            => $isLeftSidebarEnabled ? 'col-lg-9' : 'col-lg-12',
			'showInlineOnSmallScreen' => ' d-inline-block d-lg-none', // Hide on large screens
			'showOnLargeScreen'       => ' d-none d-lg-block',        // Hide on small screens
			'size'                    => 992,
		],
		'xl'  => [
			'label'                   => trans('admin.on_large_screen'),
			'leftColSize'             => $isLeftSidebarEnabled ? 'col-xl-3' : '',
			'rightColSize'            => $isLeftSidebarEnabled ? 'col-xl-9' : 'col-xl-12',
			'showInlineOnSmallScreen' => ' d-inline-block d-xl-none', // Hide on large screens
			'showOnLargeScreen'       => ' d-none d-xl-block',        // Hide on small screens
			'size'                    => 1200,
		],
		'xxl' => [
			'label'                   => trans('admin.on_extra_large_screen'),
			'leftColSize'             => $isLeftSidebarEnabled ? 'col-xxl-3' : '',
			'rightColSize'            => $isLeftSidebarEnabled ? 'col-xxl-9' : 'col-xxl-12',
			'showInlineOnSmallScreen' => ' d-inline-block d-xxl-none', // Hide on large screens
			'showOnLargeScreen'       => ' d-none d-xxl-block',        // Hide on small screens
			'size'                    => 1400,
		],
		'all' => [
			'label'                   => trans('admin.on_all_sizes_screen'),
			'leftColSize'             => '',
			'rightColSize'            => 'col-12',
			'showInlineOnSmallScreen' => ' d-inline-block', // Hide on large screens
			'showOnLargeScreen'       => ' d-block',        // Hide on small screens
			'size'                    => 7680,              // 8K:7680 | 6K:6144 | 4K:4096
		],
	];
	
	if (empty($breakpointKey)) {
		return collect($pageBreakpointList)
			->map(fn ($item, $key) => $item['label'] ?? $key)
			->toArray();
	}
	
	return $pageBreakpointList[$breakpointKey] ?? [];
}

/**
 * @param string|null $id
 * @param string|null $name
 * @param string|null $prefix
 * @param string $separator
 * @return string
 */
function getFieldIdentifier(?string $id = null, ?string $name = null, ?string $prefix = null, string $separator = '-'): string
{
	$dotSepName = arrayFieldToDotNotation($name);
	$fallbackId = str_replace('.', $separator, $dotSepName);
	
	$id = !empty($id) ? $id : $fallbackId;
	$id = str_replace(['_', '-', ' '], $separator, $id);
	
	$prefix = (!empty($prefix) && !str_ends_with($prefix, $separator)) ? "{$prefix}{$separator}" : '';
	
	return "{$prefix}{$id}";
}

/**
 * @return int
 */
function getGlobalCacheTtl(): int
{
	$fallback = 3600; // 3600 (1h) 86400 (24h)
	
	return (int)config('settings.optimization.cache_expiration', $fallback);
}

/**
 * @return int
 */
function getSchemaCacheTtl(): int
{
	return 86400 * 365; // 24h x 365
}
