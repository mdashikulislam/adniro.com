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
use App\Http\Requests\Admin\MenuItemRequest;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Throwable;

class MenuItemService extends BaseService
{
	/**
	 * List menu items
	 *
	 * @param string $location
	 * @param int|null $parentId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(string $location, ?int $parentId = null, array $params = []): JsonResponse
	{
		$locale = config('app.locale');
		$perPage = getNumberOfItemsPerPage('menuItems', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$areNestedEntriesIncluded = castIntToBool($params['nestedIncluded'] ?? 0);
		$sort = $params['sort'] ?? [];
		
		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'   => 'get.menuItems',
			'location' => $location,
			'parentId' => $parentId,
			'locale'   => $locale,
		]);
		
		// Cached Query
		/** @var \Illuminate\Pagination\LengthAwarePaginator $menuItems */
		$menuItems = caching()->remember(MenuItem::class, $cacheParams, function () use (
			$perPage, $embed, $areNestedEntriesIncluded, $sort, $location
		) {
			$menuItems = MenuItem::query();
			$menuItems->whereHas('menu', fn (Builder $query) => $query->forLocation($location));
			$menuItems->with(['menu', 'page', 'category']);
			
			if (!empty($parentId)) {
				$menuItems->childrenOf($parentId);
			} else {
				if (!$areNestedEntriesIncluded) {
					$menuItems->roots();
				}
			}
			
			if (in_array('parent', $embed)) {
				$menuItems->with(['parent']);
			}
			if (in_array('ancestors', $embed)) {
				$menuItems->with(['ancestors']);
			}
			if (in_array('children', $embed)) {
				$menuItems->with(['children'])->withCount('children');
			}
			
			// Sorting
			$menuItems = $this->applySorting($menuItems, ['lft', 'label'], $sort);
			
			$menuItemsPaginator = $menuItems->paginate($perPage);
			
			return PaginationHelper::adjustSides($menuItemsPaginator);
		});
		
		$resourceCollection = new EntityCollection(MenuItemResource::class, $menuItems, $params);
		
		$message = ($menuItems->count() <= 0) ? t('no_menu_items_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Store menu item
	 *
	 * @param \App\Http\Requests\Admin\MenuItemRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(MenuItemRequest $request): JsonResponse
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
		
		// New Menu Item
		$menuItem = new MenuItem();
		$input = $request->only($menuItem->getFillable());
		foreach ($input as $key => $value) {
			if ($request->has($key)) {
				$menuItem->{$key} = $value;
			}
		}
		
		// Save
		$menuItem->save();
		
		$data = [
			'success' => true,
			'message' => t('entry_created_successfully'),
			'result'  => (new MenuItemResource($menuItem))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Update menu item
	 *
	 * @param $id
	 * @param \App\Http\Requests\Admin\MenuItemRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update($id, MenuItemRequest $request): JsonResponse
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
		
		/** @var MenuItem $menuItem */
		$menuItem = MenuItem::query()->where('id', $id)->first();
		
		if (empty($menuItem)) {
			return apiResponse()->notFound(t('menu_item_not_found'));
		}
		
		// Update the Menu
		$input = $request->only($menuItem->getFillable());
		try {
			foreach ($input as $key => $value) {
				if ($request->has($key)) {
					$menuItem->{$key} = $value;
				}
			}
			$menuItem->save();
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
		
		$data = [
			'success' => true,
			'message' => t('entry_updated_successfully'),
			'result'  => (new MenuItemResource($menuItem))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete menu item(s)
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
			$menuItem = MenuItem::query()->where('id', $entryId)->first();
			
			if (!empty($menuItem)) {
				$res = $menuItem->delete();
			}
		}
		
		// Message
		if ($res) {
			$count = count($ids);
			$entityLabel = ($count > 1) ? t('menu_items') : t('menu_item');
			
			$data['success'] = true;
			$data['message'] = t('x_entries_deleted_successfully_for', ['count' => $count, 'entity' => $entityLabel]);
		}
		
		return apiResponse()->json($data);
	}
	
	public function buildBreadcrumbs(string $location = 'header'): Collection
	{
		$menuItems = MenuItem::query()
			->whereHas('menu', fn (Builder $query) => $query->forLocation($location))
			->with('menu')
			->with('children')
			->orderBy('lft')
			->get();
		
		$breadcrumbs = collect();
		
		foreach ($menuItems as $item) {
			if ($item->active) {
				$breadcrumbs->push($item);
				break;
			}
			
			// Check children
			$activeChild = $this->findActiveChild($item);
			if ($activeChild) {
				$breadcrumbs->push($item);
				$breadcrumbs->push($activeChild);
				break;
			}
		}
		
		return $breadcrumbs;
	}
	
	protected function findActiveChild(MenuItem $item): ?MenuItem
	{
		foreach ($item->getVisibleChildren() as $child) {
			if ($child->active) {
				return $child;
			}
			
			$activeGrandchild = $this->findActiveChild($child);
			if ($activeGrandchild) {
				return $activeGrandchild;
			}
		}
		
		return null;
	}
}
