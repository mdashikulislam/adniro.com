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
use App\Models\Role;
use App\Models\Traits\Role\HasAllRoles;

trait RoleTrait
{
	use HasAllRoles;
	
	// ===| ADMIN PANEL METHODS |===
	
	public function updateInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$out = '';
		
		if (strtolower($this->name) == strtolower(Role::getSuperAdminRole())) {
			return $out;
		}
		
		$url = urlGen()->adminUrl("roles/{$this->id}/edit");
		
		$out = '<a href="' . $url . '" class="btn btn-xs btn-primary">';
		$out .= '<i class="fa-regular fa-pen-to-square"></i> ';
		$out .= trans('admin.edit');
		$out .= '</a>';
		
		return $out;
	}
	
	public function deleteInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$out = '';
		
		if (in_array($this->name, Role::getDefaultRoles())) {
			return $out;
		}
		
		$url = urlGen()->adminUrl("roles/{$this->id}");
		
		$out = '<a href="' . $url . '" class="btn btn-xs btn-danger" data-button-type="delete">';
		$out .= '<i class="fa-regular fa-trash-can"></i> ';
		$out .= trans('admin.delete');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
