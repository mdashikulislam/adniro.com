<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Helpers\Common\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait Create
{
	/*
	|--------------------------------------------------------------------------
	|                                   CREATE
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Insert a row in the database.
	 *
	 * @param $data
	 * @return mixed
	 */
	public function create($data)
	{
		$data = $this->decodeJsonCastedAttributes($data, 'create');
		$valuesToStore = $this->compactFakeFields($data, 'create');
		
		// Omit the n-n relationships when updating the eloquent item
		$nnRelationships = Arr::pluck($this->getRelationFieldsWithPivot('create'), 'name');
		$valuesToStore = Arr::except($valuesToStore, $nnRelationships);
		
		$item = $this->model->create($valuesToStore);
		
		// If there are any relationships available, also sync those
		// $this->syncPivot($item, $data);
		$this->createRelations($item, $data);
		
		return $item;
	}
	
	/**
	 * Get all fields needed for the ADD NEW ENTRY form.
	 *
	 * @return array
	 */
	public function getCreateFields(): array
	{
		$fields = (array)$this->createFields;
		
		// Add required system fields
		$this->addCreateSystemFields($fields);
		
		return $fields;
	}
	
	/**
	 * Get all fields with relation set (model key set on field).
	 *
	 * @param string $form
	 * @return array
	 */
	public function getRelationFields(string $form = 'create'): array
	{
		if ($form == 'create') {
			$fields = $this->createFields;
		} else {
			$fields = $this->updateFields;
		}
		
		$relationFields = [];
		
		foreach ($fields as $field) {
			if (isset($field['model'])) {
				$relationFields[] = $field;
			}
			
			if (
				isset($field['subfields'])
				&& is_array($field['subfields'])
				&& count($field['subfields'])
			) {
				foreach ($field['subfields'] as $subfield) {
					$relationFields[] = $subfield;
				}
			}
		}
		
		return $relationFields;
	}
	
	/**
	 * Get all fields with n-n relation set (pivot table is true).
	 *
	 * @param string $form create/update/both
	 * @return array The fields with n-n relationships.
	 */
	public function getRelationFieldsWithPivot(string $form = 'create')
	{
		$allRelationFields = $this->getRelationFields($form);
		
		return Arr::where($allRelationFields, function ($value) {
			return isset($value['pivot']) && $value['pivot'];
		});
	}
	
	/**
	 * Create the relations for the current model.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $item The current CRUD model.
	 * @param array $data The form data.
	 * @param string $form Optional form type. Can be either 'create', 'update' or 'both'. Default is 'create'.
	 */
	public function createRelations(Model $item, array $data, string $form = 'create')
	{
		$this->syncPivot($item, $data, $form);
		
		// Keep only fillable columns
		// Note: Make sure that full form fields are sent to pivot update (i.e. to the syncPivot() method)
		$fillable = $this->model->getFillable();
		$data = Arr::only($data, $fillable);
		
		$this->createOneToOneRelations($item, $data, $form);
	}
	
	/**
	 * @param $model
	 * @param $data
	 * @param string $form
	 * @return void
	 */
	public function syncPivot($model, $data, string $form = 'create'): void
	{
		$fieldsWithRelationships = $this->getRelationFields($form);
		
		// Simulate the model's wasChanged() method with a custom attribute
		$model->setAttribute('was_manually_changed', $model->wasChanged());
		
		foreach ($fieldsWithRelationships as $field) {
			if (isset($field['pivot']) && $field['pivot']) {
				$values = $data[$field['name']] ?? [];
				$model->{$field['name']}()->sync($values);
				
				if (isset($field['pivotFields'])) {
					foreach ($field['pivotFields'] as $pivotField) {
						foreach ($data[$pivotField] as $pivot_id => $field) {
							$model->{$field['name']}()->updateExistingPivot($pivot_id, [$pivotField => $field]);
						}
					}
				}
				
				$model->setAttribute('was_manually_changed', true);
			}
			
			if (isset($field['morph']) && $field['morph']) {
				$values = $data[$field['name']] ?? [];
				
				/*
				if ($model->{$field['name']}) {
					$model->{$field['name']}()->update($values);
				} else {
					$model->{$field['name']}()->create($values);
				}
				*/
				
				$model->{$field['name']}()->sync($values);
				
				$model->setAttribute('was_manually_changed', true);
			}
		}
	}
	
	// PRIVATE
	
	/**
	 * Create any existing one-to-one relations for the current model from the form data.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $item The current CRUD model.
	 * @param array $data The form data.
	 * @param string $form Optional form type. Can be either 'create', 'update' or 'both'. Default is 'create'.
	 */
	private function createOneToOneRelations(Model $item, array $data, string $form = 'create')
	{
		$relationData = $this->getRelationDataFromFormData($data, $form);
		$this->createRelationsForItem($item, $relationData);
	}
	
	/**
	 * Create any existing one-to-one relations for the current model from the relation data.
	 *
	 * @param \Illuminate\Database\Eloquent\Model $item The current CRUD model.
	 * @param array $formattedData The form data.
	 *
	 * @return void
	 */
	private function createRelationsForItem(Model $item, array $formattedData)
	{
		if (!isset($formattedData['relations'])) {
			return;
		}
		
		foreach ($formattedData['relations'] as $relationMethod => $relationData) {
			$model = $relationData['model'];
			$relation = $item->{$relationMethod}();
			
			if ($relation instanceof BelongsTo) {
				$modelInstance = $model::find($relationData['values'])->first();
				if ($modelInstance != null) {
					$relation->associate($modelInstance)->save();
				} else {
					$relation->dissociate()->save();
				}
			} else if ($relation instanceof HasOne) {
				if ($item->{$relationMethod} != null) {
					$item->{$relationMethod}->update($relationData['values']);
					$modelInstance = $item->{$relationMethod};
				} else {
					$relationModel = new $model();
					$modelInstance = $relationModel->create($relationData['values']);
					$relation->save($modelInstance);
				}
			} else {
				$relationModel = new $model();
				$modelInstance = $relationModel->create($relationData['values']);
				$relation->save($modelInstance);
			}
			
			if (isset($relationData['relations'])) {
				$this->createRelationsForItem($modelInstance, ['relations' => $relationData['relations']]);
			}
		}
	}
	
	/**
	 * Get a relation data array from the form data.
	 * For each relation defined in the fields through the entity attribute, set the model, the parent model and the
	 * attribute values. For relations defined with the "dot" notations, this will be used to calculate the depth in the
	 * final array (@param array $data The form data.
	 *
	 * @param string $form Optional form type. Can be either 'create', 'update' or 'both'. Default is 'create'.
	 * @return array The formatted relation data.
	 * @see \Illuminate\Support\Arr::set() for more.
	 */
	private function getRelationDataFromFormData(array $data, string $form = 'create'): array
	{
		$relationFields = $this->getRelationFields($form);
		
		$relationData = [];
		foreach ($relationFields as $relationField) {
			$attributeKey = $relationField['name'] ?? null;
			$entity = $relationField['entity'] ?? null;
			
			if (empty($attributeKey) || empty($entity)) {
				continue;
			}
			
			if (array_key_exists($attributeKey, $data) && empty($relationField['pivot'])) {
				$key = implode('.relations.', explode('.', $entity));
				$fieldData = Arr::get($relationData, 'relations.' . $key, []);
				
				if (!array_key_exists('model', $fieldData)) {
					$fieldData['model'] = $relationField['model'];
				}
				
				if (!array_key_exists('parent', $fieldData)) {
					$fieldData['parent'] = $this->getRelationModel($entity, -1);
				}
				
				$fieldData['values'][$attributeKey] = $data[$attributeKey];
				
				Arr::set($relationData, 'relations.' . $key, $fieldData);
			}
		}
		
		return $relationData;
	}
	
	/**
	 * Add required system fields (locale)
	 *
	 * @param array $fields
	 * @return void
	 */
	private function addCreateSystemFields(array &$fields): void
	{
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
