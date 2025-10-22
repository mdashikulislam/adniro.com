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

namespace App\Models\Traits\Permission;

use App\Helpers\Common\DBUtils;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

trait HasAllPermissions
{
	use ForAdminPanelRoutes;
	use HasSuperAdminPermissions;
	
	/**
	 * Get all default permissions
	 *
	 * @return array<int, string>
	 */
	public static function getDefaultPermissions(): array
	{
		return array_merge(
			Permission::getSuperAdminPermissions(),
			Permission::getStaffPermissions()
		);
	}
	
	/**
	 * Get default permissions (from DB)
	 *
	 * @return array
	 */
	public static function getDefaultPermissionsFromDb(): array
	{
		$permissionList = [];
		$permissions = collect();
		try {
			$permissionList = Permission::getDefaultPermissions();
			if (!empty($permissionList)) {
				$permissions = Permission::whereIn('name', $permissionList)->get();
			}
		} catch (\Throwable $e) {
		}
		
		if (empty($permissionList) || $permissions->count() <= 0) {
			return [];
		}
		
		if (count($permissionList) !== $permissions->count()) {
			return [];
		}
		
		return $permissions->toArray();
	}
	
	/**
	 * Check default permissions
	 *
	 * @return bool
	 */
	public static function checkDefaultPermissions(): bool
	{
		if (
			!Permission::checkSuperAdminRoleAndPermissions()
		) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Reset default permissions
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return void
	 */
	public static function resetDefaultPermissions(): void
	{
		try {
			// Create the default super-admin role
			$roles = Role::resetDefaultRoles();
			if (empty($roles)) return;
			
			// Remove all current permissions & their relationship
			$permissionsOld = Permission::all();
			foreach ($permissionsOld as $permissionOld) {
				if ($permissionOld->roles()->count() > 0) {
					$permissionOld->roles()->detach();
				}
				if (!in_array($permissionOld->name, Permission::getDefaultPermissions())) {
					$permissionOld->delete();
				}
			}
			
			// Reset permissions table ID auto-increment
			$permissionsTable = DBUtils::table(config('permission.table_names.permissions'));
			DB::statement("ALTER TABLE $permissionsTable AUTO_INCREMENT = 1;");
			
			foreach ($roles as $role) {
				// Create default super-admin permissions
				if ($role->name == Role::getSuperAdminRole()) {
					$permissionList = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
					if (!empty($permissionList)) {
						foreach ($permissionList as $permissionName) {
							$permission = Permission::firstOrCreate(['name' => $permissionName]);
							$role->givePermissionTo($permission);
						}
					}
				}
				
				// ...
			}
		} catch (\Exception $e) {
		}
	}
	
	/**
	 * Ensure default roles and permissions exist
	 *
	 * @return void
	 */
	public static function ensureDefaultRolesAndPermissionsExist(): void
	{
		if (!Permission::checkDefaultPermissions()) {
			Permission::resetDefaultPermissions();
		}
	}
}
