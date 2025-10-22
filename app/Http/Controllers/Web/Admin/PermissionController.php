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

namespace App\Http\Controllers\Web\Admin;

use App\Helpers\Common\DBUtils;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\PermissionRequest as StoreRequest;
use App\Http\Requests\Admin\PermissionRequest as UpdateRequest;
use App\Http\Requests\Admin\Request;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PermissionController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Permission::class);
		$this->xPanel->setRoute(urlGen()->adminUri('permissions'));
		$this->xPanel->setEntityNameStrings(trans('admin.permission_singular'), trans('admin.permission_plural'));
		
		$this->xPanel->addButtonFromModelFunction('top', 'seed_predefined_permissions', 'seedPredefinedPermissionsTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		
		$this->xPanel->removeButton('delete');
		$this->xPanel->addButtonFromModelFunction('line', 'delete', 'deleteInLineButton', 'end');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// COLUMNS
			$this->xPanel->addColumn([
				'name'      => 'id',
				'label'     => '',
				'type'      => 'checkbox',
				'orderable' => false,
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'name',
				'label' => trans('admin.name'),
				'type'  => 'text',
			]);
			
			$this->xPanel->addColumn([
				// n-n relationship (with pivot table)
				'label'     => trans('admin.roles_have_permission'),
				'type'      => 'select_multiple',
				'name'      => 'roles',
				'entity'    => 'roles',
				'attribute' => 'name',
				'model'     => Role::class,
				'pivot'     => true,
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$permission = null;
			
			if ($this->onEditPage) {
				$currentResourceId = request()->route()->parameter('permission');
				$permission = Permission::find($currentResourceId);
			}
			
			$this->xPanel->addField([
				'name'    => 'name',
				'label'   => trans('admin.name'),
				'type'    => 'select2_from_array',
				'options' => Permission::getAdminPanelPermissions(),
			], 'create');
			
			if (!empty($permission)) {
				$this->xPanel->addField([
					'name'  => 'name_html',
					'type'  => 'custom_html',
					'value' => '<h4><span class="fw-bold">' . trans('admin.permission') . '</span>: ' . $permission->name . '</h4>',
				], 'update');
			}
			
			$this->xPanel->addField([
				'label'     => trans('admin.roles'),
				'type'      => 'checklist',
				'name'      => 'roles',
				'entity'    => 'roles',
				'attribute' => 'name',
				'model'     => Role::class,
				'pivot'     => true,
			]);
			
			if (!config('larapen.admin.allow_permission_create')) {
				$this->xPanel->denyAccess('create');
			}
			if (!config('larapen.admin.allow_permission_update')) {
				$this->xPanel->denyAccess('update');
			}
			if (!config('larapen.admin.allow_permission_delete')) {
				$this->xPanel->denyAccess('delete');
			}
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		$request = $this->setPermissionDefaultRoles($request);
		
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		$request = $this->setPermissionDefaultRoles($request);
		
		return parent::updateCrud($request);
	}
	
	/**
	 * Seed the database with all predefined system permissions.
	 * Creates and stores all default permissions required by the app in the database.
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function seedPredefinedPermissions(): RedirectResponse
	{
		$success = false;
		
		$aclTableNames = config('permission.table_names');
		
		// Get all permissions
		$permissionsTable = $aclTableNames['permissions'] ?? null;
		if (!empty($permissionsTable)) {
			$permissions = Permission::getAdminPanelPermissions();
			if (!empty($permissions)) {
				DB::statement('ALTER TABLE ' . DBUtils::table($permissionsTable) . ' AUTO_INCREMENT = 1;');
				foreach ($permissions as $permissionName) {
					$entry = Permission::firstOrCreate(['name' => $permissionName]);
					$success = (!empty($entry) && $entry->wasRecentlyCreated);
					if (empty($entry)) {
						break;
					}
				}
			}
		}
		
		if ($success) {
			$message = trans('admin.The default permissions were been created');
			notification($message, 'success');
		} else {
			$message = trans('admin.Default permissions have already been created');
			notification($message, 'warning');
		}
		
		return redirect()->back();
	}
	
	// PRIVATE
	
	/**
	 * Set permission's default (or required) roles
	 *
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	private function setPermissionDefaultRoles(Request $request): Request
	{
		// Get request roles
		$roleIds = $request->input('roles');
		$roleIds = collect($roleIds)->map(fn ($item) => (int)$item)->toArray();
		
		// Set the permission for the role (if needed)
		$permission = Permission::find($request->segment(3));
		if (!empty($permission)) {
			// super-admin
			$permissionList = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
			if (in_array($permission->name, $permissionList)) {
				$roles = Role::query()->where('name', '=', Role::getSuperAdminRole());
				if ($roles->count() > 0) {
					$roleIdsFromDb = collect($roles->get())->keyBy('id')->keys()->toArray();
					$roleIds = array_merge($roleIds, $roleIdsFromDb);
				}
			}
		}
		
		// Update the request value
		// $request->request->set('roles', $roleIds);
		$request->merge(['roles' => $roleIds]);
		
		return $request;
	}
}
