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

namespace App\Http\Requests\Admin;

use App\Helpers\Common\RepeaterFieldHandler;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\PanelExtended;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MenuItemRequest extends Request
{
	use PanelExtended;
	
	protected RepeaterFieldHandler $repeaterHandler;
	
	public function __construct(RepeaterFieldHandler $repeaterHandler)
	{
		parent::__construct();
		$this->repeaterHandler = $repeaterHandler;
	}
	
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		// dd($this->request->all());
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$menuItemTypes = (array)config('larapen.menu.menuItemTypes');
		$linkTypes = (array)config('larapen.menu.linkTypes');
		$targets = getCachedReferrerList('html/a-target');
		
		$rules = [
			'menu_id'                  => ['required'],
			'parent_id'                => ['nullable', 'exists:menu_items,id'],
			'type'                     => ['required', Rule::in(array_keys($menuItemTypes))],
			'icon'                     => ['nullable', 'string', 'max:100'],
			'label'                    => ['nullable', 'string', 'max:255'],
			'url_type'                 => ['nullable', Rule::in(array_keys($linkTypes))],
			'route_name'               => ['nullable', 'string', 'max:255'],
			'route_parameters'         => ['nullable', 'array'],
			'route_parameters.*.name'  => ['string'],
			'route_parameters.*.value' => ['nullable', 'string'],
			'url'                      => ['nullable', 'string', 'max:255'],
			'target'                   => ['nullable', Rule::in($targets)],
			'btn_class'                => ['nullable', 'string', 'max:255'],
			'conditions'               => ['nullable', 'array'],
			'conditions.*.type'        => ['string'],
			'conditions.*.value'       => ['string'],
			'attributes'               => ['nullable', 'array'],
			'attributes.*.name'        => ['string'],
			'attributes.*.value'       => ['nullable', 'string'],
			'css_class'                => ['nullable', 'string', 'max:255'],
			'roles'                    => ['nullable', 'array'],
			'roles.*.name'             => ['string'],
			'permissions'              => ['nullable', 'array'],
			'permissions.*.name'       => ['string'],
			'description'              => ['nullable', 'string', 'max:255'],
			'active'                   => ['boolean'],
		];
		
		$type = $this->input('type');
		
		if (in_array($type, ['link', 'button', 'title'])) {
			$rules['label'][] = 'required';
		}
		if (in_array($type, ['link', 'button'])) {
			$rules['url_type'][] = 'required';
			
			$urlType = $this->input('url_type');
			
			if ($urlType == 'route') {
				$rules['route_name'][] = 'required';
			}
			if (in_array($urlType, ['internal', 'external'])) {
				$rules['url'][] = 'required';
			}
		}
		
		return $rules;
	}
	
	public function withValidator($validator): void
	{
		/** @var Validator $validator */
		$validator->after(function ($validator) {
			/** @var Validator $validator */
			
			// Validate parent belongs to same menu
			$parentId = $this->input('parent_id');
			if (!empty($parentId)) {
				$menuId = $this->input('menu_id');
				
				$parent = MenuItem::find($parentId);
				if (!empty($parent) && $parent->menu_id != $menuId) {
					$validator->errors()->add('parent_id', 'Parent item must belong to the same menu.');
				}
				
				$updateMethods = ['PUT', 'PATCH', 'UPDATE'];
				if (in_array($this->method(), $updateMethods)) {
					$editingMenuItemId = request()->route()->parameter('submenu_item');
					$editingMenuItemId = request()->route()->parameter('menu_item', $editingMenuItemId);
					
					if (!empty($editingMenuItemId)) {
						$menuItem = MenuItem::find($editingMenuItemId);
						if ($this->wouldCreateCircularReference($menuItem, $parentId)) {
							$validator->errors()->add('parent_id', 'Cannot create circular reference.');
						}
					}
				}
			}
			
			$urlType = $this->input('url_type');
			$routeName = $this->input('route_name');
			
			if ($urlType === 'route' && !empty($routeName)) {
				// Validate page route if it's a page route
				if (str_starts_with($routeName, 'page.') && !$this->validatePageRoute($routeName)) {
					$validator->errors()->add('route_name', 'Selected page does not exist or is not active.');
				}
				
				// Validate category route if it's a category route
				if (str_starts_with($routeName, 'category.') && !$this->validateCategoryRoute($routeName)) {
					$validator->errors()->add('route_name', 'Selected category does not exist or is not active.');
				}
			}
			
			// Custom validation using repeater handler
			// ---
			// Validate "route_parameters"
			$routeParameters = $this->input('route_parameters', []);
			$errors = $this->repeaterHandler->validateRepeaterData(
				data: $routeParameters,
				requiredFields: ['name'],
				minItems: 0,
				maxItems: 10
			);
			
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$validator->errors()->add('route_parameters', $error);
				}
			}
			
			// Validate "conditions"
			$conditions = $this->input('conditions', []);
			$errors = $this->repeaterHandler->validateRepeaterData(
				data: $conditions,
				requiredFields: ['type'],
				minItems: 0,
				maxItems: 2
			);
			
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$validator->errors()->add('conditions', $error);
				}
			}
			
			// Validate "html_attributes"
			$htmlAttributes = $this->input('html_attributes', []);
			$errors = $this->repeaterHandler->validateRepeaterData(
				data: $htmlAttributes,
				requiredFields: ['name'],
				minItems: 0,
				maxItems: 10
			);
			
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$validator->errors()->add('html_attributes', $error);
				}
			}
			
			// Validate "roles"
			$roles = $this->input('roles', []);
			$errors = $this->repeaterHandler->validateRepeaterData(
				data: $roles,
				requiredFields: ['name'],
				minItems: 0,
				maxItems: 10
			);
			
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$validator->errors()->add('roles', $error);
				}
			}
			
			// Validate "permissions"
			$permissions = $this->input('permissions', []);
			$errors = $this->repeaterHandler->validateRepeaterData(
				data: $permissions,
				requiredFields: ['name'],
				minItems: 0,
				maxItems: 10
			);
			
			if (!empty($errors)) {
				foreach ($errors as $error) {
					$validator->errors()->add('permissions', $error);
				}
			}
		});
	}
	
	/**
	 * Handle a passed validation attempt.
	 *
	 * @return void
	 */
	protected function passedValidation()
	{
		$input = $this->all();
		
		// route_parameters
		$input['route_parameters'] = $this->repeaterHandler->extractFromRequest($this, 'route_parameters');
		
		// conditions
		$input['conditions'] = $this->repeaterHandler->extractFromRequest($this, 'conditions');
		
		// html_attributes
		$input['html_attributes'] = $this->repeaterHandler->extractFromRequest($this, 'html_attributes');
		
		// permissions
		$input['permissions'] = $this->repeaterHandler->extractFromRequest($this, 'permissions');
		
		// roles
		$input['roles'] = $this->repeaterHandler->extractFromRequest($this, 'roles');
		
		request()->merge($input); // Required!
		$this->merge($input);
	}
	
	public function attributes(): array
	{
		$attributes = [
			'menu_id'                  => trans('menu.menu'),
			'parent_id'                => trans('menu.parent_menu_item'),
			'type'                     => trans('menu.type'),
			'icon'                     => trans('menu.icon'),
			'label'                    => trans('menu.label'),
			'url_type'                 => trans('menu.url_type'),
			'route_name'               => trans('menu.route_name'),
			'route_parameters'         => trans('menu.route_parameters'),
			'route_parameters.*.name'  => trans('menu.route_parameters_name'),
			'route_parameters.*.value' => trans('menu.route_parameters_value'),
			'url'                      => trans('menu.url'),
			'target'                   => trans('menu.target'),
			'btn_class'                => trans('menu.btn_class'),
			'conditions'               => trans('menu.conditions'),
			'conditions.*.type'        => trans('menu.conditions_type'),
			'conditions.*.value'       => trans('menu.conditions_value'),
			'html_attributes'          => trans('menu.html_attributes'),
			'html_attributes.*.name'   => trans('menu.html_attributes_name'),
			'html_attributes.*.value'  => trans('menu.html_attributes_value'),
			'css_class'                => trans('menu.css_class'),
			'roles'                    => trans('menu.roles'),
			'roles.*.name'             => trans('menu.roles_name'),
			'permissions'              => trans('menu.permissions'),
			'permissions.*.name'       => trans('menu.permissions_name'),
			'description'              => trans('menu.description'),
			'active'                   => trans('menu.active'),
		];
		
		// OPTIONAL
		// ---
		// Dynamic labels for route parameters
		$routeParameters = $this->input('route_parameters');
		if (!empty($routeParameters)) {
			foreach ($routeParameters as $index => $param) {
				$attributes["route_parameters.{$index}.name"] = trans('menu.route_parameters_index_name', ['index' => $index + 1]);
				$attributes["route_parameters.{$index}.value"] = trans('menu.route_parameters_index_value', ['index' => $index + 1]);
			}
		}
		
		// Dynamic labels for conditions
		$conditions = $this->input('conditions');
		if (!empty($conditions)) {
			foreach ($conditions as $index => $condition) {
				$attributes["conditions.{$index}.type"] = trans('menu.conditions_index_type', ['index' => $index + 1]);
				$attributes["conditions.{$index}.value"] = trans('menu.conditions_index_value', ['index' => $index + 1]);
			}
		}
		
		// Dynamic labels for HTML attributes
		$htmlAttributes = $this->input('html_attributes');
		if (!empty($htmlAttributes)) {
			foreach ($htmlAttributes as $index => $attr) {
				$attributes["html_attributes.{$index}.name"] = trans('menu.html_attributes_index_name', ['index' => $index + 1]);
				$attributes["html_attributes.{$index}.value"] = trans('menu.html_attributes_index_value', ['index' => $index + 1]);
			}
		}
		
		// Dynamic labels for roles
		$roles = $this->input('roles');
		if ($roles) {
			foreach ($roles as $index => $role) {
				$attributes["roles.{$index}.name"] = trans('menu.roles_index_name', ['index' => $index + 1]);
			}
		}
		
		// Dynamic labels for permissions
		$permissions = $this->input('permissions');
		if ($permissions) {
			foreach ($permissions as $index => $permission) {
				$attributes["permissions.{$index}.name"] = trans('menu.permissions_index_name', ['index' => $index + 1]);
			}
		}
		
		return $attributes;
	}
	
	// PRIVATE
	
	private function wouldCreateCircularReference($menuItem, $parentId): bool
	{
		$childrenIds = $this->getChildrenIds($menuItem);
		
		return (in_array($parentId, $childrenIds) || $parentId == $menuItem->id);
	}
	
	private function getChildrenIds($menuItem): array
	{
		$children = [];
		$this->collectChildrenIds($menuItem, $children);
		
		return $children;
	}
	
	private function collectChildrenIds($menuItem, array &$children): void
	{
		foreach ($menuItem->children as $child) {
			$children[] = $child->id;
			$this->collectChildrenIds($child, $children);
		}
	}
	
	/**
	 * Validate if a page route exists
	 *
	 * @param string $routeKey
	 * @return bool
	 */
	private function validatePageRoute(string $routeKey): bool
	{
		$prefix = 'page.';
		
		if (!str_starts_with($routeKey, $prefix)) {
			return false;
		}
		
		// Remove 'page.' prefix
		$slug = substr($routeKey, strlen($prefix));
		
		if (!class_exists(Page::class)) {
			return false;
		}
		
		try {
			return Page::query()->active()->where('slug', $slug)->exists();
		} catch (\Exception $e) {
			return false;
		}
	}
	
	/**
	 * Validate if a category route exists
	 *
	 * @param string $routeKey
	 * @return bool
	 */
	private function validateCategoryRoute(string $routeKey): bool
	{
		$prefix = 'category.';
		
		if (!str_starts_with($routeKey, $prefix)) {
			return false;
		}
		
		// Remove 'category.' prefix
		$slug = substr($routeKey, strlen($prefix));
		
		if (!class_exists(Category::class)) {
			return false;
		}
		
		try {
			return Category::query()->active()->where('slug', $slug)->exists();
		} catch (\Exception $e) {
			return false;
		}
	}
}
