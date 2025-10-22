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

namespace App\Models\Traits\Role;

use App\Helpers\Common\DBUtils;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

trait HasAllRoles
{
	use HasSuperAdminRole;
	
	/**
	 * Get all default roles
	 *
	 * @return array
	 */
	public static function getDefaultRoles(): array
	{
		return [
			Role::getSuperAdminRole(),
		];
	}
	
	/**
	 * Get default roles (from DB)
	 *
	 * @return array
	 */
	public static function getDefaultRolesFromDb(): array
	{
		$roleList = [];
		$roles = collect();
		try {
			$roleList = Role::getDefaultRoles();
			if (!empty($roleList)) {
				$roles = Role::whereIn('name', $roleList)->get();
			}
		} catch (\Throwable $e) {
		}
		
		if (empty($roleList) || $roles->count() <= 0) {
			return [];
		}
		
		if (count($roleList) !== $roles->count()) {
			return [];
		}
		
		return $roles->toArray();
	}
	
	/**
	 * Check default roles
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return bool
	 */
	public static function checkDefaultRoles(): bool
	{
		$roles = Role::getDefaultRolesFromDb();
		
		return !empty($roles);
	}
	
	/**
	 * Reset default roles
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return array
	 */
	public static function resetDefaultRoles(): array
	{
		$roles = [];
		
		try {
			// Remove all current roles & their relationship
			$rolesOld = Role::all();
			foreach ($rolesOld as $roleOld) {
				if ($roleOld->permissions()) {
					$roleOld->permissions()->detach();
				}
				if (!in_array($roleOld->name, Role::getDefaultRoles())) {
					$roleOld->delete();
				}
			}
			
			// Reset roles table ID auto-increment
			$rolesTable = DBUtils::table(config('permission.table_names.roles'));
			DB::statement("ALTER TABLE $rolesTable AUTO_INCREMENT = 1;");
			
			// Get default system roles
			$defaultRoles = Role::getDefaultRoles();
			
			// Create the default roles (in DB)
			foreach ($defaultRoles as $defaultRole) {
				$roles[] = Role::firstOrCreate(['name' => $defaultRole]);
			}
		} catch (\Exception $e) {
		}
		
		return $roles;
	}
}
