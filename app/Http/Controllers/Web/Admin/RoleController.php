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

use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\Request;
use App\Http\Requests\Admin\RoleRequest as StoreRequest;
use App\Http\Requests\Admin\RoleRequest as UpdateRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;

class RoleController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Role::class);
		$this->xPanel->setRoute(urlGen()->adminUri('roles'));
		$this->xPanel->setEntityNameStrings(trans('admin.role'), trans('admin.roles'));
		
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
				'name'  => 'name',
				'label' => trans('admin.name'),
				'type'  => 'text',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$role = null;
			
			if ($this->onEditPage) {
				$currentResourceId = request()->route()->parameter('role');
				$role = Role::find($currentResourceId);
			}
			
			$this->xPanel->addField([
				'name'  => 'name',
				'label' => trans('admin.name'),
				'type'  => 'text',
			], 'create');
			
			if (!empty($role)) {
				$this->xPanel->addField([
					'name'  => 'name',
					'type'  => 'custom_html',
					'value' => '<h4><span class="fw-bold">' . trans('admin.role') . ':</span> ' . $role->name . '</h4>',
				], 'update');
			}
			
			$this->xPanel->addField([
				'label'     => mb_ucfirst(trans('admin.permission_plural')),
				'type'      => 'checklist',
				'name'      => 'permissions',
				'entity'    => 'permissions',
				'attribute' => 'name',
				'model'     => Permission::class,
				'pivot'     => true,
			]);
			
			if (!config('larapen.admin.allow_role_create')) {
				$this->xPanel->denyAccess('create');
			}
			if (!config('larapen.admin.allow_role_update')) {
				$this->xPanel->denyAccess('update');
			}
			if (!config('larapen.admin.allow_role_delete')) {
				$this->xPanel->denyAccess('delete');
			}
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		$request = $this->setRoleDefaultPermissions($request);
		
		// Otherwise, changes won't have an effect
		cache()->forget('spatie.permission.cache');
		
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		$request = $this->setRoleDefaultPermissions($request);
		
		// Otherwise, changes won't have an effect
		cache()->forget('spatie.permission.cache');
		
		return parent::updateCrud($request);
	}
	
	// PRIVATE
	
	/**
	 * Set role's default (or required) permissions
	 *
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	private function setRoleDefaultPermissions(Request $request): Request
	{
		// Get request permissions
		$permissionIds = $request->input('permissions');
		$permissionIds = collect($permissionIds)->map(fn ($item) => (int)$item)->toArray();
		
		// Set the role default permissions (If needed)
		// Give the minimum permissions to the role
		$role = Role::find($request->segment(3));
		if (!empty($role)) {
			// super-admin
			if ($role->name == Role::getSuperAdminRole()) {
				$permissionList = array_merge(Permission::getSuperAdminPermissions(), Permission::getStaffPermissions());
				$permissions = Permission::whereIn('name', $permissionList);
				if ($permissions->count() > 0) {
					$permissionIdsFromDb = collect($permissions->get())->keyBy('id')->keys()->toArray();
					$permissionIds = array_merge($permissionIds, $permissionIdsFromDb);
				}
			}
		}
		
		// Update the request value
		// $request->request->set('permissions', $permissionIds);
		$request->merge(['permissions' => $permissionIds]);
		
		return $request;
	}
}
