<?php

namespace App\Services\ListingAssistant;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Field;
use Illuminate\Support\Facades\Cache;
use Throwable;

/*
 * The category and custom field catalog given to the drafting model (adniro:ai).
 *
 * Compact text, in English, sorted, byte-identical between calls so the provider can cache it.
 * Only active leaf categories. File, video, date-range and URL fields are left out.
 * Rebuilt when an admin changes categories, fields or options (see AiServiceProvider).
 */

class CatalogBuilder
{
	public const SKIPPED_TYPES = ['file', 'video', 'date_range', 'url']; // url: the AI never writes links
	private const VERSION_KEY = 'ai:listing_assistant_catalog_version';
	
	public function text(): string
	{
		$version = (string)Cache::get(self::VERSION_KEY, '1');
		
		return Cache::remember('ai:listing_assistant_catalog:' . $version, now()->addDay(), fn () => $this->build());
	}
	
	/**
	 * Leaf category id => list of usable field ids, for the sanitizer.
	 *
	 * @return array<int, array<int, int>>
	 */
	public function fieldsByCategory(): array
	{
		$version = (string)Cache::get(self::VERSION_KEY, '1');
		
		return Cache::remember('ai:listing_assistant_catalog_fields:' . $version, now()->addDay(), fn () => $this->collect()['map']);
	}
	
	public static function invalidate(): void
	{
		try {
			Cache::forever(self::VERSION_KEY, (string)now()->format('YmdHisv'));
		} catch (Throwable $e) {
		}
	}
	
	private function build(): string
	{
		$data = $this->collect();
		
		$lines = ['CATEGORIES (id | path | type | field ids)'];
		foreach ($data['categories'] as $category) {
			$lines[] = $category['id'] . ' | ' . $category['path'] . ' | ' . $category['type']
				. ' | ' . (empty($category['fields']) ? '-' : implode(',', $category['fields']));
		}
		
		$lines[] = '';
		$lines[] = 'FIELDS (id | name | type | required | options as id=value)';
		foreach ($data['fields'] as $field) {
			$line = $field['id'] . ' | ' . $field['name'] . ' | ' . $field['type'] . ' | ' . ($field['required'] ? 'required' : 'optional');
			if (!empty($field['options'])) {
				$line .= ' | ' . implode('; ', array_map(fn ($id, $value) => $id . '=' . $value, array_keys($field['options']), $field['options']));
			}
			$lines[] = $line;
		}
		
		return implode("\n", $lines);
	}
	
	/**
	 * @return array{categories: array, fields: array, map: array}
	 */
	private function collect(): array
	{
		$all = Category::query()->withoutGlobalScopes()->where('active', 1)->orderBy('lft')->get();
		$byId = $all->keyBy('id');
		$parentIds = $all->pluck('parent_id')->filter()->unique()->flip();
		
		$categories = [];
		$map = [];
		$usedFieldIds = [];
		
		foreach ($all as $category) {
			if ($parentIds->has($category->id)) {
				continue; // not a leaf
			}
			
			$path = [$this->en($category, 'name')];
			$parent = $byId->get($category->parent_id);
			$guard = 0;
			while ($parent && $guard++ < 10) {
				array_unshift($path, $this->en($parent, 'name'));
				$parent = $byId->get($parent->parent_id);
			}
			
			$fieldIds = collect(CategoryField::getFields($category->id))
				->filter(fn ($field) => !in_array(data_get($field, 'type'), self::SKIPPED_TYPES, true))
				->pluck('id')
				->map(fn ($id) => (int)$id)
				->unique()
				->sort()
				->values()
				->all();
			
			$categories[] = [
				'id'     => (int)$category->id,
				'path'   => $this->oneLine(implode(' > ', $path)),
				'type'   => (string)($category->type ?: 'classified'),
				'fields' => $fieldIds,
			];
			$map[(int)$category->id] = $fieldIds;
			$usedFieldIds = array_merge($usedFieldIds, $fieldIds);
		}
		
		$fields = [];
		$fieldModels = Field::query()->withoutGlobalScopes()->with('options')->whereIn('id', array_unique($usedFieldIds))->orderBy('id')->get();
		foreach ($fieldModels as $field) {
			$options = [];
			if (in_array($field->type, ['select', 'radio', 'checkbox_multiple'], true)) {
				foreach ($field->options->sortBy('id') as $option) {
					$options[(int)$option->id] = $this->oneLine($this->en($option, 'value'));
				}
			}
			
			$fields[] = [
				'id'       => (int)$field->id,
				'name'     => $this->oneLine($this->en($field, 'name')),
				'type'     => (string)$field->type,
				'required' => (bool)$field->required,
				'options'  => $options,
			];
		}
		
		return ['categories' => $categories, 'fields' => $fields, 'map' => $map];
	}
	
	private function en($model, string $attribute): string
	{
		try {
			if (method_exists($model, 'getTranslation')) {
				return (string)$model->getTranslation($attribute, 'en', true);
			}
		} catch (Throwable $e) {
		}
		
		return (string)$model->{$attribute};
	}
	
	private function oneLine(string $text): string
	{
		return trim(preg_replace('/[\s|;=]+/u', ' ', $text) ?? '');
	}
}
