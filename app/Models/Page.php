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

use App\Casts\DateTimeCast;
use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Common\JsonUtils;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\SpatieTranslatable\HasTranslations;
use App\Jobs\GenerateThumbnail;
use App\Models\Scopes\ActiveScope;
use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\PageTrait;
use App\Observers\PageObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

#[ObservedBy([PageObserver::class])]
#[ScopedBy([ActiveScope::class])]
class Page extends BaseModel
{
	use Crud, AppendsTrait, Sluggable, SluggableScopeHelpers, HasTranslations;
	use PageTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'pages';
	
	/**
	 * @var array<int, string>
	 */
	protected $appends = ['image_url', 'url', 'is_external'];
	
	/**
	 * The attributes that aren't mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $guarded = ['id'];
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'parent_id',
		'type',
		'name',
		'slug',
		'image_path',
		'title',
		'content',
		'external_link',
		'name_color',
		'title_color',
		'target',
		'seo_title',
		'seo_description',
		'seo_keywords',
		'active',
		'lft',
		'rgt',
		'depth',
	];
	
	/**
	 * @var array<int, string>
	 */
	public array $translatable = ['name', 'title', 'content', 'seo_title', 'seo_description', 'seo_keywords'];
	
	// Optional: Specify related models that should be invalidated when this model changes
	protected array $invalidatesCacheFor = [
		MenuItem::class,
	];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'lft'        => 'integer',
			'rgt'        => 'integer',
			'depth'      => 'integer',
			'active'     => 'boolean',
			'created_at' => DateTimeCast::class,
			'updated_at' => DateTimeCast::class,
		];
	}
	
	public function hasChildren(): bool
	{
		return $this->children()->active()->count() > 0;
	}
	
	public function getActiveChildren()
	{
		return $this->children()->active()->get();
	}
	
	// Nested set helpers
	public function getAncestors(): Collection|SupportCollection
	{
		$lft = (int)($this->lft ?? 0);
		$rgt = (int)($this->rgt ?? 0);
		
		if ($lft <= 0 && $rgt <= 0) {
			return collect();
		}
		
		$cacheParams = [
			'action'  => 'get.pages',
			'lft'     => "< {$lft}",
			'rgt'     => "> {$rgt}",
			'orderBy' => 'lft',
		];
		
		return caching()->remember(static::class, $cacheParams, function () use ($lft, $rgt) {
			return static::query()
				->where('lft', '<', $lft)
				->where('rgt', '>', $rgt)
				->orderBy('lft')
				->get();
		});
	}
	
	public function getDescendants(): Collection|SupportCollection
	{
		$lft = (int)($this->lft ?? 0);
		$rgt = (int)($this->rgt ?? 0);
		
		if ($lft <= 0 && $rgt <= 0) {
			return collect();
		}
		
		$cacheParams = [
			'action'  => 'get.pages',
			'lft'     => "> {$lft}",
			'rgt'     => "< {$rgt}",
			'orderBy' => 'lft',
		];
		
		return caching()->remember(static::class, $cacheParams, function () use ($lft, $rgt) {
			return static::query()
				->where('lft', '>', $lft)
				->where('rgt', '<', $rgt)
				->orderBy('lft')
				->get();
		});
	}
	
	public function getBreadcrumbs(): Collection|SupportCollection
	{
		return $this->getAncestors()->push($this);
	}
	
	// Get hierarchical name for display
	public function getHierarchicalName(): string
	{
		$ancestors = $this->getAncestors();
		$names = $ancestors->pluck('name')->toArray();
		$names[] = $this->name ?? null;
		
		return implode(' → ', $names);
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function parent(): BelongsTo
	{
		return $this->belongsTo(Page::class, 'parent_id');
	}
	
	public function children(): HasMany
	{
		return $this->hasMany(Page::class, 'parent_id')->orderBy('lft');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	#[Scope]
	protected function active(Builder $query): void
	{
		$query->where('active', 1);
	}
	
	#[Scope]
	protected function type(Builder $query, $type): void
	{
		$query->where('type', $type)->orderByDesc('id');
	}
	
	#[Scope]
	protected function roots(Builder $query): void
	{
		$query->whereNull('parent_id');
	}
	
	#[Scope]
	protected function withSlug(Builder $query): void
	{
		$query->whereNotNull('slug')->where('slug', '!=', '');
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function name(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->attributes['name']) && !JsonUtils::isJson($this->attributes['name'])) {
					return $this->attributes['name'];
				}
				
				return $value;
			},
		);
	}
	
	protected function title(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->attributes['title']) && !JsonUtils::isJson($this->attributes['title'])) {
					return $this->attributes['title'];
				}
				
				return $value;
			},
			set: function ($value) {
				$name = $this->name ?? null;
				
				return empty($value) ? $name : $value;
			},
		);
	}
	
	protected function content(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->attributes['content']) && !JsonUtils::isJson($this->attributes['content'])) {
					return $this->attributes['content'];
				}
				
				return $value;
			},
		);
	}
	
	protected function imagePath(): Attribute
	{
		return Attribute::make(
			get: function ($value, $attributes) {
				if (empty($value)) {
					$value = $attributes['image_path'] ?? null;
				}
				
				if (empty($value)) {
					return null;
				}
				
				$disk = StorageDisk::getDisk();
				if (!$disk->exists($value)) {
					$value = null;
				}
				
				return $value;
			},
			set: function ($value) {
				// Generate the page's image thumbnails
				GenerateThumbnail::dispatchSync($value, false, 'bg-header');
				
				return $value;
			},
		);
	}
	
	protected function imageUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				$filePath = $this->image_path ?? null;
				$resizeOptionsName = 'bg-header';
				
				// Add the page's image thumbnails generation in queue
				GenerateThumbnail::dispatch($filePath, false, $resizeOptionsName);
				
				return thumbParam($filePath, false)->setOption($resizeOptionsName)->url();
			},
		);
	}
	
	protected function url(): Attribute
	{
		return Attribute::make(
			get: function () {
				if (!empty($this->external_link)) {
					return $this->external_link;
				}
				
				return urlGen()->page($this);
			},
		);
	}
	
	protected function isExternal(): Attribute
	{
		return Attribute::make(
			get: fn () => !empty($this->external_link),
		);
	}
	
	protected function seoTitle(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->seo_title ?? $this->title ?? $this->name ?? null,
		);
	}
	
	protected function seoDescription(): Attribute
	{
		$content = $this->content ?? '';
		$content = strip_tags($content);
		
		return Attribute::make(
			get: fn () => $this->seo_description ?? str($content)->limit(160)->toString(),
		);
	}
	
	protected function seoKeywords(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->seo_keywords ?? '',
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
