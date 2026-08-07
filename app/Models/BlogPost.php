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
use App\Models\Traits\BlogPostTrait;
use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\Common\HasCountryCodeColumn;
use App\Observers\BlogPostObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([BlogPostObserver::class])]
#[ScopedBy([ActiveScope::class])]
class BlogPost extends BaseModel
{
	use Crud, AppendsTrait, Sluggable, SluggableScopeHelpers;
	use HasCountryCodeColumn;
	use BlogPostTrait;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'blog_posts';

	/**
	 * @var array<int, string>
	 */
	protected $appends = ['image_url', 'thumbnail_url', 'cover_url', 'cover_thumbnail_url', 'url'];

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
		'category_id',
		'country_code',
		'user_id',
		'title',
		'slug',
		'image_path',
		'excerpt',
		'content',
		'seo_title',
		'seo_description',
		'seo_keywords',
		'views',
		'featured',
		'active',
		'published_at',
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
			'category_id'  => 'integer',
			'user_id'      => 'integer',
			'views'        => 'integer',
			'featured'     => 'boolean',
			'active'       => 'boolean',
			'published_at' => 'datetime',
			'created_at'   => DateTimeCast::class,
			'updated_at'   => DateTimeCast::class,
		];
	}

	/**
	 * Get the estimated reading time (in minutes)
	 *
	 * @return int
	 */
	public function readingTime(): int
	{
		$content = strip_tags((string)($this->attributes['content'] ?? ''));
		$wordsCount = str_word_count($content);

		return max(1, (int)ceil($wordsCount / 200));
	}

	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function category(): BelongsTo
	{
		return $this->belongsTo(BlogCategory::class, 'category_id');
	}

	public function author(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id');
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

	/**
	 * Published entries only (activated & with a past publication date)
	 */
	#[Scope]
	protected function published(Builder $query): void
	{
		$query->where('active', 1)
			->where(function ($query) {
				$query->whereNull('published_at')
					->orWhere('published_at', '<=', now());
			});
	}

	#[Scope]
	protected function featured(Builder $query): void
	{
		$query->where('featured', 1);
	}

	/**
	 * Entries available in the given country:
	 * the global entries (without country) & the entries targeting that country
	 */
	#[Scope]
	protected function availableInCountry(Builder $query, ?string $code = null): void
	{
		$code = !empty($code) ? $code : config('country.code');

		$query->where(function (Builder $query) use ($code) {
			$query->whereNull('country_code')->orWhere('country_code', '=', $code);
		});
	}

	#[Scope]
	protected function inCategory(Builder $query, $categoryId): void
	{
		$query->where('category_id', $categoryId);
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
				// Generate the post's image thumbnails
				GenerateThumbnail::dispatchSync($value, false, 'picture-lg');

				return $value;
			},
		);
	}

	/**
	 * The post's cover image URL (null when the post hasn't any picture).
	 * Used for the SEO tags (Open Graph & JSON-LD), that must not get a placeholder.
	 */
	protected function imageUrl(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getImageUrl('picture-lg'),
		);
	}

	protected function thumbnailUrl(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getImageUrl('picture-md'),
		);
	}

	/**
	 * The post's cover image URL for display,
	 * with the app's default picture ("no image" placeholder) as fallback
	 */
	protected function coverUrl(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getImageUrl('picture-lg', withDefault: true),
		);
	}

	protected function coverThumbnailUrl(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getImageUrl('picture-md', withDefault: true),
		);
	}

	protected function url(): Attribute
	{
		return Attribute::make(
			get: fn () => urlGen()->blogPost($this),
		);
	}

	protected function excerpt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!empty($value)) {
					return $value;
				}

				$content = strip_tags((string)($this->attributes['content'] ?? ''));
				$content = normalizeWhitespace($content);

				return !empty($content) ? str($content)->limit(200)->toString() : null;
			},
		);
	}

	protected function seoTitle(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => !empty($value) ? $value : ($this->title ?? null),
		);
	}

	protected function seoDescription(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!empty($value)) {
					return $value;
				}

				$description = $this->excerpt;

				return !empty($description) ? str(strip_tags($description))->limit(160)->toString() : null;
			},
		);
	}

	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
	/**
	 * @param string $resizeOptionsName
	 * @param bool $withDefault Get the app's default picture when the post hasn't any picture
	 * @return string|null
	 */
	private function getImageUrl(string $resizeOptionsName, bool $withDefault = false): ?string
	{
		$filePath = $this->image_path ?? null;

		if (empty($filePath)) {
			return $withDefault
				? thumbParam(null, true)->setOption($resizeOptionsName)->url()
				: null;
		}

		// Add the post's image thumbnails generation in queue
		GenerateThumbnail::dispatch($filePath, false, $resizeOptionsName);

		return thumbParam($filePath, $withDefault)->setOption($resizeOptionsName)->url();
	}
}
