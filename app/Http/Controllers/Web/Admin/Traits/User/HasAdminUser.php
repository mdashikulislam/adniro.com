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

namespace App\Http\Controllers\Web\Admin\Traits\User;

use App\Models\Permission;
use App\Models\Role;
use App\Http\Requests\Admin\Request;

trait HasAdminUser
{
	/**
	 * Set admin flag when roles contain staff permissions
	 *
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	protected function handleIsAdminFromRoles(Request $request)
	{
		$isFilled = false;
		
		if ($request->filled('roles')) {
			$rolesIds = $request->input('roles');
			foreach ($rolesIds as $roleId) {
				$role = Role::find($roleId);
				if (!empty($role)) {
					$permissions = $role->permissions;
					if ($permissions->count() > 0) {
						foreach ($permissions as $permission) {
							if (in_array($permission->name, Permission::getStaffPermissions())) {
								$isFilled = true;
							}
						}
					}
				}
			}
		}
		
		if ($isFilled) {
			$request->request->set('is_admin', 1);
		}
		
		return $request;
	}
	
	/**
	 * Set admin flag when staff permissions are selected
	 *
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	protected function handleIsAdminFromPermissions(Request $request)
	{
		$isFilled = false;
		
		if ($request->filled('permissions')) {
			$permissionIds = $request->input('permissions');
			foreach ($permissionIds as $permissionId) {
				$permission = Permission::find($permissionId);
				if (in_array($permission->name, Permission::getStaffPermissions())) {
					$isFilled = true;
				}
			}
		}
		
		if ($isFilled) {
			$request->request->set('is_admin', 1);
		}
		
		return $request;
	}
}
