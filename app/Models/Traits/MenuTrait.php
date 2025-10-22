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

trait MenuTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function resetMenusTopButton(?Panel $xPanel = null): string
	{
		// $url = $xPanel->getUrl('reset');
		$url = urlGen()->adminUrl("menus/reset");
		
		$msg = trans('menu.reset_all_menus');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-success shadow mb-1 confirm-simple-action" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa-solid fa-industry"></i> ';
		$out .= trans('admin.restore_factory_settings');
		$out .= '</a>';
		
		return $out;
	}
	
	public function crudNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$name = $this->name ?? null;
		if (empty($name)) {
			return '--';
		}
		
		$countItems = $this->rootMenuItems->count();
		if ($countItems > 0) {
			$url = $xPanel->getUrl($this->id . '/menu_items');
			$label = $this->name  ?? null;
			
			$tooltip = '';
			if (!empty($label)) {
				$customLabel = '&quot;' . $label . '&quot;';
				$title = trans('menu.menu_items_of', ['menu' => $customLabel]);
				$tooltip = ' data-bs-toggle="tooltip" title="' . $title . '"';
			}
			
			$out = '';
			
			// $out .= '<a href="' . $url . '"' . $tooltip . '>';
			$out .= $name;
			// $out .= '</a>';
			
			$out .= ' (';
			$out .= '<a href="' . $url . '"' . $tooltip . '>';
			$out .= $countItems;
			$out .= '</a>';
			$out .= ')';
			
			return $out;
		}
		
		return $name;
	}
	
	public function itemListInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$url = $xPanel->getUrl($this->id . '/menu_items');
		$label = $this->name  ?? null;
		
		$tooltip = '';
		if (!empty($label)) {
			$customLabel = '&quot;' . $label . '&quot;';
			$title = trans('menu.menu_items_of', ['menu' => $customLabel]);
			$tooltip = ' data-bs-toggle="tooltip" title="' . $title . '"';
		}
		
		$out = '<a class="btn btn-xs btn-dark" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="bi bi-list-nested"></i> ';
		$out .= trans('menu.menu_items');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
