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
use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\SettingTrait;
use App\Observers\SettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[ObservedBy([SettingObserver::class])]
class Setting extends BaseModel
{
	use Crud, AppendsTrait;
	use SettingTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'settings';
	
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
	 * The primary key for the model.
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
					$transKey = 'settings.' . $this->name;
					
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
					$transKey = 'settings.description_' . $this->name;
					
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
					. mb_ucwords(trans('admin.general_settings')) . ' &rarr; ';
				
				$label = $this->label ?? 'Options';
				$description = mb_ucfirst(trans('settings.setting')) . ': ' . $label;
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
				// Get the right Setting
				$settingClass = $this->getSettingClass();
				if (class_exists($settingClass)) {
					if (method_exists($settingClass, 'getFields')) {
						$value = $settingClass::getFields($diskName);
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
		// Get the right Setting
		$settingClass = $this->getSettingClass();
		if (class_exists($settingClass)) {
			if (method_exists($settingClass, 'getFieldValues')) {
				$value = $settingClass::getFieldValues($value, $disk);
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
		// Get value
		$value = JsonUtils::jsonToArray($value);
		
		// Handle 'field_values' field value
		// Get the right Setting
		$settingClass = $this->getSettingClass();
		if (class_exists($settingClass)) {
			if (method_exists($settingClass, 'setFieldValues')) {
				$value = $settingClass::setFieldValues($value, $this);
			}
		}
		
		// Make sure that setting array contains only string, numeric or null elements
		$value = sanitizeSettingArray($value);
		
		return !empty($value) ? JsonUtils::ensureJson($value) : null;
	}
	
	/**
	 * Get the right Setting class
	 *
	 * @return string
	 */
	private function getSettingClass(): string
	{
		$name = $this->name ?? '';
		
		// Get class name
		$className = str($name)->camel()->ucfirst()->append('Setting');
		
		// Get class full qualified name (i.e. with namespace)
		$namespace = '\App\Models\Setting\\';
		$class = $className->prepend($namespace)->toString();
		
		// If the class doesn't exist in the core app, try to get it from add-ons
		if (!class_exists($class)) {
			$namespace = plugin_namespace($name) . '\app\Models\Setting\\';
			$class = $className->prepend($namespace)->toString();
		}
		
		return $class;
	}
}
