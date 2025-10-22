<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Helpers\Common\Arr;
use App\Helpers\Common\RepeaterFieldHandler;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

trait Update
{
	/*
	|--------------------------------------------------------------------------
	|                                   UPDATE
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Update a row in the database.
	 *
	 * @param $id
	 * @param $data
	 * @return mixed
	 */
	public function update($id, $data)
	{
		$data = $this->decodeJsonCastedAttributes($data, 'update', $id);
		
		// Update fake fields values in array
		$valuesToStore = $this->compactFakeFields($data, 'update', $id);
		
		// Get the entry/item
		$item = $this->model->query()->findOrFail($id);
		
		// Create relationships
		$this->createRelations($item, $valuesToStore, 'update');
		
		// Omit the n-n relationships when updating the eloquent item
		$nnRelationships = Arr::pluck($this->getRelationFieldsWithPivot('update'), 'name');
		$valuesToStore = Arr::except($valuesToStore, $nnRelationships);
		
		// Keep only fillable columns
		// Note: Make sure that full form fields are sent to pivot update (i.e. to the syncPivot() method)
		$fillable = $this->model->getFillable();
		$valuesToStore = Arr::only($valuesToStore, $fillable);
		
		// Update the entry in DB
		// $updated = $item->update($valuesToStore);
		foreach ($valuesToStore as $field => $value) {
			$item->{$field} = $value;
		}
		
		if ($item->isDirty()) {
			// Retrieve the custom 'was_manually_changed' attribute
			// Then delete it from the object before saving (since no DB column exists for it)
			// That will be set back after the object is saved.
			$wasManuallyChanged = false;
			if (isset($item->was_manually_changed)) {
				$wasManuallyChanged = $item->was_manually_changed;
				unset($item->was_manually_changed);
			}
			
			// Save the entry
			$item->save();
			
			// Set back the 'was_manually_changed' attribute
			$item->setAttribute('was_manually_changed', $wasManuallyChanged);
		}
		
		/*
		// Sync. pivot (if enabled)
		if ($this->isEnabledSyncPivot()) {
			$this->syncPivot($item, $data, 'update');
		}
		*/
		
		return $item;
	}
	
	/**
	 * Get all fields needed for the EDIT ENTRY form.
	 *
	 * @param int|string|null $id The id of the entry that is being edited.
	 * @return array  The fields with attributes, fake attributes and values.
	 */
	public function getUpdateFields(int|string|null $id): array
	{
		$fields = (array)$this->updateFields;
		$entry = $this->getEntry($id);
		
		foreach ($fields as $key => $field) {
			$fieldValue = $field['value'] ?? null;
			
			// Skip if value is already set from field definition
			if (!is_null($fieldValue)) {
				continue;
			}
			
			// Check types of field
			$isFakeable = (bool)($field['fake'] ?? false);
			$fakeColumn = $field['store_in'] ?? null;
			$isFakeable = ($isFakeable && !empty($fakeColumn));
			
			$subFields = $field['subfields'] ?? [];
			$hasSubFields = (!empty($subFields) && is_array($subFields));
			
			// Process field based on whether it has "fake column" or subfields
			if ($isFakeable) {
				$field['value'] = $this->processSimpleFieldWithFakeColumn($field, $entry, $key);
			} else {
				if ($hasSubFields) {
					$field['value'] = $this->processFieldWithSubfields($field, $entry);
				} else {
					$field['value'] = $this->processSimpleField($field, $entry, $key);
				}
			}
			
			$fields[$key] = $field;
		}
		
		// Add required system fields
		$this->addUpdateSystemFields($fields, $entry);
		
		return $fields;
	}
	
	// PRIVATE
	
	/**
	 * Get the value of the 'name' attribute from the declared relation model in the given field.
	 *
	 * @param object $entry The current CRUD model entry.
	 * @param array $field The CRUD field array.
	 * @return mixed The value of the 'name' attribute from the relation model.
	 */
	private function getModelAttributeValue(object $entry, array $field)
	{
		$columnName = $field['name'] ?? null;
		if (empty($columnName)) {
			return null;
		}
		
		$entity = $field['entity'] ?? null;
		if (!empty($entity)) {
			$relationArray = explode('.', $entity);
			$relatedModel = array_reduce(array_splice($relationArray, 0, -1), function ($obj, $method) {
				return $obj->{$method} ?? $obj;
			}, $entry);
			
			$relationMethod = end($relationArray);
			if ($relatedModel->{$relationMethod} && $relatedModel->{$relationMethod}() instanceof HasOneOrMany) {
				return $relatedModel->{$relationMethod}->{$columnName};
			} else {
				return $relatedModel->{$columnName};
			}
		}
		
		return $entry->{$columnName};
	}
	
	/**
	 * Process simple field (no subfields)
	 *
	 * @param array $field
	 * @param object $entry
	 * @return null
	 */
	private function processSimpleField(array $field, object $entry)
	{
		$columnName = $field['name'] ?? null;
		if (empty($columnName)) {
			return null;
		}
		
		return $entry?->{$columnName};
	}
	
	/**
	 * Process simple field with fake column (no subfields)
	 * Handle fake columns (e.g. attributes fakely created for 'field_values')
	 *
	 * @param array $field
	 * @param object $entry
	 * @param string $key
	 * @return mixed|null
	 */
	private function processSimpleFieldWithFakeColumn(array $field, object $entry, string $key)
	{
		$fakeColumnName = $field['store_in'] ?? null;
		if (empty($fakeColumnName)) {
			return null;
		}
		
		$columnValue = $entry->{$fakeColumnName} ?? [];
		
		return $columnValue[$key] ?? null;
	}
	
	/**
	 * Process fields that have subfields (repeatable or grouped fields)
	 *
	 * @param array $field
	 * @param object $entry
	 * @return array
	 */
	private function processFieldWithSubfields(array $field, object $entry)
	{
		$fieldName = $field['name'] ?? null;
		$fieldType = $field['type'] ?? null;
		$subFields = $field['subfields'] ?? [];
		
		if ($fieldType === 'repeatable') {
			return $this->processRepeatableField($fieldName, $subFields, $entry);
		}
		
		return $this->processGroupedField($subFields, $entry);
	}
	
	/**
	 * Process repeatable field type
	 *
	 * @param string $fieldName
	 * @param array $subFields
	 * @param object $entry
	 * @return array
	 */
	private function processRepeatableField(string $fieldName, array $subFields, object $entry)
	{
		$columnValue = $entry->{$fieldName} ?? [];
		$subFieldsKeys = collect($subFields)->pluck('name')->filter()->toArray();
		
		if (empty($subFieldsKeys)) {
			return [];
		}
		
		// Handle simple repeatable fields (1-2 subfields)
		if (count($subFieldsKeys) <= 2) {
			if (empty($columnValue) || !is_array($columnValue)) {
				return $columnValue;
			}
			
			$repeaterHandler = new RepeaterFieldHandler();
			
			if (count($subFieldsKeys) === 1) {
				$subFieldKey = $subFieldsKeys[0];
				
				return $repeaterHandler->prepareForRepeater(data: $columnValue, singleKey: $subFieldKey);
			}
			
			return $repeaterHandler->prepareForRepeater(data: $columnValue, doubleKeys: $subFieldsKeys);
		}
		
		// Handle complex repeatable fields (3+ subfields)
		$processedValue = [];
		if (is_array($columnValue)) {
			foreach ($subFields as $k => $subField) {
				$subFieldName = $subField['name'] ?? null;
				if ($subFieldName) {
					$processedValue[$k][$subFieldName] = $columnValue[$k][$subFieldName] ?? null;
				}
			}
		}
		
		return $processedValue;
	}
	
	/**
	 * Process grouped field (non-repeatable with subfields)
	 *
	 * @param array $subFields
	 * @param object $entry
	 * @return array
	 */
	private function processGroupedField(array $subFields, object $entry): array
	{
		$groupedValue = [];
		
		foreach ($subFields as $subField) {
			$subFieldName = $subField['name'] ?? null;
			if (empty($subFieldName)) {
				continue;
			}
			
			$subFieldColumnValue = $entry->{$subFieldName} ?? null;
			if (!is_null($subFieldColumnValue)) {
				$groupedValue[] = $subFieldColumnValue;
			}
		}
		
		return $groupedValue;
	}
	
	/**
	 * Add required system fields (ID and locale)
	 *
	 * @param array $fields
	 * @param object $entry
	 * @return void
	 */
	private function addUpdateSystemFields(array &$fields, object $entry): void
	{
		// Always add hidden input for entry ID
		$fields['id'] = [
			'name'  => $entry->getKeyName(),
			'type'  => 'hidden',
			'value' => $entry->getKey(),
		];
		
		/** @var \App\Models\Page $model (for example) */
		$model = $this->model;
		
		// Add locale field if translations are enabled
		if ($model->translationEnabled()) {
			$fields['locale'] = [
				'name'  => 'locale',
				'type'  => 'hidden',
				'value' => request()->input('locale', app()->getLocale()),
			];
		}
	}
}
