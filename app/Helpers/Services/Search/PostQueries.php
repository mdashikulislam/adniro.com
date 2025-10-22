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

namespace App\Helpers\Services\Search;

use App\Enums\PostType;
use App\Helpers\Common\DBUtils;
use App\Helpers\Common\PaginationHelper;
use App\Helpers\Services\Search\Traits\Filters;
use App\Helpers\Services\Search\Traits\GroupBy;
use App\Helpers\Services\Search\Traits\Having;
use App\Helpers\Services\Search\Traits\OrderBy;
use App\Helpers\Services\Search\Traits\Relations;
use App\Helpers\Services\Search\Traits\Select;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostResource;
use App\Jobs\GeneratePostCollectionThumbnails;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostQueries
{
	use Select, Relations, Filters, GroupBy, Having, OrderBy;
	
	private static bool $dbModeStrict = false;
	protected static int $cacheExpiration = 300; // 5mn (60s * 5)
	
	public mixed $country;
	public mixed $lang;
	
	// Default Inputs (op, perPage, cacheExpiration & orderBy)
	// These inputs need to have a default value
	protected array $input = [];
	
	// Pre-Search Objects
	private array $preSearch;
	public mixed $cat = null;
	public mixed $city = null;
	public mixed $citiesIds = [];
	public mixed $admin = null;
	
	// Default Columns Selected
	protected array $select = [];
	protected array $groupBy = [];
	protected array $having = [];
	protected array $orderBy = [];
	
	protected Builder $posts;
	protected string $postsTable;
	
	// 'queryStringKey' => ['name' => 'column', 'order' => 'direction']
	public array $orderByParametersFields = [];
	
	/**
	 * PostQueries constructor.
	 *
	 * @param array $input
	 * @param array $preSearch
	 */
	public function __construct(array $input = [], array $preSearch = [])
	{
		self::$dbModeStrict = config('database.connections.' . config('database.default') . '.strict');
		
		// Input
		$this->input = $this->bindValidValuesForInput($input);
		
		// Pre-Search (category, city or admin. division)
		$this->cat = !empty($preSearch['cat']) ? $preSearch['cat'] : null;
		$this->city = !empty($preSearch['city']) ? $preSearch['city'] : null;
		$this->citiesIds = !empty($preSearch['citiesIds']) ? $preSearch['citiesIds'] : [];
		$this->admin = !empty($preSearch['admin']) ? $preSearch['admin'] : null;
		
		// Save preSearch
		$this->preSearch = $preSearch;
		
		// Init. Builder
		$this->posts = Post::query();
		$this->postsTable = (new Post())->getTable();
		
		// Add Default Select Columns
		$this->setSelect();
		
		// Relations
		$this->setRelations();
	}
	
	/**
	 * Get the results
	 *
	 * @return array
	 */
	public function fetch(): array
	{
		// Apply Requested Filters
		$this->applyFilters();
		
		// Apply Aggregation & Reorder Statements
		$this->applyGroupBy();
		$this->applyHaving();
		$this->applyOrderBy();
		
		$isListingTypeShowingEnabled = (config('settings.listing_form.show_listing_type') == '1');
		
		// Get Count PostTypes Results
		$count = $isListingTypeShowingEnabled ? $this->countFetch() : [];
		
		// Get Results
		$perPage = $this->input['perPage'] ?? null;
		$posts = $this->posts->paginate((int)$perPage);
		$posts = PaginationHelper::adjustSides($posts);
		
		// Generate listings images thumbnails
		GeneratePostCollectionThumbnails::dispatch($posts);
		
		// Remove Distance from Request
		$this->input = $this->removeDistanceFromRequest($this->input);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$posts = setPaginationBaseUrl($posts);
		
		// Append request queries in the pagination links
		$query = request()->query();
		$query = collect($query)->map(fn ($item) => (is_null($item) ? '' : $item))->toArray();
		$posts->appends($query);
		
		// Get Count Results
		$totalCountId = 0;
		$count[$totalCountId] = $posts->total();
		if ($isListingTypeShowingEnabled) {
			$postTypeId = $this->input['type'] ?? null;
			if (!empty($postTypeId) && isset($count[$postTypeId])) {
				$total = 0;
				foreach ($count as $typeId => $countItems) {
					if ($typeId != $totalCountId) {
						$total += $countItems;
					}
				}
				$count[$totalCountId] = $total;
			}
		}
		
		// Wrap the listings for API calls
		$postsCollection = new EntityCollection(PostResource::class, $posts, $this->input);
		$message = ($posts->count() <= 0) ? t('no_posts_found') : null;
		$postsResult = $postsCollection->toResponse(request())->getData(true);
		
		// Retrieve user identifiers values
		$userId = $this->input['userId'] ?? null;
		$username = $this->input['username'] ?? null;
		$searchBasedOnUser = (!empty($userId) || !empty($username));
		
		// Add 'user' object in preSearch (If available)
		$this->preSearch['user'] = null;
		if ($searchBasedOnUser) {
			$this->preSearch['user'] = data_get($postsResult, 'data.0.user');
		}
		
		$this->preSearch['tag'] = $this->input['tag'] ?? null;
		
		$this->preSearch['distance'] = [
			'default' => self::$defaultDistance,
			'current' => self::$distance,
			'max'     => self::$maxDistance,
		];
		
		// Results Data
		return [
			'message'   => $message,
			'count'     => $count,
			'posts'     => $postsResult,
			'distance'  => self::$distance,
			'preSearch' => $this->preSearch,
			'tags'      => $this->getPostsTags($posts),
		];
	}
	
	/**
	 * Count the results
	 *
	 * @return array
	 */
	private function countFetch(): array
	{
		$count = [];
		
		$postTypes = PostType::all();
		if (empty($postTypes)) {
			return $count;
		}
		
		// Count entries by post type
		$pattern = '/`post_type_id`\s*=\s*[\d\']+\s+/ui';
		foreach ($postTypes as $postType) {
			$postTypeId = data_get($postType, 'id');
			$iPosts = clone $this->posts;
			
			$sql = DBUtils::getRealSql($iPosts->toSql(), $iPosts->getBindings());
			
			if (preg_match($pattern, $sql)) {
				$sql = preg_replace($pattern, '`post_type_id` = ' . $postTypeId . ' ', $sql);
			} else {
				$iPosts->where('post_type_id', $postTypeId);
				$sql = DBUtils::getRealSql($iPosts->toSql(), $iPosts->getBindings());
			}
			
			try {
				$sql = 'SELECT COUNT(*) AS total FROM (' . $sql . ') AS x';
				$result = DB::select($sql);
			} catch (Throwable $e) {
				// dd($e->getMessage()); // Debug!
				$result = null;
			}
			
			$count[$postTypeId] = isset($result[0]) ? (int)$result[0]->total : 0;
		}
		
		return $count;
	}
	
	/**
	 * Get found listings' tags (per page)
	 *
	 * @param $posts
	 * @return array|string|null
	 */
	private function getPostsTags($posts): array|string|null
	{
		$isListingsTagsShowingEnabled = (config('settings.listings_list.show_listings_tags') == '1');
		
		if (!$isListingsTagsShowingEnabled) {
			return null;
		}
		
		if ($posts->count() > 0) {
			$tags = [];
			foreach ($posts as $post) {
				if (!empty($post->tags)) {
					$tags = array_merge($tags, $post->tags);
				}
			}
			
			return tagCleaner($tags);
		}
		
		return null;
	}
	
	/**
	 * Bind valid values for the input's elements
	 *
	 * @param array $array
	 * @return array
	 */
	private function bindValidValuesForInput(array $array = []): array
	{
		// cacheExpiration
		$cacheExpiration = $array['cacheExpiration'] ?? null;
		$cacheExpirationIsValid = !empty($cacheExpiration) && is_numeric($cacheExpiration);
		if (!$cacheExpirationIsValid) {
			$array['cacheExpiration'] = getGlobalCacheTtl();
		}
		
		// op (operation)
		$array['op'] = $array['op'] ?? 'default';
		
		// perPage
		$perPage = $array['perPage'] ?? null;
		$array['perPage'] = getNumberOfItemsPerPage('posts', $perPage);
		
		// orderBy
		// Avoid to set an arbitrary orderBy value (set value to null instead)
		// $orderBy = $array['orderBy'] ?? null;
		// $array['orderBy'] = !empty($orderBy) ? $orderBy : null;
		
		return $array;
	}
}
