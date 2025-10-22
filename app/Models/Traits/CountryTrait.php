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

namespace App\Models\Traits;

use App\Helpers\Common\JsonUtils;
use App\Helpers\Services\Localization\Helpers\Country as CountryHelper;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Models\Language;
use Illuminate\Support\Facades\DB;

trait CountryTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function crudNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = $xPanel->getUrl($this->getKey() . '/edit');
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function crudActiveColumn(?Panel $xPanel = null, array $column = []): string
	{
		if (!isset($this->active)) return '';
		
		return installAjaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'active', $this->active);
	}
	
	public function adminDivisions1InLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		// $url = urlGen()->adminUrl("countries/{$this->getKey()}/admins1");
		$url = $xPanel->getUrl($this->getKey() . '/admins1');
		
		$msg = trans('admin.Admin Divisions 1 of country', ['country' => $this->name]);
		$toolTip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $toolTip . '>';
		$out .= '<i class="fa-regular fa-eye"></i> ';
		$out .= mb_ucfirst(trans('admin.admin divisions 1'));
		$out .= '</a>';
		
		return $out;
	}
	
	public function adminDivisions2InLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		// $url = urlGen()->adminUrl("countries/{$this->getKey()}/admins2");
		$url = $xPanel->getUrl($this->getKey() . '/admins2');
		
		$msg = trans('admin.Admin Divisions 2 of country', ['country' => $this->name]);
		$toolTip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $toolTip . '>';
		$out .= '<i class="fa-regular fa-eye"></i> ';
		$out .= mb_ucfirst(trans('admin.admin divisions 2'));
		$out .= '</a>';
		
		return $out;
	}
	
	public function citiesInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		// $url = urlGen()->adminUrl("countries/{$this->getKey()}/cities");
		$url = $xPanel->getUrl($this->getKey() . '/cities');
		
		$msg = trans('admin.Cities of country', ['country' => $this->name]);
		$toolTip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $toolTip . '>';
		$out .= '<i class="fa-regular fa-eye"></i> ';
		$out .= mb_ucfirst(trans('admin.cities'));
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	/**
	 * Countries Batch Auto Translation
	 *
	 * @param bool $overwriteExistingTrans
	 * @return void
	 */
	public static function autoTranslation(bool $overwriteExistingTrans = false): void
	{
		$tableName = (new self())->getTable();
		
		$languages = DB::table((new Language())->getTable())->get();
		$oldEntries = DB::table($tableName)->get();
		
		if ($oldEntries->count() > 0) {
			$transCountry = new CountryHelper();
			foreach ($oldEntries as $oldEntry) {
				$newNames = [];
				
				foreach ($languages as $language) {
					if (JsonUtils::isJson($oldEntry->name)) {
						$oldNames = JsonUtils::jsonToArray($oldEntry->name);
					}
					
					$langCode = $language->code ?? ($language->abbr ?? null);
					$translationNotFound = (empty($oldNames[$langCode]));
					
					if ($overwriteExistingTrans || $translationNotFound) {
						if ($translationNotFound) {
							$newNames[$langCode] = getColumnTranslation($oldEntry->name);
						}
						if ($name = $transCountry->get($oldEntry->code, $langCode)) {
							$newNames[$langCode] = $name;
						}
					}
				}
				
				if (!empty($newNames)) {
					$name = json_encode($newNames, JSON_UNESCAPED_UNICODE);
					$affected = DB::table($tableName)
						->where('code', '=', $oldEntry->code)
						->update(['name' => $name]);
				}
			}
		}
	}
}
