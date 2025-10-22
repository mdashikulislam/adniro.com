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

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

trait HasSuperAdminPermissions
{
	/**
	 * Default super-admin users permissions
	 *
	 * @return array<int, string>
	 */
	public static function getSuperAdminPermissions(): array
	{
		return [
			'admin.roles.view',
			'admin.roles.create',
			'admin.roles.edit',
			'admin.roles.delete',
			'admin.permissions.view',
			'admin.permissions.create',
			'admin.permissions.edit',
			'admin.permissions.delete',
		];
	}
	
	/**
	 * Default staff users permissions
	 *
	 * @return array<int, string>
	 */
	public static function getStaffPermissions(): array
	{
		return [
			'admin.dashboard.view',
		];
	}
	
	/**
	 * Get super-admin users permissions (from DB)
	 *
	 * @return array
	 */
	public static function getSuperAdminPermissionsFromDb(): array
	{
		$permissionList = [];
		$permissions = collect();
		try {
			$permissionList = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
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
	 * Check super-admin permissions
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return bool
	 */
	public static function checkSuperAdminPermissions(): bool
	{
		$permissions = Permission::getSuperAdminPermissionsFromDb();
		
		return !empty($permissions);
	}
	
	/**
	 * Check super-admin role & permissions
	 *
	 * @param \App\Models\Role|null $role
	 * @return bool
	 */
	public static function checkSuperAdminRoleAndPermissions(?Role $role = null): bool
	{
		$doesRoleExist = !empty($role);
		$doesRoleExist = $doesRoleExist || Role::checkSuperAdminRole();
		
		if (!$doesRoleExist || !Permission::checkSuperAdminPermissions()) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Reset super-admin permissions
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @param \App\Models\Role|null $role
	 * @return void
	 */
	public static function resetSuperAdminPermissions(?Role $role = null): void
	{
		try {
			// Create the default super-admin role
			$role = empty($role) ? Role::ensureSuperAdminRoleExists() : $role;
			if (empty($role)) return;
			
			$permissionList = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
			
			// Remove all current permissions & their relationship
			$permissionsOld = Permission::whereIn('name', $permissionList)->get();
			foreach ($permissionsOld as $permissionOld) {
				if ($permissionOld->roles()->count() > 0) {
					$permissionOld->roles()->detach();
				}
				if (!in_array($permissionOld->name, $permissionList)) {
					$permissionOld->delete();
				}
			}
			
			// Create default super-admin permissions
			if (!empty($permissionList)) {
				foreach ($permissionList as $permissionName) {
					$permission = Permission::firstOrCreate(['name' => $permissionName]);
					$role->givePermissionTo($permission);
				}
			}
		} catch (\Exception $e) {
		}
	}
	
	/**
	 * Ensure super-admin role and permissions exist
	 * NOTE: Return the Role object
	 *
	 * @return \App\Models\Role|null
	 */
	public static function ensureSuperAdminRoleAndPermissionsExist(): ?Role
	{
		$role = empty($role) ? Role::ensureSuperAdminRoleExists() : $role;
		if (!Permission::checkSuperAdminRoleAndPermissions($role)) {
			Permission::resetSuperAdminPermissions($role);
		}
		
		return $role;
	}
	
	/**
	 * Ensure at least one super-admin user exists in the system.
	 *
	 * Creates a super-admin user if none exists by following a priority fallback strategy:
	 * 1. First, assigns super-admin role to existing users with is_admin = 1
	 * 2. If no admin users exist, assigns the role to the first user in the system
	 * 3. As a final fallback, assigns the role to the user with the configured app email
	 *
	 * Resets default permissions if needed before processing. This function is typically
	 * used during system initialization or migration to ensure proper admin access.
	 *
	 * NOTE: Must use try {...} catch {...}
	 *
	 * USAGE: This method is primarily used to upgrade the app's data. However, due to its low memory usage,
	 * it should also be called during app initialization to consistently ensure a super-admin account exists.
	 *
	 * @return void
	 */
	public static function ensureSuperAdminExists(): void
	{
		// Ensure super-admin role and permissions exist
		$role = Permission::ensureSuperAdminRoleAndPermissionsExist();
		if (empty($role) || empty($role->name)) return;
		
		if (Permission::doesSuperAdminUserExist()) return;
		
		// Auto define default super-admin user(s)
		try {
			// Temporarily disable the lazy loading prevention
			preventLazyLoadingForModelRelations(false);
			
			$isSuperAdminRoleSet = false;
			
			// Assign the super-admin role to the old admin users
			if (Schema::hasColumn((new User)->getTable(), 'is_admin')) {
				$admins = User::query()->where('is_admin', 1)->get();
				if ($admins->count() > 0) {
					foreach ($admins as $admin) {
						$admin->removeRole($role->name);
						$admin->assignRole($role->name);
						if (!$isSuperAdminRoleSet) {
							$isSuperAdminRoleSet = true;
						}
					}
				}
			}
			
			if (!$isSuperAdminRoleSet) {
				// Assign the super-admin role to the single user (If only one user exists)
				$users = User::query();
				$admin = ($users->count() === 1) ? $users->first() : null;
				if (!empty($admin)) {
					$admin->removeRole($role->name);
					$admin->assignRole($role->name);
					$isSuperAdminRoleSet = true;
				}
			}
			
			if (!$isSuperAdminRoleSet) {
				$appEmail = config('settings.app.email');
				if (!empty($appEmail)) {
					$admin = User::query()->where('email', $appEmail)->first();
					if (!empty($admin)) {
						$admin->removeRole($role->name);
						$admin->assignRole($role->name);
						$isSuperAdminRoleSet = true;
					}
				}
			}
			
			if (!$isSuperAdminRoleSet) {
				// Assign the super-admin role to the first user
				$admin = User::query()->orderBy('id')->first();
				if (!empty($admin)) {
					$admin->removeRole($role->name);
					$admin->assignRole($role->name);
					$isSuperAdminRoleSet = true;
				}
			}
			
			// Re-enable the lazy loading prevention if needed
			preventLazyLoadingForModelRelations();
		} catch (\Throwable $e) {
		}
	}
	
	/**
	 * Check super-admin user(s) exist(s)
	 *
	 * @return bool
	 */
	public static function doesSuperAdminUserExist(): bool
	{
		try {
			$superAdmins = User::role(Role::getSuperAdminRole());
			
			return ($superAdmins->count() > 0);
		} catch (\Throwable $e) {
			return false;
		}
	}
	
	// LEGACY
	
	/**
	 * Super-admin users legacy permissions
	 *
	 * @return array<int, string>
	 */
	public static function getSuperAdminLegacyPermissions(): array
	{
		return [
			'permission-list',
			'permission-create',
			'permission-update',
			'permission-delete',
			'role-list',
			'role-create',
			'role-update',
			'role-delete',
		];
	}
	
	/**
	 * Staff users legacy permissions
	 *
	 * @return array<int, string>
	 */
	public static function getStaffLegacyPermissions(): array
	{
		return [
			'dashboard-access',
		];
	}
}
