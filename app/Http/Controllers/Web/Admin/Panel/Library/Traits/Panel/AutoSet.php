<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use Illuminate\Support\Facades\Schema;

trait AutoSet
{
	// ------------------------------------------------------
	// AUTO-SET-FIELDS-AND-COLUMNS FUNCTIONALITY
	// ------------------------------------------------------
	public mixed $labeller = false;
	
	/**
	 * For a simple CRUD Panel, there should be no need to add/define the fields.
	 * The public columns in the database will be converted to be fields.
	 *
	 * @return void
	 */
	public function setFromDb()
	{
		$this->getDbColumnTypes();
		
		array_map(function ($field) {
			$newField = [
				'name'       => $field,
				'label'      => $this->makeLabel($field),
				'value'      => null,
				'default'    => $this->dbColumnTypes[$field]['default'] ?? null,
				'type'       => $this->getFieldTypeFromDbColumnType($field),
				'values'     => [],
				'attributes' => [],
				'autoset'    => true,
			];
			if (!isset($this->createFields[$field])) {
				$this->createFields[$field] = $newField;
			}
			if (!isset($this->updateFields[$field])) {
				$this->updateFields[$field] = $newField;
			}
			
			if (!in_array($field, $this->model->getHidden()) && !isset($this->columns[$field])) {
				$this->columns[$field] = [
					'name'    => $field,
					'label'   => $this->makeLabel($field),
					'type'    => $this->getFieldTypeFromDbColumnType($field),
					'autoset' => true,
				];
			}
		}, $this->getDbColumnsNames());
	}
	
	/**
	 * Get all columns from the database for that table.
	 *
	 * @return array
	 */
	public function getDbColumnTypes()
	{
		$tableColumns = Schema::getColumnListing($this->model->getTable());
		
		foreach ($tableColumns as $column) {
			$columnType = Schema::getColumnType($this->model->getTable(), $column);
			$this->dbColumnTypes[$column]['type'] = trim(preg_replace('/\(\d+\)(.*)/i', '', $columnType));
			$this->dbColumnTypes[$column]['default'] = ''; // No way to do this using DBAL?! $column->getDefault()
		}
		
		return $this->dbColumnTypes;
	}
	
	/**
	 * Intuit a field type, judging from the database column type.
	 *
	 * @param $field
	 * @return string
	 */
	public function getFieldTypeFromDbColumnType($field): string
	{
		if (!array_key_exists($field, $this->dbColumnTypes)) {
			return 'text';
		}
		
		if ($field == 'password') {
			return 'password';
		}
		
		if ($field == 'email') {
			return 'email';
		}
		
		return match ($this->dbColumnTypes[$field]['type']) {
			'int', 'integer', 'smallint', 'mediumint', 'longint' => 'number',
			'tinyint'                                            => 'active',
			'text', 'mediumtext', 'longtext'                     => 'textarea',
			'date'                                               => 'date',
			'datetime', 'timestamp'                              => 'datetime',
			'time'                                               => 'time',
			default                                              => 'text',
		};
	}
	
	/**
	 * Turn a database column name or PHP variable into a pretty label to be shown to the user.
	 *
	 * @param $value
	 * @return string
	 */
	public function makeLabel($value): string
	{
		$value = str_replace('_', ' ', $value);
		if (strtolower($value) != 'id') {
			$value = preg_replace('/(id|at|\[])$/ui', '', $value);
		}
		$value = trim($value);
		
		return ucfirst($value);
	}
	
	/**
	 * Alias to the makeLabel method.
	 */
	public function getLabel($value): string
	{
		return $this->makeLabel($value);
	}
	
	/**
	 * Change the way labels are made.
	 *
	 * @param callable $labeller A function that receives a string and returns the formatted string, after stripping down useless characters.
	 * @return self
	 */
	public function setLabeller(callable $labeller)
	{
		$this->labeller = $labeller;
		
		return $this;
	}
	
	/**
	 * Get the database column names, in order to figure out what fields/columns to show in the auto-fields-and-columns functionality.
	 *
	 * @return array
	 */
	public function getDbColumnsNames()
	{
		// Automatically-set columns should be both in the database, and in the $fillable variable on the Eloquent Model
		$columns = Schema::getColumnListing($this->model->getTable());
		$fillable = $this->model->getFillable();
		
		if (!empty($fillable)) {
			$columns = array_intersect($columns, $fillable);
		}
		
		// Get system columns (created_at, updated_at, deleted_at, ...)
		$systemColumns = [
			$this->model->getKeyName(),
			$this->model->getCreatedAtColumn(),
			$this->model->getUpdatedAtColumn(),
			'deleted_at'
		];
		
		// But not system columns
		return array_values(array_diff($columns, $systemColumns));
	}
}
