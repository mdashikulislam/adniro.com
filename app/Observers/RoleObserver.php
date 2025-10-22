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

class RoleObserver extends BaseObserver
{
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param Role $role
	 * @return void
	 * @throws \App\Exceptions\Custom\ModelOperationCancelledException
	 */
	public function deleting(Role $role)
	{
		// Check if default permission exist, to prevent recursion of the deletion.
		if (Permission::checkDefaultPermissions()) {
			if (in_array($role->name, Role::getDefaultRoles())) {
				// Since Laravel detaches all pivot entries before starting deletion,
				// Re-give the corresponding permissions to role.
				// ---
				// super-admin
				if ($role->name == Role::getSuperAdminRole()) {
					$role->syncPermissions(Permission::getSuperAdminPermissions());
				}
				
				// Use Exceptions to propagate error message in the Controller
				// Or use "return false;" to cancel operation without message.
				$message = trans('admin.cannot_delete_role');
				throw new ModelOperationCancelledException($message);
			}
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Role $role
	 * @return void
	 */
	public function saved(Role $role)
	{
		// ...
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param Role $role
	 * @return void
	 */
	public function deleted(Role $role)
	{
		// ...
	}
}
