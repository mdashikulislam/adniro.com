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

use App\Enums\BootstrapColor;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\MenuItemRequest as StoreRequest;
use App\Http\Requests\Admin\MenuItemRequest as UpdateRequest;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\MenuItemSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MenuItemController extends PanelController
{
	protected float|int|string|null $menuId = null;
	protected float|int|string|null $menuItemId = null;
	
	public function setup()
	{
		// Set the xPanel Model
		$this->xPanel->setModel(MenuItem::class);
		
		// Init. each parent entry
		$menu = null; // * required
		$menuItem = null;
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		// Find each parent entity key
		$this->menuId = request()->route()->parameter('menuId');
		$this->menuItemId = request()->route()->parameter('parentIdentifier');
		
		if (empty($this->menuId)) {
			$parentUrl = urlGen()->adminUrl('menus');
			if (!request()->ajax()) {
				redirectUrl($parentUrl, 301, config('larapen.core.noCacheHeaders'));
			}
		}
		
		// Retrieve each parent entry
		if (!empty($this->menuItemId)) {
			$menuItem = MenuItem::find($this->menuItemId);
			abort_if(empty($menuItem), 404, t('menu_item_not_found'));
			
			$this->menuId = $menuItem->menu_id ?? $this->menuId;
		}
		if (!empty($this->menuId)) {
			$menu = Menu::find($this->menuId);
		}
		
		abort_if(empty($menu), 404, t('menu_not_found'));
		
		// CRUD Panel Configuration
		$singularTxt = trans('menu.menu_item');
		$pluralTxt = trans('menu.menu_items');
		
		$this->xPanel->addClause('where', 'menu_id', '=', $this->menuId);
		if (!empty($menuItem)) {
			$this->xPanel->addClause('where', 'parent_id', '=', $this->menuItemId);
			$this->xPanel->setParentKeyColumn('parent_id');
			
			$uri = "menus/{$this->menuId}/menu_items/{$this->menuItemId}/submenu_items";
			$label = "{$menuItem->label}, {$menu->name}";
			$singularTxt = "{$singularTxt} &rarr; {$label}";
			$pluralTxt = "{$pluralTxt} &rarr; {$label}";
			
			$parentUri = !empty($menuItem->parent)
				? "menus/{$this->menuId}/menu_items/{$menuItem->parent->id}/submenu_items"
				: dirname($uri, 2);
			$parentLabel = $menuItem->parent->label ?? null;
			$parentLabel = !empty($parentLabel) ? " &rarr; {$parentLabel}" : '';
			$parentSingularTxt = trans('menu.sub_menu_item') . $parentLabel;
			$parentPluralTxt = trans('menu.sub_menu_item') . $parentLabel;
		} else {
			$this->xPanel->addClause('where', fn ($query) => $query->roots());
			$this->xPanel->setParentKeyColumn('menu_id');
			
			$uri = "menus/{$this->menuId}/menu_items";
			$singularTxt = "{$singularTxt} &rarr; {$menu->name}";
			$pluralTxt = "{$pluralTxt} &rarr; {$menu->name}";
			
			$parentUri = dirname($uri, 2);
			$parentSingularTxt = trans('menu.menu');
			$parentPluralTxt = trans('menu.menus');
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->with(['menu', 'page', 'category', 'children']);
		$this->xPanel->setRoute(urlGen()->adminUri($uri));
		$this->xPanel->setEntityNameStrings($singularTxt, $pluralTxt);
		$this->xPanel->enableReorder('label', 1);
		$this->xPanel->allowAccess(['reorder']);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		// Has Parent
		if (
			!empty($parentUri)
			&& !empty($parentSingularTxt)
			&& !empty($parentPluralTxt)
		) {
			$this->xPanel->enableParentEntity();
			$this->xPanel->allowAccess(['parent']);
			$this->xPanel->setParentRoute(urlGen()->adminUri($parentUri));
			$this->xPanel->setParentEntityNameStrings($parentSingularTxt, $parentPluralTxt);
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'reset_menu_items_button', 'resetMenuItemsTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'rebuild_nested_set_nodes_button', 'rebuildNestedSetNodesTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationTopButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionTopButton', 'end');
		
		$this->xPanel->addButtonFromModelFunction('line', 'item_list_button', 'itemListInLineButton', 'beginning');
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS
		|--------------------------------------------------------------------------
		*/
		if ($this->onIndexPage) {
			// FILTERS
			$this->xPanel->disableSearchBar();
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'label',
					'type'  => 'text',
					'label' => mb_ucfirst(trans('menu.label')),
				],
				filterLogic: fn ($value) => $this->xPanel->addClause('where', 'label', 'LIKE', "%$value%")
			);
			
			$this->xPanel->addFilter(
				options: [
					'name'  => 'status',
					'type'  => 'dropdown',
					'label' => trans('admin.Status'),
				],
				values: [
					1 => trans('admin.Activated'),
					2 => trans('admin.Unactivated'),
				],
				filterLogic: function ($value) {
					if ($value == 1) {
						$this->xPanel->addClause('where', 'active', '=', 1);
					}
					if ($value == 2) {
						$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
					}
				}
			);
			
			// COLUMNS
			$this->xPanel->addColumn([
				'name'      => 'id',
				'label'     => '',
				'type'      => 'checkbox',
				'orderable' => false,
			]);
			
			/*
			$this->xPanel->addColumn([
				'name'          => 'menu_id',
				'label'         => trans('menu.menu'),
				'type'          => 'model_function',
				'function_name' => 'crudMenuColumn',
			]);
			*/
			
			$this->xPanel->addColumn([
				'name'          => 'label',
				'label'         => mb_ucfirst(trans('menu.label')),
				'type'          => 'model_function',
				'function_name' => 'crudLabelColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'url',
				'label'         => trans('menu.url'),
				'type'          => 'model_function',
				'function_name' => 'crudUrlColumn',
			]);
			
			$this->xPanel->addColumn([
				'name'          => 'route_name',
				'label'         => trans('menu.route_name'),
				'type'          => 'model_function',
				'function_name' => 'crudRouteNameColumn',
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
			$currentResourceId = request()->route()->parameter('submenu_item');
			$currentResourceId = request()->route()->parameter('menu_item', $currentResourceId);
			
			if (!empty($menu)) {
				$this->xPanel->addField([
					'name'  => 'menu_id',
					'type'  => 'hidden',
					'value' => $menu->id ?? $this->menuId,
				]);
			} else {
				$this->xPanel->addField([
					'label'     => trans('menu.menu'),
					'name'      => 'menu_id',
					'model'     => Menu::class,
					'entity'    => 'menu',
					'attribute' => 'name',
					'type'      => 'select2',
					'required'  => true,
					'wrapper'   => [
						'class' => 'col-md-12',
					],
				]);
			}
			
			$menuId = is_numeric($this->menuId) ? $this->menuId : -1;
			$queryModifier = fn ($query) => $query->where('menu_id', '=', $menuId);
			
			if ($this->onCreatePage) {
				if (!empty($menuItem)) {
					$this->xPanel->addField([
						'name'  => 'parent_id',
						'type'  => 'hidden',
						'value' => $menuItem->id ?? $this->menuItemId,
					]);
				} else {
					$this->xPanel->addField([
						'name'    => 'parent_id',
						'label'   => trans('menu.parent'),
						'type'    => 'select2_from_array',
						'options' => MenuItem::getNestedSelectOptions(queryModifier: $queryModifier),
						'default' => $this->menuItemId,
						'wrapper' => [
							'class' => 'col-md-6',
						],
					], 'create');
				}
			}
			
			if ($this->onEditPage) {
				$this->xPanel->addField([
					'name'    => 'parent_id',
					'label'   => trans('menu.parent'),
					'type'    => 'select2_from_array',
					'options' => MenuItem::getNestedSelectOptions(excludeId: $currentResourceId, queryModifier: $queryModifier),
					'default' => $this->menuItemId,
					'wrapper' => [
						'class' => 'col-md-6',
					],
				], 'update');
			}
			
			$menuItemTypes = (array)config('larapen.menu.menuItemTypes');
			$this->xPanel->addField([
				'name'     => 'type',
				'label'    => trans('menu.type'),
				'type'     => 'select2_from_array',
				'options'  => $menuItemTypes,
				'required' => true,
				'wrapper'  => [
					'class' => 'col-md-6',
				],
				'newline'  => true,
			]);
			
			$defaultFontIconSet = config('larapen.core.defaultFontIconSet', 'bootstrap');
			$this->xPanel->addField([
				'name'        => 'icon',
				'label'       => trans('menu.icon'),
				'type'        => 'icon_picker',
				'iconSet'     => config("larapen.core.fontIconSet.{$defaultFontIconSet}.key"),
				'iconVersion' => config("larapen.core.fontIconSet.{$defaultFontIconSet}.version"),
				'wrapper'     => [
					'class' => 'col-md-6 type-link type-button type-title',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'label',
				'label'   => mb_ucfirst(trans('menu.label')),
				'type'    => 'text',
				'hint'    => trans('menu.label_hint'),
				'wrapper' => [
					'class' => 'col-md-6 type-link type-button type-title',
				],
			]);
			
			// Get Bootstrap's Button Colors
			$btnColorsByName = BootstrapColor::Button->getColorsByName();
			$formattedBtnColors = BootstrapColor::Button->getFormattedColors();
			$this->xPanel->addField([
				'name'        => 'btn_class',
				'label'       => trans('menu.btn_class'),
				'type'        => 'select2_from_skins',
				'options'     => $btnColorsByName,
				'skins'       => $formattedBtnColors,
				'allows_null' => true,
				'hint'        => trans('menu.btn_class_hint'),
				'wrapper'     => [
					'class' => 'col-md-6 type-button',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'btn_outline',
				'label'   => trans('menu.btn_outline'),
				'type'    => 'checkbox_switch',
				'wrapper' => [
					'class' => 'col-md-6 mt-3 type-button',
				],
			]);
			
			$linkTypes = (array)config('larapen.menu.linkTypes');
			$this->xPanel->addField([
				'name'        => 'url_type',
				'label'       => trans('menu.url_type'),
				'type'        => 'select2_from_array',
				'options'     => $linkTypes,
				'allows_null' => true,
				'wrapper'     => [
					'class' => 'col-md-6 type-link type-button',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'url',
				'label'   => trans('menu.url'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-6 type-link type-button link-internal link-external',
				],
				'newline' => true,
			]);
			
			$this->xPanel->addField([
				'name'        => 'route_name',
				'label'       => trans('menu.route_name'),
				'type'        => 'select2_from_array',
				'options'     => $this->getAvailableRoutes(),
				'allows_null' => true,
				'wrapper'     => [
					'class' => 'col-md-6 type-link type-button link-route',
				],
			]);
			
			$this->xPanel->addField([
				'name'      => 'route_parameters',
				'label'     => trans('menu.route_parameters'),
				'type'      => 'repeatable',
				'subfields' => [
					[
						'name'       => 'name',
						'type'       => 'text',
						'label'      => trans('menu.route_parameters_name'),
						'attributes' => [
							'placeholder' => 'e.g. foo',
						],
						'wrapper'    => ['class' => 'col-md-6'],
					],
					[
						'name'       => 'value',
						'type'       => 'text',
						'label'      => trans('menu.route_parameters_value'),
						'attributes' => [
							'placeholder' => 'e.g. bar',
						],
						'wrapper'    => ['class' => 'col-md-6'],
					],
				],
				'init_rows' => 0,
				'min_rows'  => 0,
				'max_rows'  => 10,
				'reorder'   => true,
				'wrapper'   => [
					'class' => 'col-md-6 type-link type-button link-route',
				],
			]);
			
			$this->xPanel->addField([
				'name'        => 'target',
				'label'       => trans('menu.target'),
				'type'        => 'select2_from_array',
				'options'     => collect(getCachedReferrerList('html/a-target'))
					->mapWithKeys(fn ($item) => [$item => $item])
					->toArray(),
				'allows_null' => true,
				'wrapper'     => [
					'class' => 'col-md-6 type-link type-button',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'nofollow',
				'label'   => trans('menu.nofollow'),
				'type'    => 'checkbox_switch',
				'hint'    => trans('menu.nofollow_hint'),
				'wrapper' => [
					'class' => 'col-md-6 mt-4 type-link type-button',
				],
				'newline' => true,
			]);
			
			$this->xPanel->addField([
				'name'    => 'css_class',
				'label'   => trans('menu.css_class'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-12',
				],
			]);
			
			$this->xPanel->addField([
				'name'    => 'description',
				'label'   => trans('menu.description'),
				'type'    => 'text',
				'wrapper' => [
					'class' => 'col-md-12 type-link type-button type-title',
				],
				'newline' => true,
			]);
			
			$conditions = [
				''              => trans('admin.select'),
				'auth'          => trans('menu.for_auth_users'),
				'guest'         => trans('menu.for_guest_users'),
				'impersonating' => trans('menu.for_impersonating'),
				'authentic'     => trans('menu.for_authentic_users'),
			];
			$this->xPanel->addField([
				'name'           => 'conditions',
				'label'          => trans('menu.conditions'),
				'type'           => 'repeatable',
				'subfields'      => [
					[
						'name'        => 'type',
						'type'        => 'select_from_array',
						'label'       => trans('menu.conditions_type'),
						'options'     => $conditions,
						'allows_null' => false,
						'wrapper'     => ['class' => 'col-md-12'],
					],
					[
						'name' => 'value',
						'type' => 'hidden',
					],
				],
				'init_rows'      => 0,
				'min_rows'       => 0,
				'max_rows'       => 2,
				'reorder'        => true,
				'default_values' => [
					'type'  => null,
					'value' => 'true',
				],
				'wrapper'        => [
					'class' => 'col-md-6',
				],
			]);
			
			$this->xPanel->addField([
				'name'      => 'html_attributes',
				'label'     => trans('menu.html_attributes'),
				'type'      => 'repeatable',
				'subfields' => [
					[
						'name'       => 'name',
						'type'       => 'text',
						'label'      => trans('menu.html_attributes_name'),
						'attributes' => [
							'placeholder' => 'e.g. data-foo',
						],
						'wrapper'    => ['class' => 'col-md-6'],
					],
					[
						'name'       => 'value',
						'type'       => 'text',
						'label'      => trans('menu.html_attributes_value'),
						'attributes' => [
							'placeholder' => 'e.g. bar',
						],
						'wrapper'    => ['class' => 'col-md-6'],
					],
				],
				'init_rows' => 0,
				'min_rows'  => 0,
				'max_rows'  => 10,
				'reorder'   => true,
				'wrapper'   => [
					'class' => 'col-md-6',
				],
			]);
			
			$roles = Role::query()->get();
			$roles = $roles->pluck('name', 'name')
				->prepend(trans('admin.select'), '')
				->toArray();
			$this->xPanel->addField([
				'name'      => 'roles',
				'label'     => trans('menu.roles'),
				'type'      => 'repeatable',
				'subfields' => [
					[
						'name'        => 'name',
						'type'        => 'select_from_array',
						'label'       => trans('menu.roles_name'),
						'options'     => $roles,
						'allows_null' => false,
						'wrapper'     => ['class' => 'col-md-12'],
					],
				],
				'init_rows' => 0,
				'min_rows'  => 0,
				'max_rows'  => 10,
				'reorder'   => true,
				'wrapper'   => [
					'class' => 'col-md-6',
				],
			]);
			
			$permissions = Permission::query()->get();
			$permissions = $permissions->pluck('name', 'name')
				->prepend(trans('admin.select'), '')
				->toArray();
			$this->xPanel->addField([
				'name'      => 'permissions',
				'label'     => trans('menu.permissions'),
				'type'      => 'repeatable',
				'subfields' => [
					[
						'name'        => 'name',
						'type'        => 'select_from_array',
						'label'       => trans('menu.permissions_name'),
						'options'     => $permissions,
						'allows_null' => false,
						'wrapper'     => ['class' => 'col-md-12'],
					],
				],
				'init_rows' => 0,
				'min_rows'  => 0,
				'max_rows'  => 10,
				'reorder'   => true,
				'wrapper'   => [
					'class' => 'col-md-6',
				],
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
			
			$allFields = $this->xPanel->getFields(false);
			$allFieldSelectors = collect($allFields)
				->map(function ($item) {
					$type = $item['type'] ?? '';
					$name = $item['name'] ?? '';
					$class = $item['wrapper']['class'] ?? '';
					$class = explode(' ', $class);
					$class = collect($class)->filter(fn ($item) => !empty($item))->implode('.');
					$class = (!empty($class) && !str_starts_with($class, '.')) ? ".{$class}" : $class;
					
					if (empty($class)) return '';
					
					$selector = $class . ' [name="' . $name . '"]';
					if ($type == 'repeatable') {
						$selector = $class . ' [data-repeater-list="' . $name . '"]';
					}
					
					return trim($selector);
				})
				->filter(fn ($item) => !empty($item))
				->values()
				->join(', ');
			
			$menuItemTypeClassesJson = collect($menuItemTypes)
				->keys()
				->mapWithKeys(fn ($item) => [$item => '.type-' . $item])
				->toJson();
			
			$linkTypeClassesJson = collect($linkTypes)
				->keys()
				->mapWithKeys(fn ($item) => [$item => '.link-' . $item])
				->toJson();
			
			$data = [
				'allFieldSelectors'   => $allFieldSelectors,
				'menuItemTypeClasses' => $menuItemTypeClassesJson,
				'linkTypeClasses'     => $linkTypeClassesJson,
			];
			$this->xPanel->addField([
				'name'  => 'javascript',
				'type'  => 'custom_html',
				'value' => view('admin.js.menu-item', $data)->render(),
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
		$menu = Menu::find($this->menuId);
		
		if (empty($menu)) {
			$message = t('menu_not_found');
			notification($message, 'error');
			
			return redirect()->back();
		}
		
		Schema::disableForeignKeyConstraints();
		
		try {
			$res = MenuItem::where('menu_id', $menu->id)->delete();
			
			$menuSeeder = new MenuItemSeeder();
			$menuSeeder->runWithMenuId($menu->id);
			
			$message = trans('admin.menu_items_restored_successfully');
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
	
	/**
	 * Convert Adjacent List to Nested Set
	 *
	 * NOTE:
	 * - The items' order is reset, using the adjacent list's primary key order
	 * - Need to use the adjacent list model to add, update or delete nodes
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function rebuildNestedSetNodes(): RedirectResponse
	{
		return $this->xPanel->rebuildNestedSetNodes();
	}
	
	// PRIVATE
	
	/**
	 * @return array
	 */
	private function getAvailableRoutes(): array
	{
		$routes = [];
		
		// Get predefined routes from config or method
		$predefinedRoutes = $this->getPredefinedRoutes();
		
		// Validate that these routes actually exist
		foreach ($predefinedRoutes as $routeName => $routeLabel) {
			if ($this->routeExists($routeName)) {
				$routes[$routeName] = $routeLabel;
			}
		}
		
		// Add dynamic pages as routes
		$pages = $this->getAvailablePages();
		foreach ($pages as $pageRoute => $pageLabel) {
			$routes[$pageRoute] = $pageLabel;
		}
		
		// Add dynamic categories as routes
		$categories = $this->getAvailableCategories();
		foreach ($categories as $categoryRoute => $categoryLabel) {
			$routes[$categoryRoute] = $categoryLabel;
		}
		
		// Keep only the right listing form type route
		$msRouteName = 'listing.create.ms.showForm';
		$ssRouteName = 'listing.create.ss.showForm';
		if (!isMultipleStepsFormEnabled()) {
			if (array_key_exists($msRouteName, $routes)) {
				unset($routes[$msRouteName]);
			}
		}
		if (!isSingleStepFormEnabled()) {
			if (array_key_exists($ssRouteName, $routes)) {
				unset($routes[$ssRouteName]);
			}
		}
		
		return $routes;
	}
	
	private function getPredefinedRoutes(): array
	{
		$predefinedRoutes = config('larapen.menu.allowedRoutes', []);
		
		return (array)$predefinedRoutes;
	}
	
	private function routeExists(string $routeName): bool
	{
		try {
			return Route::has($routeName);
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * @return array
	 */
	private function getAvailablePages(): array
	{
		$pages = [];
		
		// Check if Page model exists
		if (!class_exists(Page::class)) {
			return $pages;
		}
		
		$prefix = 'page.';
		
		try {
			// Get active pages with their hierarchical structure
			$activePages = Page::query()
				->active()
				->whereNotNull('slug')
				->where('slug', '!=', '')
				->orderBy('lft')
				->get();
			
			foreach ($activePages as $page) {
				// Create route identifier for the page
				$routeKey = "{$prefix}{$page->slug}";
				
				// Create hierarchical label
				$label = $this->buildPageLabel($page, $activePages);
				
				// Add additional info about page type
				$typeLabel = match ($page->type) {
					'terms'    => ' (Terms of Service)',
					'privacy'  => ' (Privacy Policy)',
					'tips'     => ' (Tips)',
					'standard' => '',
					default    => " ({$page->type})"
				};
				
				$pages[$routeKey] = $label . $typeLabel;
			}
		} catch (\Exception $e) {
			// Log error but don't break the form
			Log::warning('Could not load pages for menu routes: ' . $e->getMessage());
		}
		
		return $pages;
	}
	
	private function buildPageLabel($currentPage, $allPages): string
	{
		$labels = [];
		
		// Build hierarchy path using nested set model
		$ancestors = $allPages->filter(function ($page) use ($currentPage) {
			return (
				$page->lft < $currentPage->lft
				&& $page->rgt > $currentPage->rgt
			);
		})->sortBy('lft');
		
		// Add ancestor names
		foreach ($ancestors as $ancestor) {
			$labels[] = $ancestor->name;
		}
		
		// Add current page name
		$labels[] = $currentPage->name;
		
		// Prefix for the label
		$labelPrefix = trans('admin.Page') . ': ';
		
		return $labelPrefix . implode(' → ', $labels);
	}
	
	/**
	 * @return array
	 */
	private function getAvailableCategories(): array
	{
		$categories = [];
		
		// Check if Category model exists
		if (!class_exists(Category::class)) {
			return $categories;
		}
		
		$prefix = 'category.';
		
		try {
			// Get active categories with their hierarchical structure
			$activeCategories = Category::query()
				->active()
				->whereNotNull('slug')
				->where('slug', '!=', '')
				->orderBy('lft')
				->get();
			
			foreach ($activeCategories as $page) {
				// Create route identifier for the category
				$routeKey = "{$prefix}{$page->slug}";
				
				// Create hierarchical label
				$label = $this->buildCategoryLabel($page, $activeCategories);
				
				$categories[$routeKey] = $label;
			}
		} catch (\Exception $e) {
			// Log error but don't break the form
			Log::warning('Could not load categories for menu routes: ' . $e->getMessage());
		}
		
		return $categories;
	}
	
	private function buildCategoryLabel($currentCategory, $allCategories): string
	{
		$labels = [];
		
		// Build hierarchy path using nested set model
		$ancestors = $allCategories->filter(function ($category) use ($currentCategory) {
			return (
				$category->lft < $currentCategory->lft
				&& $category->rgt > $currentCategory->rgt
			);
		})->sortBy('lft');
		
		// Add ancestor names
		foreach ($ancestors as $ancestor) {
			$labels[] = $ancestor->name;
		}
		
		// Add current page name
		$labels[] = $currentCategory->name;
		
		// Prefix for the label
		$labelPrefix = trans('admin.Category') . ': ';
		
		return $labelPrefix . implode(' → ', $labels);
	}
}
