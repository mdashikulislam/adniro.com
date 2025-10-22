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

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Common\JsonUtils;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Models\Scopes\ActiveScope;
use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\SectionTrait;
use App\Observers\SectionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Throwable;

#[ObservedBy([SectionObserver::class])]
#[ScopedBy([ActiveScope::class])]
class Section extends BaseModel
{
	use Crud, AppendsTrait;
	use SectionTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'sections';
	
	/**
	 * @var array<int, string>
	 */
	protected array $fakeColumns = ['field_values'];
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
	
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
		'id',
		'belongs_to',
		'name',
		'label',
		'fields',
		'field_values',
		'description',
		'parent_id',
		'lft',
		'rgt',
		'depth',
		'active',
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
			'fields'       => 'array',
			'field_values' => 'array',
		];
	}
	
	/**
	 * Get all sections
	 *
	 * @param string|null $countryCode
	 * @return \Illuminate\Support\Collection
	 */
	public static function getAllSections(?string $countryCode = null): Collection
	{
		$countryCode = !empty($countryCode) ? $countryCode : config('country.code');
		
		$sections = collect();
		
		// Check if the Domain Mapping plugin is available
		if (config('plugins.domainmapping.installed')) {
			try {
				$sectionClass = '\extras\plugins\domainmapping\app\Models\DomainSection';
				$sections = $sectionClass::query()
					->where('country_code', '=', $countryCode)
					->orderBy('lft')
					->get();
			} catch (Throwable $e) {
			}
		}
		
		// Get the entry from the core
		if ($sections->count() <= 0) {
			$sections = self::query()->orderBy('lft')->get();
		}
		
		return ($sections instanceof Collection) ? $sections : collect($sections);
	}
	
	/**
	 * Get all sections key by formatted name
	 *
	 * Info: Set valid key name (for each Section) and set the collection key by 'key'
	 * Note: The key name needs to be cleared when the "Domain Mapping Plugin" is installed
	 *
	 * @param string|null $countryCode
	 * @return \Illuminate\Support\Collection
	 */
	public static function getFormattedSections(?string $countryCode = null): Collection
	{
		$sections = self::getAllSections();
		
		if ($sections->count() <= 0) {
			return collect();
		}
		
		$countryCode = !empty($countryCode) ? $countryCode : config('country.code');
		
		return $sections->mapWithKeys(function ($item) use ($countryCode) {
			$prefix = strtolower($countryCode) . '_';
			
			$method = $item['name'] ?? '';
			if (str_starts_with($method, $prefix)) {
				$method = str($method)->replaceStart($prefix, '')->toString();
			}
			
			return [$method => $item];
		});
	}
	
	/**
	 * @param string $name
	 * @param string|null $countryCode
	 * @param bool $isUnactivatedIncluded
	 * @return object|null
	 */
	public static function getSectionByName(string $name, ?string $countryCode = null, bool $isUnactivatedIncluded = false): ?object
	{
		$countryCode = !empty($countryCode) ? $countryCode : config('country.code');
		
		$section = null;
		
		// Check if the Domain Mapping plugin is available
		if (config('plugins.domainmapping.installed')) {
			try {
				$sectionClass = '\extras\plugins\domainmapping\app\Models\DomainSection';
				$section = $sectionClass::query();
				if ($isUnactivatedIncluded) {
					$section->withoutGlobalScopes([ActiveScope::class]);
				}
				$section = $section->where('country_code', '=', $countryCode)->where('name', $name)->first();
			} catch (Throwable $e) {
			}
		}
		
		// Get the entry from the core
		if (empty($section)) {
			$section = Section::query();
			if ($isUnactivatedIncluded) {
				$section->withoutGlobalScopes([ActiveScope::class]);
			}
			$section = $section->where('name', $name)->first();
		}
		
		return $section;
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function label(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->name)) {
					$transKey = 'sections.' . $this->name;
					
					if (trans()->has($transKey)) {
						$value = trans($transKey);
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function description(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->name)) {
					$transKey = 'sections.description_' . $this->name;
					
					if (trans()->has($transKey)) {
						$value = trans($transKey);
					}
					
					if (empty($value)) {
						$value = $this->label ?? $this->name;
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function fields(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$diskName = StorageDisk::getDiskName();
				
				// Get 'field' field value
				$value = JsonUtils::jsonToArray($value);
				
				$breadcrumb = trans('admin.Admin panel') . ' &rarr; '
					. mb_ucwords(trans('admin.settings')) . ' &rarr; '
					. mb_ucwords(trans('admin.homepage')) . ' &rarr; ';
				
				$label = $this->label ?? 'Options';
				$description = mb_ucfirst(trans('sections.section')) . ': ' . $label;
				$description = $this->description ?? $description;
				$title = !empty($description) ? $description : $label;
				
				$formTitle = [
					[
						'name'  => 'group_title',
						'type'  => 'custom_html',
						'value' => '<h2 class="mb-0 border-bottom pb-3 fw-bold">' . $title . '</h2>',
					],
					[
						'name'  => 'group_breadcrumb',
						'type'  => 'custom_html',
						'value' => '<p class="mb-0 border-bottom pb-3">' . $breadcrumb . $label . '</p>',
					],
				];
				
				// Handle 'field' field value
				// Get the right Section
				$sectionClass = $this->getSectionClass();
				if (class_exists($sectionClass)) {
					if (method_exists($sectionClass, 'getFields')) {
						$value = $sectionClass::getFields($diskName);
					}
				}
				
				return array_merge($formTitle, $value);
			},
		);
	}
	
	protected function fieldValues(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => $this->getValues($value),
			set: fn ($value) => $this->setValues($value),
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
	private function getValues($value): array
	{
		// IMPORTANT
		// The line below means that the all Storage providers need to be load before the AppServiceProvider,
		// to prevent all errors during the retrieving of the settings in the AppServiceProvider.
		$disk = StorageDisk::getDisk();
		
		// Get 'field_values' field value
		$value = JsonUtils::jsonToArray($value);
		
		// Handle 'field_values' field value
		// Get the right Section
		$sectionClass = $this->getSectionClass();
		if (class_exists($sectionClass)) {
			if (method_exists($sectionClass, 'getFieldValues')) {
				$value = $sectionClass::getFieldValues($value, $disk);
			}
		}
		
		// Demo: Secure some Data (Applied for all Entries)
		if (isAdminPanel() && isDemoDomain()) {
			$isPostOrPutMethod = (in_array(strtolower(request()->method()), ['post', 'put']));
			$isNotFromAuthForm = (!in_array(request()->segment(2), ['password', 'login']));
			$value = collect($value)
				->mapWithKeys(function ($v, $k) use ($isPostOrPutMethod, $isNotFromAuthForm) {
					$isOptionNeedToBeHidden = (
						!$isPostOrPutMethod
						&& $isNotFromAuthForm
						&& in_array($k, self::optionsThatNeedToBeHidden())
					);
					if ($isOptionNeedToBeHidden) {
						$v = '************************';
					}
					
					return [$k => $v];
				})->toArray();
		}
		
		return $value;
	}
	
	private function setValues($value): ?string
	{
		$value = JsonUtils::jsonToArray($value);
		
		// Handle 'field_values' field value
		// Get the right Section
		$sectionClass = $this->getSectionClass();
		if (class_exists($sectionClass)) {
			if (method_exists($sectionClass, 'setFieldValues')) {
				$value = $sectionClass::setFieldValues($value, $this);
			}
		}
		
		// Make sure that section array contains only string, numeric or null elements
		$value = sanitizeSettingArray($value);
		
		return !empty($value) ? JsonUtils::ensureJson($value) : null;
	}
	
	/**
	 * Get the right Section class
	 *
	 * @return string
	 */
	private function getSectionClass(): string
	{
		$belongsTo = $this->belongs_to ?? '';
		$name = $this->name ?? '';
		
		// Get class name
		$belongsTo = !empty($belongsTo) ? str($belongsTo)->camel()->ucfirst()->finish('\\')->toString() : '';
		$className = str($name)->camel()->ucfirst()->append('Section');
		
		// Get class full qualified name (i.e. with namespace)
		$namespace = '\App\Models\Section\\' . $belongsTo;
		$class = $className->prepend($namespace)->toString();
		
		// If the class doesn't exist in the core app, try to get it from add-ons
		if (!class_exists($class)) {
			$namespace = plugin_namespace($name) . '\app\Models\Section\\' . $belongsTo;
			$class = $className->prepend($namespace)->toString();
		}
		
		return $class;
	}
}
