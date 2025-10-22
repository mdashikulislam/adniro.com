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
use App\Http\Requests\Admin\MenuRequest as StoreRequest;
use App\Http\Requests\Admin\MenuRequest as UpdateRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use Database\Seeders\MenuItemSeeder;
use Database\Seeders\MenuSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MenuController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(Menu::class);
		$this->xPanel->with(['rootMenuItems']);
		$this->xPanel->setRoute(urlGen()->adminUri('menus'));
		$this->xPanel->setEntityNameStrings(trans('menu.menu'), trans('menu.menus'));
		// $this->xPanel->denyAccess(['create', 'delete']);
		$this->xPanel->enableReorder('name', 1);
		$this->xPanel->allowAccess(['reorder']);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'reset_menus_button', 'resetMenusTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('line', 'item_list_button', 'itemListInLineButton', 'beginning');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// COLUMNS
			$this->xPanel->addColumn([
				'name'          => 'name',
				'label'         => trans('menu.name'),
				'type'          => 'model_function',
				'function_name' => 'crudNameColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'  => 'location',
				'label' => trans('menu.location'),
				'type'  => 'text',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'active',
				'label'         => trans('menu.active'),
				'type'          => 'model_function',
				'function_name' => 'crudActiveColumn',
			]);
		}
		
		/*
		|--------------------------------------------------------------------------
		| FIELDS
		|--------------------------------------------------------------------------
		*/
		if ($this->onCreatePage || $this->onEditPage) {
			$availableLocations = Menu::getAvailableLocations();
			
			// Check the $availableLocations for the create form
			if ($this->onCreatePage) {
				if (empty($availableLocations)) {
					$menuListUrl = urlGen()->adminUrl('menus');
					$message = 'All predefined menu locations are already in use. You can edit existing menus or define custom locations.';
					
					notification($message, 'warning');
					
					/*
					 * Manually save the session, to allow to access to current session values
					 * after redirection make with PHP header('Location: /target-page') method.
					 */
					session()->save();
					
					redirectUrl($menuListUrl, 301, config('larapen.core.noCacheHeaders'));
				}
			}
			
			// Check the $availableLocations for the edit form
			if ($this->onEditPage) {
				$currentResourceId = request()->route()->parameter('menu');
				$menu = Menu::find($currentResourceId);
				
				if (!empty($menu)) {
					// Add current location to available options
					$availableLocations[$menu->location] = Menu::getLocationDisplayName($menu->location);
				}
			}
			
			$this->xPanel->addField([
				'name'  => 'name',
				'label' => trans('menu.name'),
				'type'  => 'text',
			]);
			
			$this->xPanel->addField([
				'name'    => 'location',
				'label'   => trans('menu.location'),
				'type'    => 'select2_from_array',
				'options' => $availableLocations,
			]);
			
			$this->xPanel->addField([
				'name'  => 'description',
				'label' => trans('menu.description'),
				'type'  => 'text',
			]);
			
			$defaultActiveValue = $this->onCreatePage ? '1' : '0';
			$this->xPanel->addField([
				'name'    => 'active',
				'label'   => trans('menu.active'),
				'type'    => 'checkbox_switch',
				'default' => $defaultActiveValue,
				'wrapper' => [
					'class' => 'col-md-12',
				],
			]);
		}
	}
	
	public function store(StoreRequest $request): RedirectResponse
	{
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request): RedirectResponse
	{
		return parent::updateCrud($request);
	}
	
	/**
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function reset(): RedirectResponse
	{
		Schema::disableForeignKeyConstraints();
		
		try {
			$menuItemsTable = (new MenuItem)->getTable();
			DB::table($menuItemsTable)->truncate();
			DB::statement('ALTER TABLE ' . DBUtils::table($menuItemsTable) . ' AUTO_INCREMENT = 1;');
			
			$menusTable = (new Menu)->getTable();
			DB::table($menusTable)->truncate();
			DB::statement('ALTER TABLE ' . DBUtils::table($menusTable) . ' AUTO_INCREMENT = 1;');
			
			$menuSeeder = new MenuSeeder();
			$menuSeeder->run();
			
			$menuSeeder = new MenuItemSeeder();
			$menuSeeder->run();
			
			$message = trans('menu.menus_restored_successfully');
			notification($message, 'success');
		} catch (\Exception $e) {
			$message = $e->getMessage();
			$message = !empty($message) ? $message : 'Something went wrong';
			notification($message, 'error');
		}
		
		Schema::enableForeignKeyConstraints();
		
		// Removing all the cache
		try {
			cache()->flush();
		} catch (Throwable $e) {
			notification($e->getMessage(), 'error');
		}
		
		return redirect()->back();
	}
}
