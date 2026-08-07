<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Models;

use App\Casts\DateTimeCast;
use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Jobs\GenerateThumbnail;
use App\Models\Scopes\ActiveScope;
use App\Models\Traits\BlogCategoryTrait;
use App\Models\Traits\Common\AppendsTrait;
use App\Observers\BlogCategoryObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([BlogCategoryObserver::class])]
#[ScopedBy([ActiveScope::class])]
class BlogCategory extends BaseModel
{
	use Crud, AppendsTrait, Sluggable, SluggableScopeHelpers;
	use BlogCategoryTrait;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'blog_categories';

	/**
	 * @var array<int, string>
	 */
	protected $appends = ['image_url', 'url'];

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
		'name',
		'slug',
		'image_path',
		'description',
		'seo_title',
		'seo_description',
		'seo_keywords',
		'active',
		'lft',
		'rgt',
		'depth',
	];

	// Related models that need to be invalidated when this model changes
	protected array $invalidatesCacheFor = [
		BlogPost::class,
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

	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function posts(): HasMany
	{
		return $this->hasMany(BlogPost::class, 'category_id');
	}

	public function parent(): BelongsTo
	{
		return $this->belongsTo(BlogCategory::class, 'parent_id');
	}

	public function children(): HasMany
	{
		return $this->hasMany(BlogCategory::class, 'parent_id')->orderBy('lft');
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
	protected function roots(Builder $query): void
	{
		$query->whereNull('parent_id');
	}

	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
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
				// Generate the category's image thumbnails
				GenerateThumbnail::dispatchSync($value, false, 'picture-lg');

				return $value;
			},
		);
	}

	protected function imageUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				$filePath = $this->image_path ?? null;
				if (empty($filePath)) {
					return null;
				}

				$resizeOptionsName = 'picture-lg';

				// Add the category's image thumbnails generation in queue
				GenerateThumbnail::dispatch($filePath, false, $resizeOptionsName);

				return thumbParam($filePath, false)->setOption($resizeOptionsName)->url();
			},
		);
	}

	protected function url(): Attribute
	{
		return Attribute::make(
			get: fn () => urlGen()->blogCategory($this),
		);
	}

	protected function seoTitle(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => !empty($value) ? $value : ($this->name ?? null),
		);
	}

	protected function seoDescription(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!empty($value)) {
					return $value;
				}

				$description = strip_tags((string)($this->attributes['description'] ?? ''));

				return !empty($description) ? str($description)->limit(160)->toString() : null;
			},
		);
	}
}
