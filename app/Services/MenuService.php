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

namespace App\Services;

use App\Helpers\Common\PaginationHelper;
use App\Http\Requests\Admin\MenuRequest;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\MenuResource;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Throwable;

class MenuService extends BaseService
{
	/**
	 * List menus
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$perPage = getNumberOfItemsPerPage('menus', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$sort = $params['sort'] ?? [];
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.menus',
			'locale' => $locale,
		]);
		
		// Cached Query
		$menus = caching()->remember(Menu::class, $cacheParams, function () use (
			$perPage, $embed, $sort
		) {
			$menus = Menu::query();
			
			if (in_array('rootMenuItems', $embed)) {
				$menus->with('rootMenuItems');
			}
			if (in_array('menuItems', $embed)) {
				$menus->with('menuItems');
			}
			
			// Sorting
			$menus = $this->applySorting($menus, ['lft', 'name'], $sort);
			
			$menus = $menus->paginate($perPage);
			
			return PaginationHelper::adjustSides($menus);
		});
		
		/** @var \Illuminate\Pagination\LengthAwarePaginator $menus */
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$menus = setPaginationBaseUrl($menus);
		
		$resourceCollection = new EntityCollection(MenuResource::class, $menus, $params);
		
		$message = ($menus->count() <= 0) ? t('no_menus_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get menu
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id, array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action' => 'get.menu',
			'id'     => $id,
			'locale' => $locale,
		]);
		
		$menu = caching()->remember(Menu::class, $cacheParams, function () use ($embed, $id) {
			$menu = Menu::query()
				->where('id', '=', $id)
				->active();
			
			if (in_array('rootMenuItems', $embed)) {
				$menu->with('rootMenuItems');
			}
			if (in_array('menuItems', $embed)) {
				$menu->with('menuItems');
			}
			
			return $menu->first();
		});
		
		return $this->returnResource($menu, $params);
	}
	
	/**
	 * Get menu (by location)
	 *
	 * @param string $location
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntryByLocation(string $location, array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'   => 'get.menu',
			'location' => $location,
			'active'   => true,
			'locale'   => $locale,
		]);
		
		$menu = caching()->remember(Menu::class, $cacheParams, function () use ($embed, $location) {
			$menu = Menu::query()
				->forLocation($location)
				->active();
			
			if (in_array('rootMenuItems', $embed)) {
				$menu->with(['rootMenuItems']);
				if (in_array('children', $embed)) {
					$menu->with(['rootMenuItems.children']);
				}
			}
			if (in_array('menuItems', $embed)) {
				$menu->with('menuItems');
			}
			
			return $menu->first();
		});
		
		return $this->returnResource($menu, $params);
	}
	
	/**
	 * Store menu
	 *
	 * @param \App\Http\Requests\Admin\MenuRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(MenuRequest $request): JsonResponse
	{
		/** @var User $authUser */
		$authUser = request()->user() ?? auth(getAuthGuard())->user();
		
		// Logged-in user not found
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Logged-in user is not an admin user
		if (!$authUser->can(Permission::getStaffPermissions())) {
			return apiResponse()->forbidden();
		}
		
		// Check if menu already exists for this location
		$location = $request->input('location');
		if (!empty($location)) {
			$existingMenu = Menu::query()->forLocation($location)->first();
			if ($existingMenu) {
				throw new \InvalidArgumentException("A menu already exists for location: {$location}. Each location can only have one menu.");
			}
		}
		
		// New Menu
		$menu = new Menu();
		$input = $request->only($menu->getFillable());
		foreach ($input as $key => $value) {
			if ($request->has($key)) {
				$menu->{$key} = $value;
			}
		}
		
		// Save
		$menu->save();
		
		$data = [
			'success' => true,
			'message' => t('entry_created_successfully'),
			'result'  => (new MenuResource($menu))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Update menu
	 *
	 * @param $id
	 * @param \App\Http\Requests\Admin\MenuRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update($id, MenuRequest $request): JsonResponse
	{
		/** @var User $authUser */
		$authUser = request()->user() ?? auth(getAuthGuard())->user();
		
		// Logged-in user not found
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Logged-in user is not an admin user
		if (!$authUser->can(Permission::getStaffPermissions())) {
			return apiResponse()->forbidden();
		}
		
		/** @var Menu $menu */
		$menu = Menu::query()->where('id', $id)->first();
		
		if (empty($menu)) {
			return apiResponse()->notFound(t('menu_not_found'));
		}
		
		$oldLocation = $menu->location;
		
		// Check if trying to change to a location that's already taken
		$location = $request->input('location');
		if (!empty($location) && $location !== $oldLocation) {
			$existingMenu = Menu::query()
				->forLocation($location)
				->where('id', '!=', $menu->id)
				->first();
			if ($existingMenu) {
				throw new \InvalidArgumentException("A menu already exists for location: {$location}. Each location can only have one menu.");
			}
		}
		
		// Update the Menu
		$input = $request->only($menu->getFillable());
		try {
			foreach ($input as $key => $value) {
				if ($request->has($key)) {
					$menu->{$key} = $value;
				}
			}
			$menu->save();
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
		
		$data = [
			'success' => true,
			'message' => t('entry_updated_successfully'),
			'result'  => (new MenuResource($menu))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete menu(s)
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		/** @var User $authUser */
		$authUser = request()->user() ?? auth(getAuthGuard())->user();
		
		// Logged-in user not found
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Logged-in user is not an admin user
		if (!$authUser->can(Permission::getStaffPermissions())) {
			return apiResponse()->forbidden();
		}
		
		$data = [
			'success' => false,
			'message' => t('no_deletion_is_done'),
			'result'  => null,
		];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $entryId) {
			$menu = Menu::query()->where('id', $entryId)->first();
			
			if (!empty($menu)) {
				$res = $menu->delete();
			}
		}
		
		// Message
		if ($res) {
			$count = count($ids);
			$entityLabel = ($count > 1) ? t('menus') : t('menu');
			
			$data['success'] = true;
			$data['message'] = t('x_entries_deleted_successfully_for', ['count' => $count, 'entity' => $entityLabel]);
		}
		
		return apiResponse()->json($data);
	}
	
	// PRIVATE
	
	/**
	 * @param $menu
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	private function returnResource($menu, array $params = []): JsonResponse
	{
		abort_if(empty($menu), 404, t('menu_not_found'));
		
		$resource = new MenuResource($menu, $params);
		
		return apiResponse()->withResource($resource);
	}
}
