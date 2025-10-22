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

use App\Helpers\Common\Arr;
use App\Helpers\Common\Files\Storage\StorageDisk;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Builder;

class BaseService
{
	public int $cacheExpiration = 3600;     // In seconds (e.g.: 60 * 60 for 1h)
	public int $cookieExpiration = 3600;    // In seconds (e.g.: 60 * 60 for 1h)
	
	public int $perPage = 10;
	public Filesystem $disk;
	
	public function __construct()
	{
		// Cache Expiration Time
		$this->cacheExpiration = getGlobalCacheTtl();
		
		// Cookie Expiration Time
		$this->cookieExpiration = (int)config('settings.other.cookie_expiration');
		
		// Get the global items per page
		$this->perPage = (int)getNumberOfItemsPerPage(default: $this->perPage);
		
		// CommonTrait: Set the storage disk
		$this->setStorageDisk();
	}
	
	/**
	 * Set the storage disk
	 */
	private function setStorageDisk(): void
	{
		// Get the storage disk
		$this->disk = StorageDisk::getDisk();
	}
	
	/**
	 * Apply Sorting
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $builder
	 * @param array|null $fillable
	 * @param string|array|null $sort
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function applySorting(Builder $builder, ?array $fillable = [], string|array|null $sort = []): Builder
	{
		// Get fillable array (If not provided)
		if (empty($fillable) || !is_array($fillable)) {
			$fillable = $builder->getModel()->getFillable();
		}
		
		// Get the model primary key, and add it as fillable
		$primaryKey = $builder->getModel()->getKeyName();
		$fillable[] = $primaryKey;
		
		$sort = is_string($sort) ? explode(',', $sort) : $sort;
		
		if (is_array($sort)) {
			$sortAssoc = collect($sort)
				->mapWithKeys(function ($item) {
					$cleanItem = !empty($item) ? ltrim($item, '-') : '';
					
					return [$cleanItem => $item];
				})
				->reject(fn ($item, $key) => (empty($key) || empty($item)))
				->toArray();
			
			$sort = Arr::only($sortAssoc, $fillable);
			
			if (!empty($sort)) {
				foreach ($sort as $colWithOrder) {
					if (is_string($colWithOrder)) {
						$builder = $this->addOrderBy($builder, $colWithOrder);
					}
				}
			} else {
				$builder = $this->addOrderBy($builder, $primaryKey);
			}
		}
		
		return $builder;
	}
	
	/**
	 * Add an orderBy statement
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $builder
	 * @param string $columnWithOrder
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function addOrderBy(Builder $builder, string $columnWithOrder): Builder
	{
		$column = ltrim($columnWithOrder, '-');
		if (str_starts_with($columnWithOrder, '-')) {
			$builder->orderBy($column);
		} else {
			$builder->orderByDesc($column);
		}
		
		return $builder;
	}
	
	/**
	 * Cache control
	 *
	 * @param array $params
	 * @return void
	 */
	protected function updateCachingParameters(array $params = []): void
	{
		$cacheDriver = config('cache.default');
		$cacheExpiration = getGlobalCacheTtl();
		$noCache = castIntToBool($params['noCache'] ?? 0);
		
		if ($noCache) {
			config()->set('cache.default', 'array');
			$this->cacheExpiration = -1;
		}
		
		config()->set('cache.tmp.driver', $cacheDriver);
		config()->set('cache.tmp.expiration', $cacheExpiration);
	}
	
	/**
	 * Reset caching parameters
	 *
	 * @return void
	 */
	protected function resetCachingParameters(): void
	{
		config()->set('cache.default', config('cache.tmp.driver', 'file'));
		$this->cacheExpiration = (int)config('cache.tmp.expiration', 3600);
	}
}
