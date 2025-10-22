<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Helpers\Common\JsonUtils;
use Illuminate\Support\Arr;

trait FakeFields
{
	/**
	 * Refactor the request array to something that can be passed to the model's create or update function.
	 * The resulting array will only include the fields that are stored in the database and their values,
	 * plus the '_token' and 'redirect_after_save' variables.
	 *
	 * @param array $inputArray The request input.
	 * @param string $form The CRUD form. Can be 'create' or 'update' . Default is 'create'.
	 * @param int|string|null $id The CRUD entry id in the case of the 'update' form.
	 * @return array The updated request input.
	 */
	public function compactFakeFields(array $inputArray, string $form = 'create', int|string|null $id = null)
	{
		if (empty($inputArray)) {
			$inputArray = request()->all();
		}
		
		$fakeFieldColumnsToEncode = [];
		
		// Get the right fields according to the form type (create/update)
		$fields = $this->getFields($form, $id);
		
		$defaultFakeColumn = 'extras';
		
		// Go through each defined field
		foreach ($fields as $field) {
			$fieldName = $field['name'] ?? null;
			$fieldType = $field['type'] ?? null;
			$isFakeable = (bool)($field['fake'] ?? false);
			$fakeColumn = $field['store_in'] ?? $defaultFakeColumn;
			
			if (empty($fieldName) || empty($fieldType)) {
				continue;
			}
			if ($fieldType == 'custom_html') {
				continue;
			}
			if (!array_key_exists($fieldName, $inputArray)) {
				continue;
			}
			
			// If it's a fake field
			if ($isFakeable) {
				// Add it to the request in its appropriate variable - the one defined, if defined
				if (!empty($fakeColumn)) {
					$inputArray[$fakeColumn][$fieldName] = $inputArray[$fieldName];
					
					// Remove the fake field
					Arr::pull($inputArray, $fieldName);
					
					if (!in_array($fakeColumn, $fakeFieldColumnsToEncode, true)) {
						$fakeFieldColumnsToEncode[] = $fakeColumn;
					}
				}
			}
		}
		
		// json_encode all fake_value columns if applicable in the database, so they can be properly stored and interpreted
		if (is_array($fakeFieldColumnsToEncode) && count($fakeFieldColumnsToEncode) > 0) {
			/**
			 * @var \App\Models\Page $model (for example)
			 * To make some methods clickable in IDE (i.e. PhpStorm), we need a model that uses specific traits.
			 * The Page model uses the CRUD/xPanel traits.
			 * So that needs to be changed in other projects where Page doesn't exist or no longer use these traits.
			 */
			$model = $this->model;
			foreach ($fakeFieldColumnsToEncode as $column) {
				$isTranslatableModel = (
					property_exists($model, 'translatable')
					&& method_exists($model, 'getTranslatableAttributes')
					&& in_array($column, $model->getTranslatableAttributes(), true)
				);
				
				if (!$isTranslatableModel) {
					if ($model->shouldEncodeFake($column)) {
						$inputArray[$column] = JsonUtils::ensureJson($inputArray[$column]);
					}
				}
				
				$inputArray[$column] = JsonUtils::ensureJson($inputArray[$column]);
			}
		}
		
		// If there are no fake fields defined, this will just return the original Request in full
		// since no modifications or additions have been made to $inputArray
		return $inputArray;
	}
	
	/**
	 * Compact a fake field in the request input array.
	 *
	 * @param array $inputArray  The request input.
	 * @param string $fakeFieldName The fake field name.
	 * @param string $fakeFieldKey  The fake field key.
	 */
	private function addCompactedField(array &$inputArray, string $fakeFieldName, string $fakeFieldKey)
	{
		$fakeField = $inputArray[$fakeFieldName];
		
		// Remove the fake field from the request
		Arr::pull($inputArray, $fakeFieldName);
		
		$inputArray[$fakeFieldKey][$fakeFieldName] = $fakeField;
	}
}
