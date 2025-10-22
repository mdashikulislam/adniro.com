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

namespace App\Observers;

use App\Exceptions\Custom\ModelOperationCancelledException;
use App\Models\Permission;
use App\Models\Role;

class PermissionObserver extends BaseObserver
{
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param Permission $permission
	 * @return void
	 * @throws \App\Exceptions\Custom\ModelOperationCancelledException
	 */
	public function deleting(Permission $permission)
	{
		// Check if default permission exist, to prevent recursion of the deletion.
		if (Permission::checkDefaultPermissions()) {
			if (in_array($permission->name, Permission::getDefaultPermissions())) {
				// Since Laravel detaches all pivot entries before starting deletion,
				// Re-assign the permission to the corresponding role.
				// ---
				// super-admin
				$adminPermissions = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
				if (in_array($permission->name, $adminPermissions)) {
					$permission->assignRole(Role::getSuperAdminRole());
				}
				
				// Use Exceptions to propagate error message in the Controller
				// Or use "return false;" to cancel operation without message.
				$message = trans('admin.cannot_delete_permission');
				throw new ModelOperationCancelledException($message);
			}
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Permission $permission
	 * @return void
	 */
	public function saved(Permission $permission)
	{
		// ...
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param Permission $permission
	 * @return void
	 */
	public function deleted(Permission $permission)
	{
		// ...
	}
}
