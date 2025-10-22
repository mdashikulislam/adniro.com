<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$entries = [
			[
				'code'                  => 'en',
				'locale'                => $this->getUtf8Locale('en_US'),
				'name'                  => 'English',
				'native'                => 'English',
				'flag'                  => 'flag-icon-gb',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'MMM Do, YYYY',
				'datetime_format'       => 'MMM Do, YYYY [at] HH:mm',
				'default'               => '1', // Set to true (1) only for this entry
			],
			[
				'code'                  => 'fr',
				'locale'                => $this->getUtf8Locale('fr_FR'),
				'name'                  => 'French',
				'native'                => 'Français',
				'flag'                  => 'flag-icon-fr',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'Do MMM YYYY',
				'datetime_format'       => 'Do MMM YYYY [à] H[h]mm',
			],
			[
				'code'                  => 'es',
				'locale'                => $this->getUtf8Locale('es_ES'),
				'name'                  => 'Spanish',
				'native'                => 'Español',
				'flag'                  => 'flag-icon-es',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D [de] MMMM [de] YYYY',
				'datetime_format'       => 'D [de] MMMM [de] YYYY HH:mm',
			],
			[
				'code'                  => 'ar',
				'locale'                => $this->getUtf8Locale('ar_SA'),
				'name'                  => 'Arabic',
				'native'                => 'العربية',
				'flag'                  => 'flag-icon-sa',
				'script'                => 'Arab',
				'direction'             => 'rtl',
				'russian_pluralization' => '0',
				'date_format'           => 'DD/MMMM/YYYY',
				'datetime_format'       => 'DD/MMMM/YYYY HH:mm',
			],
			[
				'code'                  => 'pt',
				'locale'                => $this->getUtf8Locale('pt_PT'),
				'name'                  => 'Portuguese',
				'native'                => 'Português',
				'flag'                  => 'flag-icon-pt',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D [de] MMMM [de] YYYY',
				'datetime_format'       => 'D [de] MMMM [de] YYYY HH:mm',
			],
			[
				'code'                  => 'de',
				'locale'                => $this->getUtf8Locale('de_DE'),
				'name'                  => 'German',
				'native'                => 'Deutsch',
				'flag'                  => 'flag-icon-de',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'dddd, D. MMMM YYYY',
				'datetime_format'       => 'dddd, D. MMMM YYYY HH:mm',
			],
			[
				'code'                  => 'it',
				'locale'                => $this->getUtf8Locale('it_IT'),
				'name'                  => 'Italian',
				'native'                => 'Italiano',
				'flag'                  => 'flag-icon-it',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY HH:mm',
			],
			[
				'code'                  => 'tr',
				'locale'                => $this->getUtf8Locale('tr_TR'),
				'name'                  => 'Turkish',
				'native'                => 'Türkçe',
				'flag'                  => 'flag-icon-tr',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'DD MMMM YYYY dddd',
				'datetime_format'       => 'DD MMMM YYYY dddd HH:mm',
			],
			[
				'code'                  => 'ru',
				'locale'                => $this->getUtf8Locale('ru_RU'),
				'name'                  => 'Russian',
				'native'                => 'Русский',
				'flag'                  => 'flag-icon-ru',
				'script'                => 'Cyrl',
				'direction'             => 'ltr',
				'russian_pluralization' => '1',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY [ г.] H:mm',
			],
			[
				'code'                  => 'hi',
				'locale'                => $this->getUtf8Locale('hi_IN'),
				'name'                  => 'Hindi',
				'native'                => 'हिन्दी',
				'flag'                  => 'flag-icon-in',
				'script'                => 'Deva',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY H:mm',
			],
			[
				'code'                  => 'bn',
				'locale'                => $this->getUtf8Locale('bn_BD'),
				'name'                  => 'Bengali',
				'native'                => 'বাংলা',
				'flag'                  => 'flag-icon-bd',
				'script'                => 'Beng',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY H.mm',
			],
			[
				'code'                  => 'zh',
				'locale'                => $this->getUtf8Locale('zh_CN'),
				'name'                  => 'Simplified Chinese',
				'native'                => '简体中文',
				'flag'                  => 'flag-icon-cn',
				'script'                => 'Hans',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY H:mm',
			],
			[
				'code'                  => 'ja',
				'locale'                => $this->getUtf8Locale('ja_JP'),
				'name'                  => 'Japanese',
				'native'                => '日本語',
				'flag'                  => 'flag-icon-jp',
				'script'                => 'Jpan',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY H:mm',
			],
			[
				'code'                  => 'he',
				'locale'                => $this->getUtf8Locale('he_IL'),
				'name'                  => 'Hebrew',
				'native'                => 'עִברִית',
				'flag'                  => 'flag-icon-il',
				'script'                => 'Hebr',
				'direction'             => 'rtl',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY H:mm',
			],
			[
				'code'                  => 'th',
				'locale'                => $this->getUtf8Locale('th_TH'),
				'name'                  => 'Thai',
				'native'                => 'ไทย',
				'flag'                  => 'flag-icon-th',
				'script'                => 'Thai',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY H:mm',
			],
			[
				'code'                  => 'ro',
				'locale'                => $this->getUtf8Locale('ro_RO'),
				'name'                  => 'Romanian',
				'native'                => 'Română',
				'flag'                  => 'flag-icon-ro',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D MMMM YYYY',
				'datetime_format'       => 'D MMMM YYYY H:mm',
			],
			[
				'code'                  => 'ka',
				'locale'                => $this->getUtf8Locale('ka_GE'),
				'name'                  => 'Georgian',
				'native'                => 'ქართული',
				'flag'                  => 'flag-icon-ge',
				'script'                => 'Geor',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'YYYY [წლის] DD MM',
				'datetime_format'       => 'YYYY [წლის] DD MMMM, dddd H:mm',
			],
		];
		
		// Add or update columns
		$timezone = config('app.timezone', 'UTC');
		$entries = collect($entries)
			->map(function ($item) use ($timezone) {
				$item['default'] = $item['default'] ?? 0;
				$item['active'] = 1;
				
				$item['parent_id'] = null;
				$item['lft'] = 0;
				$item['rgt'] = 0;
				$item['depth'] = 0;
				
				$item['deleted_at'] = null;
				$item['created_at'] = now($timezone)->format('Y-m-d H:i:s');
				$item['updated_at'] = null;
				
				return $item;
			})->toArray();
		
		$tableName = (new Language())->getTable();
		
		$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
		NestedSetSeeder::insertEntries($tableName, $entries, $startPosition);
	}
	
	/**
	 * @param string $locale
	 * @return string
	 */
	private function getUtf8Locale(string $locale): string
	{
		// Limit the use of this method only for locales which often produce malfunctions
		// when they don't have their UTF-8 format. e.g. the Turkish language (tr_TR).
		$localesToFix = ['tr_TR'];
		if (!in_array($locale, $localesToFix)) {
			return $locale;
		}
		
		$localesList = getLocales('installed');
		
		// Return the given locale, if installed locales list cannot be retrieved from the server
		if (empty($localesList)) {
			return $locale;
		}
		
		// Return given locale, if the database charset is not utf-8
		$dbCharset = config('database.connections.' . config('database.default') . '.charset');
		if (!str_starts_with($dbCharset, 'utf8')) {
			return $locale;
		}
		
		$utf8LocaleFound = false;
		
		$codesetList = ['UTF-8', 'utf8'];
		foreach ($codesetList as $codeset) {
			$tmpLocale = $locale . '.' . $codeset;
			if (in_array($tmpLocale, $localesList, true)) {
				$locale = $tmpLocale;
				$utf8LocaleFound = true;
				break;
			}
		}
		
		if (!$utf8LocaleFound) {
			$codesetList = ['utf-8', 'UTF8'];
			foreach ($codesetList as $codeset) {
				$tmpLocale = $locale . '.' . $codeset;
				if (in_array($tmpLocale, $localesList, true)) {
					$locale = $tmpLocale;
					break;
				}
			}
		}
		
		return $locale;
	}
}
