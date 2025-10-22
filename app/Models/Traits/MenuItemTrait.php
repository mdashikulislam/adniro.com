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
use App\Models\Menu;

trait MenuItemTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function resetMenuItemsTopButton(?Panel $xPanel = null): string
	{
		$out = '';
		
		if (request()->route()->hasParameter('menuItemId')) return $out;
		
		$url = urlGen()->adminUrl("menus/reset");
		
		$menuId = request()->route()->parameter('menuId', 0);
		$menuName = null;
		if (!empty($menuId)) {
			$menu = Menu::find($menuId);
			
			$menuName = $menu->name ?? null;
			$menuName = !empty($menuName) ? mb_strtolower($menuName) : null;
			$menuId = $menu->id ?? $menuId;
			
			$url = urlGen()->adminUrl("menus/{$menuId}/menu_items/reset");
		}
		
		$msg = trans('menu.reset_all_menu_items', ['menu' => $menuName]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out .= '<a class="btn btn-success shadow mb-1 confirm-simple-action" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa-solid fa-industry"></i> ';
		$out .= trans('admin.restore_factory_settings');
		$out .= '</a>';
		
		return $out;
	}
	
	public function rebuildNestedSetNodesTopButton(?Panel $xPanel = null): string
	{
		$out = '';
		
		if (request()->route()->hasParameter('menuItemId')) return $out;
		
		$menuId = request()->route()->parameter('menuId', 0);
		$url = urlGen()->adminUrl("menus/{$menuId}/menu_items/rebuild_nested_set_nodes");
		
		$msg = trans('admin.rebuild_nested_set_nodes_info');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out .= '<a class="btn btn-light shadow mb-1 confirm-simple-action" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa-solid fa-code-branch"></i> ';
		$out .= trans('admin.rebuild_nested_set_nodes');
		$out .= '</a>';
		
		return $out;
	}
	
	public function crudMenuColumn(?Panel $xPanel = null, array $column = []): string
	{
		$menuName = $this->menu->name ?? null;
		if (empty($menuName)) {
			return '--';
		}
		
		return $menuName;
	}
	
	public function crudLabelColumn(?Panel $xPanel = null, array $column = []): string
	{
		$limit = $column['limit'] ?? 100;
		
		$out = '';
		
		$iconClass = $this->icon ?? '';
		$icon = !empty($iconClass) ? '<i class="' . $iconClass . '"></i> ' : '';
		$label = $this->formatted_label ?? '';
		$label = str($label)->limit($limit, '[...]')->toString();
		$fullLabel = "{$icon}{$label}";
		
		$type = $this->type ?? null;
		
		if (in_array($type, ['link', 'button'])) {
			$url = $this->url ?? null;
			
			if ($type == 'link') {
				if (!empty($url)) {
					$out .= '<a href="' . $url . '" target="_blank">' . $fullLabel . '</a>';
				}
			}
			if ($type == 'button') {
				$isBtnOutline = $this->btn_outline ?? false;
				$btnClass = $this->btn_class ?? '';
				$btnClass = $isBtnOutline ? str_replace('btn-', 'btn-outline-', $btnClass) : $btnClass;
				
				if (!empty($url) || !empty($btnClass)) {
					$hrefAttr = !empty($url) ? ' href="' . $url . '"' : '';
					$classAttr = !empty($btnClass) ? ' class="btn ' . $btnClass . '"' : '';
					
					$out .= '<a' . $hrefAttr . $classAttr . '>' . $fullLabel . '</a>';
				}
			}
		} else {
			$out .= $fullLabel;
		}
		
		// Help Icon
		$helpIcon = '';
		$description = $this->description ?? null;
		if (!empty($description)) {
			$tooltip = ' data-bs-toggle="tooltip" title="' . $description . '"';
			$helpIcon .= !empty($out) ? ' ' : '';
			$helpIcon .= '<i class="bi bi-question-circle"' . $tooltip . '></i>';
		}
		$out .= $helpIcon;
		
		// Count Sub-MenuItems
		$subOut = '';
		$countItems = $this->children->count();
		if ($countItems > 0) {
			$uri = "menus/{$this->menu_id}/menu_items/{$this->id}/submenu_items";
			$url = urlGen()->adminUrl($uri);
			$label = $this->formatted_label ?? null;
			
			$tooltip = '';
			if (!empty($label)) {
				$label = strip_tags($label);
				$customLabel = '&quot;' . $label . '&quot;';
				$title = trans('menu.sub_menu_items_of', ['menuItem' => $customLabel]);
				$tooltip = ' data-bs-toggle="tooltip" title="' . $title . '"';
			}
			
			$subOut .= !empty($out) ? ' ' : '';
			$subOut .= '(';
			$subOut .= '<a href="' . $url . '"' . $tooltip . '>';
			$subOut .= $countItems;
			$subOut .= '</a>';
			$subOut .= ')';
		}
		$out .= $subOut;
		
		return $out;
	}
	
	public function crudUrlColumn(?Panel $xPanel = null, array $column = []): string
	{
		$type = $this->type ?? null;
		$url = $this->url ?? null;
		$defaultUrl = '--';
		
		if (!in_array($type, ['link', 'button']) || empty($url)) {
			return $defaultUrl;
		}
		
		return '<a href="' . $url . '" target="_blank">' . $url . '</a>';
	}
	
	public function crudRouteNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$routeName = $this->route_name ?? null;
		if (empty($routeName)) {
			return '--';
		}
		
		return $routeName;
	}
	
	public function crudSubMenuItemsColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = '';
		
		$uri = "menus/{$this->menu_id}/menu_items/{$this->id}/submenu_items";
		$url = urlGen()->adminUrl($uri);
		
		$msg = trans('menu.sub_menu_items_of', ['menuItem' => $this->name]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		$countSubItems = $this->children->count();
		
		$out .= '<a class="btn btn-xs btn-light" href="' . $url . '"' . $tooltip . '>';
		$out .= $countSubItems . ' ';
		$out .= ($countSubItems > 1) ? trans('menu.sub_menu_items') : trans('menu.sub_menu_item');
		$out .= '</a>';
		
		return $out;
	}
	
	public function itemListInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$uri = "menus/{$this->menu_id}/menu_items/{$this->id}/submenu_items";
		$url = urlGen()->adminUrl($uri);
		$label = $this->formatted_label ?? null;
		
		$tooltip = '';
		if (!empty($label)) {
			$label = strip_tags($label);
			$customLabel = '&quot;' . $label . '&quot;';
			$title = trans('menu.sub_menu_items_of', ['menuItem' => $customLabel]);
			$tooltip = ' data-bs-toggle="tooltip" title="' . $title . '"';
		}
		
		$out = '<a class="btn btn-xs btn-dark" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="bi bi-list-nested"></i> ';
		$out .= trans('menu.sub_menu_items');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
