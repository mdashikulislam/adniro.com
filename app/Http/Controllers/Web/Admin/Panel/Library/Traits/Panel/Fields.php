<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Helpers\Common\JsonUtils;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait Fields
{
	// ------------
	// FIELDS
	// ------------
	
	/**
	 * Add a field to the create/update form or both.
	 *
	 * @param array|string $field The new field.
	 * @param string $form The CRUD form. Can be 'create', 'update' or 'both'. Default is 'both'.
	 *
	 * @return self
	 */
	public function addField(array|string $field, string $form = 'both')
	{
		// if the fieldDefinitionArray array is a string, it means the programmer was lazy and has only passed the name
		// set some default values, so the field will still work
		if (is_string($field)) {
			$completeFieldsArray['name'] = $field;
		} else {
			$completeFieldsArray = $field;
		}
		
		// if the label is missing, we should set it
		if (!isset($completeFieldsArray['label'])) {
			$completeFieldsArray['label'] = ucfirst($completeFieldsArray['name']);
		}
		
		// if the field type is missing, we should set it
		if (!isset($completeFieldsArray['type'])) {
			$completeFieldsArray['type'] = $this->getFieldTypeFromDbColumnType($completeFieldsArray['name']);
		}
		
		// if a tab was mentioned, we should enable it
		if (isset($completeFieldsArray['tab'])) {
			if (!$this->tabsEnabled()) {
				$this->enableTabs();
			}
			$completeFieldsArray['tab'] = strip_tags($completeFieldsArray['tab']);
		}
		
		// store the field information into the correct variable on the CRUD object
		$this->transformFields($form, function ($fields) use ($completeFieldsArray) {
			$fields[$completeFieldsArray['name']] = $completeFieldsArray;
			
			return $fields;
		});
		
		return $this;
	}
	
	/**
	 * Add multiple fields to the create/update form or both.
	 *
	 * @param array $fields The new fields.
	 * @param string $form The CRUD form. Can be 'create', 'update' or 'both'. Default is 'both'.
	 */
	public function addFields(array $fields, string $form = 'both')
	{
		if (!empty($fields)) {
			foreach ($fields as $field) {
				$this->addField($field, $form);
			}
		}
	}
	
	/**
	 * Move the most recently added field after the given target field.
	 *
	 * @param string $targetFieldName The target field name.
	 * @param string $form The CRUD form. Can be 'create', 'update' or 'both'. Default is 'both'.
	 */
	public function afterField(string $targetFieldName, string $form = 'both')
	{
		$this->transformFields($form, function ($fields) use ($targetFieldName) {
			return $this->moveField($fields, $targetFieldName, false);
		});
	}
	
	/**
	 * Move the most recently added field before the given target field.
	 *
	 * @param string $targetFieldName The target field name.
	 * @param string $form The CRUD form. Can be 'create', 'update' or 'both'. Default is 'both'.
	 */
	public function beforeField(string $targetFieldName, string $form = 'both')
	{
		$this->transformFields($form, function ($fields) use ($targetFieldName) {
			return $this->moveField($fields, $targetFieldName, true);
		});
	}
	
	/**
	 * Move the most recently added field before or after the given target field. Default is before.
	 *
	 * @param array $fields The form fields.
	 * @param string $targetFieldName The target field name.
	 * @param bool $before If true, the field will be moved before the target field, otherwise it will be moved after it.
	 *
	 * @return array
	 */
	private function moveField(array $fields, string $targetFieldName, bool $before = true)
	{
		if (array_key_exists($targetFieldName, $fields)) {
			$targetFieldPosition = $before
				? array_search($targetFieldName, array_keys($fields))
				: array_search($targetFieldName, array_keys($fields)) + 1;
			
			if ($targetFieldPosition >= (count($fields) - 1)) {
				// Target field name is same as element
				return $fields;
			}
			
			$element = array_pop($fields);
			$beginningArrayPart = array_slice($fields, 0, $targetFieldPosition, true);
			$endingArrayPart = array_slice($fields, $targetFieldPosition, null, true);
			
			$fields = array_merge($beginningArrayPart, [$element['name'] => $element], $endingArrayPart);
		}
		
		return $fields;
	}
	
	/**
	 * Remove a certain field from the create/update/both forms by its name.
	 *
	 * @param string $name Field name (as defined with the addField() procedure)
	 * @param string $form update/create/both
	 */
	public function removeField(string $name, string $form = 'both')
	{
		$this->transformFields($form, function ($fields) use ($name) {
			Arr::forget($fields, $name);
			
			return $fields;
		});
	}
	
	/**
	 * Remove many fields from the create/update/both forms by their name.
	 *
	 * @param array $arrayOfNames
	 * @param string $form
	 */
	public function removeFields(array $arrayOfNames, string $form = 'both')
	{
		if (!empty($arrayOfNames)) {
			foreach ($arrayOfNames as $name) {
				$this->removeField($name, $form);
			}
		}
	}
	
	/**
	 * Remove all fields from the create/update/both forms.
	 *
	 * @param string $form update/create/both
	 */
	public function removeAllFields(string $form = 'both')
	{
		$currentFields = $this->getCurrentFields();
		if (!empty($currentFields)) {
			foreach ($currentFields as $field) {
				$this->removeField($field['name'], $form);
			}
		}
	}
	
	/**
	 * Update value of a given key for a current field.
	 *
	 * @param string $field The field
	 * @param array $modifications An array of changes to be made.
	 * @param string $form update/create/both
	 */
	public function modifyField(string $field, array $modifications, string $form = 'both')
	{
		foreach ($modifications as $key => $newValue) {
			switch (strtolower($form)) {
				case 'create':
					$this->createFields[$field][$key] = $newValue;
					break;
				
				case 'update':
					$this->updateFields[$field][$key] = $newValue;
					break;
				
				default:
					$this->createFields[$field][$key] = $newValue;
					$this->updateFields[$field][$key] = $newValue;
					break;
			}
		}
	}
	
	/**
	 * Set label for a specific field.
	 *
	 * @param string $field
	 * @param string $label
	 */
	public function setFieldLabel(string $field, string $label)
	{
		if (isset($this->createFields[$field])) {
			$this->createFields[$field]['label'] = $label;
		}
		if (isset($this->updateFields[$field])) {
			$this->updateFields[$field]['label'] = $label;
		}
	}
	
	/*
	 * Check if field is the first of its type in the given fields array.
	 * It's used in each field_type.blade.php to determine weather to push the css and js content or not
	 * (we only need to push the js and css for a field the first time it's loaded in the form, not any subsequent times).
	 *
	 * @param array $field
	 * @param array $fieldsArray
	 * @return bool
	 /
	public function checkIfFieldIsFirstOfItsType(array $field, array $fieldsArray)
	{
		if ($field['name'] == $this->getFirstOfItsTypeInArray($field['type'], $fieldsArray)['name']) {
			return true;
		}
		
		return false;
	}
	*/
	
	/**
	 * Check if field is the first of its type in the given fields array.
	 * It's used in each field_type.blade.php to determine weather to push the css and js content or not
	 * (we only need to push the js and css for a field the first time it's loaded in the form, not any subsequent times).
	 *
	 * @param array $field The current field being tested if it's the first of its type.
	 * @param array|\Illuminate\Support\Collection $fieldsArray
	 * @return bool
	 */
	public function checkIfFieldIsFirstOfItsType(array $field, array|Collection $fieldsArray = [])
	{
		$fieldsArray = ($fieldsArray instanceof Collection) ? $fieldsArray->toArray() : $fieldsArray;
		$fieldsArray = !empty($fieldsArray) ? $fieldsArray : $this->getCurrentFields();
		
		$firstField = $this->getFirstOfItsTypeInArray($field['type'], $fieldsArray);
		
		if ($field['name'] == $firstField['name']) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Decode attributes that are cast as array/object/json in the model.
	 * So that they are not json_encoded twice before they are stored in the db
	 * (once by the CRUD Panel in front-end, once by Laravel Attribute Casting).
	 *
	 * @param $data
	 * @param string $form
	 * @param int|string|null $id
	 * @return mixed
	 */
	public function decodeJsonCastedAttributes($data, string $form = 'create', int|string|null $id = null)
	{
		/** @var \Illuminate\Database\Eloquent\Model $model */
		$model = $this->model;
		
		// Get the right fields according to the form type (create/update)
		$fields = $this->getFields($form, $id);
		$castedAttributes = $model->getCasts();
		
		foreach ($fields as $field) {
			$fieldName = $field['name'] ?? null;
			if (empty($fieldName)) continue;
			
			// Reject non-castable fields
			if (!array_key_exists($fieldName, $castedAttributes)) {
				continue;
			}
			
			// Handle JSON field types
			$jsonCastables = ['array', 'object', 'json'];
			$fieldCasting = $castedAttributes[$fieldName];
			
			// Reject non JSON castable fields
			if (!in_array($fieldCasting, $jsonCastables)) {
				continue;
			}
			
			// Retrieve the field input value
			$value = $data[$fieldName];
			
			if (!empty($value) && !is_array($value)) {
				$data[$fieldName] = JsonUtils::isJson($value) ? JsonUtils::jsonToArray($value) : $value;
			}
		}
		
		return $data;
	}
	
	/**
	 * @return array
	 */
	public function getCurrentFields()
	{
		if ($this->entry) {
			return $this->getUpdateFields($this->entry->getKey());
		}
		
		return $this->getCreateFields();
	}
	
	/**
	 * Order the CRUD fields in the given form. If certain fields are missing from the given order array, they will be
	 * pushed to the new fields array in the original order.
	 *
	 * @param array $order An array of field names in the desired order.
	 * @param string $form The CRUD form. Can be 'create', 'update' or 'both'.
	 */
	public function orderFields(array $order, string $form = 'both')
	{
		$this->transformFields($form, function ($fields) use ($order) {
			return $this->applyOrderToFields($fields, $order);
		});
	}
	
	/**
	 * Apply the given order to the fields and return the new array.
	 *
	 * @param array $fields The fields array.
	 * @param array $order The desired field order array.
	 *
	 * @return array The ordered fields array.
	 */
	private function applyOrderToFields(array $fields, array $order)
	{
		$orderedFields = [];
		foreach ($order as $fieldName) {
			if (array_key_exists($fieldName, $fields)) {
				$orderedFields[$fieldName] = $fields[$fieldName];
			}
		}
		
		if (empty($orderedFields)) {
			return $fields;
		}
		
		$remaining = array_diff_key($fields, $orderedFields);
		
		return array_merge($orderedFields, $remaining);
	}
	
	/**
	 * Apply the given callback to the form fields.
	 *
	 * @param string $form The CRUD form. Can be 'create', 'update' or 'both'.
	 * @param callable $callback The callback function to run for the given form fields.
	 */
	private function transformFields(string $form, callable $callback)
	{
		switch (strtolower($form)) {
			case 'create':
				$this->createFields = $callback($this->createFields);
				break;
			
			case 'update':
				$this->updateFields = $callback($this->updateFields);
				break;
			
			default:
				$this->createFields = $callback($this->createFields);
				$this->updateFields = $callback($this->updateFields);
				break;
		}
	}
}
