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

use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Models\Scopes\ActiveScope;
use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\MenuTrait;
use App\Observers\MenuObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([MenuObserver::class])]
#[ScopedBy([ActiveScope::class])]
class Menu extends BaseModel
{
	use Crud, AppendsTrait;
	use MenuTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'menus';
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = false;
	
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
		'name',
		'location',
		'description',
		'active',
		'lft',
		'rgt',
		'depth',
	];
	
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
			'active' => 'boolean',
			'lft'    => 'integer',
			'rgt'    => 'integer',
			'depth'  => 'integer',
		];
	}
	
	public static function getAvailableLocations(): array
	{
		$predefined = static::getLocations();
		
		// Get locations that are already taken
		$takenLocations = static::pluck('location')->toArray();
		
		return array_diff_key($predefined, array_flip($takenLocations));
	}
	
	public static function getLocationDisplayName(string $location): string
	{
		$locations = static::getLocations();
		
		return $locations[$location] ?? ucfirst($location) . ' Menu';
	}
	
	public static function getLocations(): array
	{
		$locations = config('larapen.menu.locations');
		
		return (array)$locations;
	}
	
	public function canChangeLocation(string $newLocation): bool
	{
		if (isset($this->location) && $this->location === $newLocation) {
			return true;
		}
		
		return !static::where('location', $newLocation)->exists();
	}
	
	public function getVisibleRootMenuItems()
	{
		return $this->rootMenuItems()
			->active()
			->with([
				'children' => fn ($query) => $query->active()->orderBy('lft'),
			])
			->get()
			->filter(fn ($item) => $item->is_visible);
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function menuItems(): HasMany
	{
		return $this->hasMany(MenuItem::class, 'menu_id');
	}
	
	public function rootMenuItems(): HasMany
	{
		return $this->menuItems()->columnIsEmpty('parent_id')->orderBy('lft')->orderBy('id');
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
	protected function forLocation(Builder $query, string $location): void
	{
		$query->where('location', '=', $location);
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
