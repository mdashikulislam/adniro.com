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
use App\Models\Permission;
use App\Models\Traits\Permission\HasAllPermissions;

trait PermissionTrait
{
	use HasAllPermissions;
	
	// ===| ADMIN PANEL METHODS |===
	
	public function seedPredefinedPermissionsTopButton(?Panel $xPanel = null): ?string
	{
		if (!config('larapen.admin.allow_permission_create')) {
			return null;
		}
		
		$url = urlGen()->adminUrl('permissions/seed_predefined_permissions');
		
		$out = '<a class="btn btn-success shadow mb-1 confirm-simple-action" href="' . $url . '">';
		$out .= '<i class="fa-solid fa-industry"></i> ';
		$out .= trans('admin.Reset the Permissions');
		$out .= '</a>';
		
		return $out;
	}
	
	public function deleteInLineButton(?Panel $xPanel = null, ?self $entry = null): string
	{
		$out = '';
		
		if (in_array($this->name, Permission::getDefaultPermissions())) {
			return $out;
		}
		
		$url = urlGen()->adminUrl("permissions/{$this->id}");
		
		$out = '<a href="' . $url . '" class="btn btn-xs btn-danger" data-button-type="delete">';
		$out .= '<i class="fa-regular fa-trash-can"></i> ';
		$out .= trans('admin.delete');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
