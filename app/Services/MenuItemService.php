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
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Throwable;

class MenuItemService extends BaseService
{
	/** adniro: how long a finished site menu is kept (getSiteMenu) */
	private const SITE_MENU_SECONDS = 600;
	
	/** adniro: entries whose label or link is the member's own: unread chats, where they post a listing */
	private const PERSONAL_ROUTES = [
		'chat.index', 'account.messages', 'listing.create.ms.showForm', 'listing.create.ss.showForm',
	];

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
		return $this->entriesResponse($this->cachedEntries($location, $parentId, $params), $params);
	}
	
	/**
	 * adniro: a site menu (header, footer) ready for the views, kept in the cache.
	 *
	 * The menu's entries were already cached, but as models: on every page they went through the API layer to
	 * JSON and back again, which was about half of the time of an otherwise empty page. The finished menu is
	 * now kept instead, once per case that changes what it shows:
	 * - the language, the country and the site's address (labels and links);
	 * - a guest or a member (entries can be for one or the other);
	 * - which entry is the page being looked at (it is marked as current).
	 * - the settings its links and looks are drawn from (header, footer, sign-in pop-up, who may post).
	 * It is forgotten with the menu entries themselves (same cache group), and after ten minutes in any case.
	 * A member's menu is built each time, as before, when one of its entries is personal: it shows the
	 * member's name or their unread chat count, links to where this member posts a listing, or depends on a
	 * role or a permission (isPersonalEntry). On a live site a menu is only kept for the site's own address.
	 *
	 * @param string $location
	 * @param array $params
	 * @return array
	 */
	public function getSiteMenu(string $location, array $params = []): array
	{
		$menuItems = $this->cachedEntries($location, null, $params);
		$build = fn () => getServiceData($this->entriesResponse($menuItems, $params));
		
		$visitor = $this->visitorKind($menuItems);
		if ($visitor === null || !$this->isOnOwnHost()) {
			return $build();
		}
		
		$cacheParams = array_merge($params, [
			'action'   => 'get.siteMenu',
			'location' => $location,
			'locale'   => app()->getLocale(),
			'country'  => config('country.code'),
			'site'     => url('/'),
			'visitor'  => $visitor,
			'current'  => implode(',', $this->currentEntryIds($menuItems)),
			// The settings an entry's link, classes and label are drawn from: a change in the admin panel
			// shows on the next page, as it did when the menu was built each time
			'settings' => md5(json_encode([
				config('settings.header'),
				config('settings.footer'),
				config('settings.auth.open_login_in_modal'),
				config('settings.listing_form.guest_can_submit_listings'),
				config('settings.listing_form.mandatory_package_selection'),
			])),
		]);
		
		return caching()->remember(MenuItem::class, $cacheParams, $build, self::SITE_MENU_SECONDS);
	}
	
	/**
	 * adniro: "guest" or "member" when every visitor of that kind sees the same menu; null for a member whose
	 * menu has a personal entry, which differs from one member to the next.
	 *
	 * @param iterable $menuItems
	 * @return string|null
	 */
	private function visitorKind(iterable $menuItems): ?string
	{
		if (!auth()->check()) {
			return 'guest';
		}
		
		foreach ($this->withChildren($menuItems) as $menuItem) {
			if ($this->isPersonalEntry($menuItem)) {
				return null;
			}
		}
		
		return 'member';
	}
	
	/**
	 * adniro: whether an entry is not the same for every member: it asks about a role, a permission or
	 * impersonation; its label carries something of the member's own (their name, their unread chat count:
	 * see MenuItem::formattedLabel()); or its link depends on the member (where they post a listing depends
	 * on their package: getCreateListingLinkInfo()). Anything personal added to a menu entry must be named
	 * here too.
	 *
	 * @param \App\Models\MenuItem $menuItem
	 * @return bool
	 */
	private function isPersonalEntry(MenuItem $menuItem): bool
	{
		$conditions = array_keys((array)($menuItem->conditions ?? []));
		
		return !empty($menuItem->permissions)
			|| !empty($menuItem->roles)
			|| !empty(array_intersect($conditions, ['role', 'permission', 'impersonating', 'authentic']))
			|| str_contains((string)$menuItem->getRawOriginal('label'), '{user.')
			|| in_array($menuItem->route_name, self::PERSONAL_ROUTES, true);
	}
	
	/**
	 * adniro: a menu's links carry the address the visitor came by. On a live site only the site's own
	 * address (with or without "www") gets a kept menu, so made-up host names cannot fill the cache.
	 *
	 * @return bool
	 */
	private function isOnOwnHost(): bool
	{
		if (!app()->isProduction()) {
			return true;
		}
		
		$withoutWww = fn (?string $host) => preg_replace('/^www\./', '', strtolower((string)$host));
		$ownHost = $withoutWww(parse_url((string)config('app.url'), PHP_URL_HOST) ?: '');
		
		return $ownHost !== '' && $withoutWww(request()->getHost()) === $ownHost;
	}
	
	/**
	 * adniro: the entries that stand for the page being looked at (usually none or one).
	 *
	 * @param iterable $menuItems
	 * @return array<int, int>
	 */
	private function currentEntryIds(iterable $menuItems): array
	{
		$ids = [];
		foreach ($this->withChildren($menuItems) as $menuItem) {
			if ($menuItem->is_current_page) {
				$ids[] = (int)$menuItem->id;
			}
		}
		sort($ids);
		
		return $ids;
	}
	
	/**
	 * adniro: the entries and, under each, the children the views show.
	 *
	 * @param iterable $menuItems
	 * @return \Generator
	 */
	private function withChildren(iterable $menuItems): \Generator
	{
		foreach ($menuItems as $menuItem) {
			// A cache store that keeps objects as they are (the "array" store) hands back entries the API
			// layer has already wrapped, once for each time they were used
			while ($menuItem instanceof JsonResource) {
				$menuItem = $menuItem->resource;
			}
			if (!$menuItem instanceof MenuItem) {
				continue;
			}
			
			yield $menuItem;
			foreach ($menuItem->relationLoaded('children') ? $menuItem->children : [] as $child) {
				if ($child instanceof MenuItem) {
					yield $child;
				}
			}
		}
	}
	
	/**
	 * The menu entries of a location, from the cache (adniro: taken out of getEntries(), unchanged)
	 *
	 * @param string $location
	 * @param int|null $parentId
	 * @param array $params
	 * @return \Illuminate\Pagination\LengthAwarePaginator
	 */
	private function cachedEntries(string $location, ?int $parentId = null, array $params = [])
	{
		$locale = config('app.locale');
		$perPage = getNumberOfItemsPerPage('menuItems', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$areNestedEntriesIncluded = castIntToBool($params['nestedIncluded'] ?? 0);
		$sort = $params['sort'] ?? [];

		// Page to load: an explicit 'page' param wins over the current URL's "?page=N",
		// and the page is part of the cache key. Otherwise a visit to any paginated URL
		// (e.g. /search?page=2) right after a cache invalidation would cache an empty
		// page of menu items under the key used by every other page.
		$page = (int)($params['page'] ?? 0);
		$page = ($page > 0) ? $page : (int)Paginator::resolveCurrentPage();
		$page = max($page, 1);

		// Cache Parameters
		$cacheParams = array_merge($params, [
			'action'   => 'get.menuItems',
			'location' => $location,
			'parentId' => $parentId,
			'locale'   => $locale,
			'page'     => $page,
		]);

		// Cached Query
		/** @var \Illuminate\Pagination\LengthAwarePaginator $menuItems */
		$menuItems = caching()->remember(MenuItem::class, $cacheParams, function () use (
			$perPage, $embed, $areNestedEntriesIncluded, $sort, $location, $page
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
			
			$menuItemsPaginator = $menuItems->paginate($perPage, ['*'], 'page', $page);
			
			return PaginationHelper::adjustSides($menuItemsPaginator);
		});
		
		return $menuItems;
	}
	
	/**
	 * The entries as the API answers them (adniro: taken out of getEntries(), unchanged)
	 *
	 * @param \Illuminate\Pagination\LengthAwarePaginator $menuItems
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	private function entriesResponse($menuItems, array $params): JsonResponse
	{
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
