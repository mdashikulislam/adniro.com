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

trait SectionTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function resetHomepageReOrderTopButton(?Panel $xPanel = null): string
	{
		$url = urlGen()->adminUrl("sections/reset/all/reorder");
		
		$msg = trans('admin.Reset the homepage sections reorder');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-warning text-white shadow mb-1" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa-solid fa-arrow-up-wide-short"></i> ';
		$out .= trans('admin.Reset sections reorganization');
		$out .= '</a>';
		
		return $out;
	}
	
	public function resetHomepageSettingsTopButton(?Panel $xPanel = null): string
	{
		$url = urlGen()->adminUrl("sections/reset/all/options");
		
		$msg = trans('admin.Reset all the homepage settings');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-danger shadow mb-1 confirm-simple-action" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa-solid fa-industry"></i> ';
		$out .= trans('admin.Return to factory settings');
		$out .= '</a>';
		
		return $out;
	}
	
	public function crudLabelColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = $xPanel->getUrl($this->getKey() . '/edit');
		
		return '<a href="' . $url . '">' . $this->label . '</a>';
	}
	
	public function configureInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$url = $xPanel->getUrl($this->getKey() . '/edit');
		
		$msg = trans('admin.configure_entity', ['entity' => $this->label]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		$out = '<a class="btn btn-xs btn-primary" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa-solid fa-gear"></i> ';
		$out .= mb_ucfirst(trans('admin.Configure'));
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	public static function optionsThatNeedToBeHidden(): array
	{
		return [];
	}
}
