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

use App\Models\Role;

trait HasSuperAdminRole
{
	/**
	 * Default super-admin users role
	 *
	 * @return string
	 */
	public static function getSuperAdminRole(): string
	{
		return 'super-admin';
	}
	
	/**
	 * Get super-admin users role (from DB)
	 *
	 * @return \App\Models\Role|null
	 */
	public static function getSuperAdminRoleFromDb(): ?Role
	{
		try {
			return Role::where('name', Role::getSuperAdminRole())->first();
		} catch (\Throwable $e) {
			return null;
		}
	}
	
	/**
	 * Check super-admin role
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return bool
	 */
	public static function checkSuperAdminRole(): bool
	{
		$role = Role::getSuperAdminRoleFromDb();
		
		return !empty($role);
	}
	
	/**
	 * Ensure super-admin role exists
	 *
	 * @return \App\Models\Role|null
	 */
	public static function ensureSuperAdminRoleExists(): ?Role
	{
		try {
			return Role::firstOrCreate(['name' => Role::getSuperAdminRole()]);
		} catch (\Throwable $e) {
			return null;
		}
	}
}
