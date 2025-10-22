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

namespace App\Models;

use App\Helpers\Common\CacheManager\InvalidatesCache;
use App\Models\Builders\HasGlobalBuilder;
use App\Models\Traits\Common\HasActiveColumn;
use App\Models\Traits\Common\HasVerifiedAtColumn;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
	use HasGlobalBuilder;
	use InvalidatesCache;
	use HasVerifiedAtColumn, HasActiveColumn;
	
	/**
	 * Enable/disable cache invalidation per model
	 *
	 * @var bool
	 */
	protected bool $enableCacheInvalidation = true;
	
	/**
	 * Indicates if the model should use UUID as primary key
	 *
	 * @var bool
	 */
	protected bool $useUuid = false;
	
	/**
	 * Indicates if the model should automatically generate slugs
	 *
	 * @var bool
	 */
	protected bool $autoSlug = false;
	
	/**
	 * The attribute to use for slug generation
	 *
	 * @var string
	 */
	protected string $slugSource = 'name';
	
	/**
	 * Boot the base model
	 *
	 * @return void
	 */
	protected static function boot()
	{
		parent::boot();
		
		static::creating(function ($model) {
			// Auto-generate UUID if enabled
			if ($model->useUuid) {
				if (empty($model->{$model->getKeyName()})) {
					$model->{$model->getKeyName()} = (string)Str::uuid();
				}
			}
			
			// Auto-generate slug if enabled
			if ($model->autoSlug) {
				if (empty($model->slug) && isset($model->{$model->slugSource})) {
					$model->slug = Str::slug($model->{$model->slugSource});
				}
			}
		});
		
		static::updating(function ($model) {
			// Update slug if source field changed and autoSlug is enabled
			if ($model->autoSlug) {
				if ($model->isDirty($model->slugSource)) {
					$model->slug = Str::slug($model->{$model->slugSource});
				}
			}
		});
	}
	
	/**
	 * Check if cache invalidation is enabled for this model
	 *
	 * @return bool
	 */
	public function shouldInvalidateCache(): bool
	{
		return $this->enableCacheInvalidation;
	}
	
	/**
	 * Scope to filter non-soft-deleted records
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @return void
	 */
	#[Scope]
	public function notTrashed(Builder $query): void
	{
		if (in_array(SoftDeletes::class, class_uses_recursive($this))) {
			$query->whereNull('deleted_at');
		}
	}
	
	/**
	 * Get the model's fillable attributes excluding timestamps
	 *
	 * @return array
	 */
	public function getFillableWithoutTimestamps(): array
	{
		return array_diff($this->getFillable(), [
			$this->getCreatedAtColumn(),
			$this->getUpdatedAtColumn(),
			'deleted_at',
		]);
	}
	
	/**
	 * Convert the model to an array with only fillable attributes
	 *
	 * @return array
	 */
	public function toFillableArray(): array
	{
		return $this->only($this->getFillable());
	}
}
