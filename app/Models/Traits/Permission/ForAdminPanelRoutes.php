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

use App\Http\Controllers\Web\Admin\ActionController;
use App\Http\Controllers\Web\Admin\BackupController;
use App\Http\Controllers\Web\Admin\BlacklistController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\InlineRequestController;
use App\Http\Controllers\Web\Admin\LanguageController;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Controllers\Web\Admin\PermissionController;
use App\Http\Controllers\Web\Admin\PluginController;
use App\Http\Controllers\Web\Admin\RoleController;
use App\Http\Controllers\Web\Admin\SectionController;
use App\Http\Controllers\Web\Admin\SystemPhpInfoController;
use App\Models\Permission;
use Illuminate\Support\Facades\Route;

trait ForAdminPanelRoutes
{
	/**
	 * Get admin panel permissions (by parsing the admin panel routes)
	 *
	 * @return array
	 */
	public static function getAdminPanelPermissions(): array
	{
		$permissions = Permission::parseAdminPanelRoutesPermissions();
		$permissions = collect($permissions)->pluck('permission', 'permission')->sort();
		
		return $permissions->toArray();
	}
	
	/**
	 * Parse all the admin panel routes permissions
	 *
	 * @return array
	 */
	public static function parseAdminPanelRoutesPermissions(): array
	{
		$routeCollection = Route::getRoutes();
		
		$defaultAccess = ['list', 'create', 'update', 'delete', 'reorder', 'details_row', 'bulk-actions'];
		$defaultAllowAccess = ['list', 'create', 'update', 'delete'];
		$defaultDenyAccess = ['reorder', 'details_row', 'bulk-actions'];
		
		// Get the mapping of controller actions to their required access names
		$controllerActionAccessMap = self::getControllerActionAccessMap();
		
		// Admin Panel Access => Rewritten Access
		// Note: If a key cannot be found, use the access original name
		$accessMapping = [
			'list'   => 'view',
			'create' => 'create',
			'update' => 'edit',
			'delete' => 'delete',
			// ...
		];
		
		$tab = $data = [];
		foreach ($routeCollection as $key => $routeObj) {
			/** @var \Illuminate\Routing\Route $routeObj */
			
			// Init.
			$data['filePath'] = null;
			$data['actionMethod'] = null;
			$data['methods'] = [];
			$data['permission'] = null;
			
			// Get & Clear the route prefix
			$routePrefix = $routeObj->getPrefix();
			$routePrefix = trim($routePrefix, '/');
			if ($routePrefix != urlGen()->getAdminBasePath()) {
				$routePrefix = head(explode('/', $routePrefix));
			}
			
			// Exit, if the prefix is still not that of the admin panel
			if ($routePrefix != urlGen()->getAdminBasePath()) {
				continue;
			}
			
			$data['methods'] = $routeObj->methods();
			
			$data['uri'] = $routeObj->uri();
			$data['uri'] = preg_replace('#\{[^}]+}#', '*', $data['uri']);
			
			/*
			 * Note:
			 * $routeObj->getActionName()      => ControllerClass@method
			 * $routeObj->getControllerClass() => ControllerClass
			 * $routeObj->getActionMethod()    => method
			 */
			$routeActionName = $routeObj->getActionName();
			$routeActionMethod = $routeObj->getActionMethod();
			
			try {
				$controllerNamespace = '\\' . preg_replace('#@.+#i', '', $routeActionName);
				$reflector = new \ReflectionClass($controllerNamespace);
				$filePath = $reflector->getFileName();
			} catch (\Exception $e) {
				$filePath = null;
			}
			
			$data['filePath'] = $filePath;
			$data['actionName'] = $routeActionName;
			$data['actionMethod'] = $routeActionMethod;
			
			// Get the corresponding access name
			$access = $controllerActionAccessMap[$routeActionName] ?? $controllerActionAccessMap[$routeActionMethod] ?? null;
			
			if (!empty($filePath) && file_exists($filePath)) {
				$content = file_get_contents($filePath);
				
				// Get the CRUD master class name dynamically
				$crudMasterClassName = class_basename(PanelController::class);
				
				// Is the current class extends the CRUD master class?
				if (str_contains($content, "extends $crudMasterClassName")) {
					$allowAccess = [];
					$denyAccess = [];
					
					if (str_contains($routeActionName, PermissionController::class)) {
						if (!config('larapen.admin.allow_permission_create')) {
							$denyAccess[] = 'create';
						}
						if (!config('larapen.admin.allow_permission_update')) {
							$denyAccess[] = 'update';
						}
						if (!config('larapen.admin.allow_permission_delete')) {
							$denyAccess[] = 'delete';
						}
					} else if (str_contains($routeActionName, RoleController::class)) {
						if (!config('larapen.admin.allow_role_create')) {
							$denyAccess[] = 'create';
						}
						if (!config('larapen.admin.allow_role_update')) {
							$denyAccess[] = 'update';
						}
						if (!config('larapen.admin.allow_role_delete')) {
							$denyAccess[] = 'delete';
						}
					} else {
						// Get allowed accesses
						$matches = [];
						preg_match('#->allowAccess\(([^)]+)\);#', $content, $matches);
						$allowAccessStr = $matches[1] ?? null;
						
						if (!empty($allowAccessStr)) {
							$matches = [];
							preg_match_all("#'([^']+)'#", $allowAccessStr, $matches);
							$allowAccess = $matches[1] ?? [];
							
							if (empty($denyAccess)) {
								$matches = [];
								preg_match_all('#"([^"]+)"#', $allowAccessStr, $matches);
								$allowAccess = $matches[1] ?? [];
							}
						}
						
						// Get denied accesses
						$matches = [];
						preg_match('#->denyAccess\(([^)]+)\);#', $content, $matches);
						$denyAccessStr = $matches[1] ?? null;
						
						if (!empty($denyAccessStr)) {
							$matches = [];
							preg_match_all("#'([^']+)'#", $denyAccessStr, $matches);
							$denyAccess = $matches[1] ?? [];
							
							if (empty($denyAccess)) {
								$matches = [];
								preg_match_all('#"([^"]+)"#', $denyAccessStr, $matches);
								$denyAccess = $matches[1] ?? [];
							}
						}
					}
					
					$allowAccess = array_merge($defaultAllowAccess, (array)$allowAccess);
					$denyAccess = array_merge($defaultDenyAccess, (array)$denyAccess);
					
					$availableAccess = array_merge(array_diff($allowAccess, $defaultAccess), $defaultAccess);
					$availableAccess = array_diff($availableAccess, $denyAccess);
					
					if (in_array($access, $defaultAccess)) {
						if (!in_array($access, $availableAccess)) {
							continue;
						}
					}
					
					// For 'bulk-actions' access
					if ($access == 'bulk-actions') {
						// Check bulk actions buttons
						$pattern = '/[\'"]bulk_\w+_button[\'"]/i';
						preg_match_all($pattern, $content, $matches);
						$isBulkActionsBtnFound = !empty($matches[0]);
						
						// Check bulk actions function name
						// Use strict pattern with word boundaries and case insensitivity
						$pattern = '/[\'"]bulk[A-Z][a-zA-Z0-9]+Button[\'"]/i';
						preg_match_all($pattern, $content, $matches);
						$isBulkActionsFnNameFound = !empty($matches[0]);
						
						// Don't apply the 'bulk-actions' access to controllers that haven't bulk actions button
						if (!$isBulkActionsBtnFound && !$isBulkActionsFnNameFound) {
							continue;
						}
					}
				}
			}
			
			if (str_contains($routeActionName, ActionController::class)) {
				$inferredPermission = str($routeActionMethod)->kebab()->toString();
			} else {
				$matches = [];
				preg_match('#\\\([a-zA-Z0-9]+)Controller@#', $routeActionName, $matches);
				$controllerSlug = $matches[1] ?? null;
				
				$controllerSlug = str($controllerSlug)->kebab()->toString();
				$normalizedAccess = $accessMapping[$access] ?? $access;
				
				$inferredPermission = !empty($normalizedAccess) ? "{$controllerSlug}.{$normalizedAccess}" : null;
			}
			
			if (empty($inferredPermission)) {
				continue;
			}
			
			// For DEBUG
			// dump($inferredPermission);
			
			$data['permission'] = "admin.{$inferredPermission}";
			
			if (array_key_exists('filePath', $data)) {
				unset($data['filePath']);
			}
			if (array_key_exists('actionMethod', $data)) {
				unset($data['actionMethod']);
			}
			
			// Save It!
			$tab[$key] = $data;
			
		}
		
		// For DEBUG
		// dd(collect($tab)->pluck('permission', 'actionName')->toArray());
		
		return $tab;
	}
	
	// PRIVATE
	
	/**
	 * Get the mapping of controller actions to their required access names
	 * Build the controller action to access permission mapping
	 *
	 * Creates a comprehensive mapping between admin controller methods and their
	 * required access permissions, including standard CRUD operations and
	 * controller-specific actions.
	 *
	 * @return array<string, string> Controller action => access permission mapping
	 */
	private static function getControllerActionAccessMap(): array
	{
		// Controller's public method => Access name
		$array = [
			'index'           => 'list',
			'show'            => 'list',
			'create'          => 'create',
			'store'           => 'create',
			'edit'            => 'update',
			'update'          => 'update',
			'reorder'         => 'update',
			'saveReorder'     => 'update',
			'listRevisions'   => 'update',
			'restoreRevision' => 'update',
			'destroy'         => 'delete',
			'bulkActions'     => 'bulk-actions',
		];
		
		$array = array_merge($array, [
			'resendEmailVerification' => 'resend-verification-notification',
			'resendPhoneVerification' => 'resend-verification-notification',
		]);
		
		$array = array_merge($array, [
			DashboardController::class . '@dashboard'                  => 'view',
			DashboardController::class . '@redirect'                   => 'view',
			LanguageController::class . '@syncFilesLines'              => 'update',
			LanguageController::class . '@showTexts'                   => 'update',
			LanguageController::class . '@updateTexts'                 => 'update',
			PermissionController::class . '@seedPredefinedPermissions' => 'create',
			SectionController::class . '@reset'                        => 'delete',
			BackupController::class . '@download'                      => 'download',
			BlacklistController::class . '@banUser'                    => 'ban-users',
			InlineRequestController::class . '@make'                   => 'make',
			PluginController::class . '@install'                       => 'install',
			PluginController::class . '@uninstall'                     => 'uninstall',
			SystemPhpInfoController::class . '@rawVersion'             => 'view',
		]);
		
		// if (config('plugins.domainmapping.installed')) {}
		$dmNamespace = plugin_namespace('domainmapping', 'app\Http\Controllers\Web\Admin');
		$dmNamespace = trim($dmNamespace, '\\');
		
		return array_merge($array, [
			"$dmNamespace\DomainController@createBulkCountriesSubDomain" => 'create',
			"$dmNamespace\DomainMetaTagController@generate"              => 'create',
			"$dmNamespace\DomainSectionController@generate"              => 'create',
			"$dmNamespace\DomainSettingController@generate"              => 'create',
		]);
	}
}
