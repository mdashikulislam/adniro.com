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

use App\Http\Controllers\Web\Admin\Panel\Library\Panel;

trait SubAdmin1Trait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function crudNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = $xPanel->getUrl($this->code . '/edit');
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function adminDivisions2InLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$url = $xPanel->getUrl($this->code . '/admins2');
		
		$msg = trans('admin.Admin Divisions 2 of admin1', ['admin_division1' => $this->name]);
		$toolTip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $toolTip . '>';
		$out .= '<i class="fa-regular fa-eye"></i> ';
		$out .= mb_ucfirst(trans('admin.admin divisions 2'));
		$out .= '</a>';
		
		return $out;
	}
	
	public function citiesInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$url = $xPanel->getUrl($this->code . '/cities');
		
		$msg = trans('admin.Cities of admin1', ['admin_division1' => $this->name]);
		$toolTip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $toolTip . '>';
		$out .= '<i class="fa-regular fa-eye"></i> ';
		$out .= mb_ucfirst(trans('admin.cities'));
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
