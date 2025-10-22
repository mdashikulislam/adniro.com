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

/**
 * @param bool $excludeNonRegionalLang
 * @param bool $localeInName
 * @return array
 */
function getRegionalLanguageRefList(bool $excludeNonRegionalLang = false, bool $localeInName = true): array
{
	// Get locales with name
	$localeLanguages = getLocaleRefList();
	$localeLanguages = collect($localeLanguages)->reject(fn ($name, $code) => str_contains($code, '.'));
	if ($excludeNonRegionalLang) {
		$localeLanguages = $localeLanguages->reject(fn ($name, $code) => !isRegionalLocaleCode($code));
	}
	$localeLanguages = $localeLanguages->toArray();
	
	// Get languages by their main country
	$isoLanguageCountries = getLanguagesLinkedToTheirMainCountry();
	
	// Get the localized version of a locale, if it exists
	// This is done by linking the locale to its main country
	$updateLocaleCode = function ($name, $code) use ($isoLanguageCountries) {
		$locale = $code;
		if (!empty($isoLanguageCountries[$code]['locale'])) {
			$locale = $isoLanguageCountries[$code]['locale'];
		}
		
		return [$locale => $name];
	};
	
	// Get languages
	$isoLanguages = getLanguageRefList();
	$isoLanguages = collect($isoLanguages)->mapWithKeys($updateLocaleCode)->toArray();
	
	$array = array_merge($localeLanguages, $isoLanguages);
	$collection = collect($array);
	
	// Remove non-localized locales whose one
	// of their localized version is available
	$hasLocalizedLocale = function ($name, $code) use ($array) {
		if (isRegionalLocaleCode($code)) {
			return false;
		}
		$codeFound = collect($array)->first(function ($item, $key) use ($code) {
			return (str_starts_with($key, $code . '_'));
		});
		
		return !empty($codeFound);
	};
	
	if ($excludeNonRegionalLang) {
		$collection = $collection->reject($hasLocalizedLocale);
	}
	if ($localeInName) {
		$collection = $collection->map(fn ($name, $code) => $name . ' - ' . $code);
	}
	
	return $collection->sort()->toArray();
}

/**
 * @return array
 */
function getLanguageRefList(): array
{
	return getCachedReferrerList('languages');
}

/**
 * Get all locales
 *
 * - installed: locale list from the server with the "locale -a" command
 * - referrer: locale list from array
 * - merged: merge of locale list from "referrer" and "installed"
 * - null: locale list from "installed", if this cannot be got, then get list from "referrer"
 *
 * @param string|null $from
 * @param bool $includeNonLocales
 * @return array
 */
function getLocales(?string $from = null, bool $includeNonLocales = false): array
{
	$from = in_array($from, ['referrer', 'installed', 'merged']) ? $from : null;
	$isFromReferrer = (empty($from) || $from == 'referrer');
	$isFromInstalled = (empty($from) || $from == 'installed');
	$isFromMerged = ($from == 'merged');
	
	$locales = [];
	
	// Get available|installed locales from the server
	if ($isFromInstalled || $isFromMerged) {
		try {
			exec('locale -a', $locales);
		} catch (Throwable $e) {
		}
	}
	
	// Get locales from config (referrer)
	if ($isFromMerged) {
		$localeRefList = array_keys(getLocaleRefList());
		$locales = array_merge($locales, $localeRefList);
	} else {
		if ($isFromReferrer && empty($locales)) {
			$locales = array_keys(getLocaleRefList());
		}
	}
	
	// Non locale entry list
	$nonLocales = ['c', 'posix'];
	
	return collect($locales)
		->reject(fn ($code) => !$includeNonLocales && in_array(strtolower($code), $nonLocales))
		->toArray();
}

/**
 * @param string|null $from
 * @param bool $localeInName
 * @param bool $includeNonLocales
 * @return array
 */
function getLocalesWithName(?string $from = null, bool $localeInName = true, bool $includeNonLocales = false): array
{
	$locales = getLocales($from, $includeNonLocales);
	$localesWithName = getLocaleRefList();
	
	$locales = collect($locales)
		->mapWithKeys(function ($sysCode) use ($localesWithName) {
			$name = collect($localesWithName)
				->first(function ($name, $code) use ($sysCode) {
					return (str_starts_with($sysCode, $code . '.') || $code == $sysCode);
				});
			
			// \Locale is available in the PHP intl Extension
			$nameFromSystem = $sysCode;
			if (extension_loaded('intl') && class_exists('\Locale')) {
				$nameFromSystem = \Locale::getDisplayName($sysCode);
				$nameFromSystem = !empty($nameFromSystem) ? $nameFromSystem : $sysCode;
			}
			
			return [$sysCode => !empty($name) ? $name : $nameFromSystem];
		});
	
	if ($localeInName) {
		$locales = $locales->map(fn ($name, $code) => $code . ' | ' . $name);
	}
	
	$locales = $locales->sort();
	
	return $locales->toArray();
}

/**
 * @return array
 */
function getCurrencySymbolRefList(): array
{
	return getCachedReferrerList('currency-symbols');
}

/**
 * Get languages linked to their main country
 *
 * @return array
 */
function getLanguagesLinkedToTheirMainCountry(): array
{
	$array = getCachedReferrerList('language-countries');
	
	// Append locale in item
	$locales = getLocaleRefList();
	$appendLocale = function ($item, $code) use ($locales) {
		$locale = $code;
		if (!empty($item['country'])) {
			$locale = $code . '_' . $item['country'];
			$locale = isset($locales[$locale]) ? $locale : $code;
		}
		$item['locale'] = $locale;
		
		return $item;
	};
	
	return collect($array)->map($appendLocale)->toArray();
}

/**
 * @return array
 */
function getLanguageScriptRefList(): array
{
	$array = getCachedReferrerList('language-scripts');
	
	return collect($array)
		->sort()
		->map(fn ($name, $code) => $name . ' - ' . $code)
		->toArray();
}

/**
 * @return array
 */
function getLocaleRefList(): array
{
	return getCachedReferrerList('language-locales');
}

/**
 * @return array
 */
function getReservedUsernameRefList(): array
{
	return getCachedReferrerList('reserved-usernames');
}

/**
 * @return array
 */
function getTimeZoneRefList(): array
{
	return getCachedReferrerList('time-zones');
}

/**
 * @return array
 */
function getTopLevelDomainRefList(): array
{
	return getCachedReferrerList('tlds');
}

/**
 * Retrieves a referrer list by key
 *
 * @param string $referrer
 * @return array
 */
function getReferrerList(string $referrer): array
{
	$array = [];
	try {
		$path = __DIR__ . '/referrer/' . $referrer . '.php';
		if (file_exists($path)) {
			$array = (array)include realpath($path);
		}
	} catch (Throwable $e) {
	}
	
	return $array;
}

/**
 * Retrieves a referrer list by key, with caching to avoid repeated file loading
 *
 * @param string $referrer Identifier for the referrer list (e.g., 'bootstrap/colors')
 * @return array The referrer array, or empty array if not found or invalid
 */
function getCachedReferrerList(string $referrer): array
{
	static $cache = [];
	
	// Return cached result if available
	if (isset($cache[$referrer])) {
		return $cache[$referrer];
	}
	
	// Load array using getReferrerList and cache the result
	$array = getReferrerList($referrer);
	$cache[$referrer] = $array;
	
	return $array;
}

/**
 * Retrieves the umpirsky country list
 *
 * @return array
 */
function getCountryRefList(): array
{
	$array = [];
	try {
		$path = __DIR__ . '/../../../database/umpirsky/country/en/country.php';
		if (file_exists($path)) {
			$array = (array)include realpath($path);
		}
	} catch (Throwable $e) {
	}
	
	return $array;
}

/**
 * Retrieves the umpirsky country list, with caching to avoid repeated file loading
 *
 * @return array
 */
function getCachedCountryRefList(): array
{
	static $cache = [];
	
	$cacheId = 'umpirsky-country';
	
	// Return cached result if available
	if (isset($cache[$cacheId])) {
		return $cache[$cacheId];
	}
	
	// Load array using getCountryRefList and cache the result
	$array = getCountryRefList();
	$cache[$cacheId] = $array;
	
	return $array;
}

/**
 * @return array
 */
function getDemoEmailAddresses(): array
{
	return [
		'admin@domain.tld',
		'company@domain.tld',
		'user@domain.tld',
		'mayeul@domain.tld',
		'admin@demosite.com',
		'company@demosite.com',
		'user@demosite.com',
		'mayeul@demosite.com',
	];
}
