<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait Columns
{
	// ------------
	// COLUMNS
	// ------------
	public int $actionsColumnPriority = 1;
	
	/**
	 * Get the CRUD columns.
	 *
	 * @return array CRUD columns.
	 */
	public function getColumns()
	{
		return $this->columns;
	}
	
	/**
	 * Add a bunch of column names and their details to the CRUD object.
	 *
	 * @param array|string $columns
	 */
	public function setColumns(array|string $columns)
	{
		// Clear any columns already set
		$this->columns = [];
		
		// If $columns is array, add a column for each of the items
		if (is_array($columns) && count($columns)) {
			foreach ($columns as $column) {
				// If label and other details have been defined in the array
				if (is_array($columns[0])) {
					$this->addColumn($column);
				} else {
					$this->addColumn([
						'name'  => $column,
						'label' => ucfirst($column),
						'type'  => 'text',
					]);
				}
			}
		}
		
		if (is_string($columns)) {
			$this->addColumn([
				'name'  => $columns,
				'label' => ucfirst($columns),
				'type'  => 'text',
			]);
		}
		
		// This was the old setColumns() function, and it did not work:
		// $this->columns = array_filter(array_map([$this, 'addDefaultTypeToColumn'], $columns));
	}
	
	/**
	 * Add a column at the end of to the CRUD object's "columns" array.
	 *
	 * @param $column
	 * @return $this
	 */
	public function addColumn($column)
	{
		// If a string was passed, not an array, change it to an array
		if (!is_array($column)) {
			$column = ['name' => $column];
		}
		
		// Make sure the column has a label
		$columnWithDetails = $this->addDefaultLabel($column);
		
		// Make sure the column has a name
		if (!array_key_exists('name', $columnWithDetails)) {
			$columnWithDetails['name'] = 'anonymous_column_' . Str::random(5);
		}
		
		// Check if the column exists in the database table
		$columnExistsInDb = $this->hasColumn($this->model->getTable(), $columnWithDetails['name']);
		
		// Make sure the column has a type
		if (!array_key_exists('type', $columnWithDetails)) {
			// $columnWithDetails['type'] = 'text';
			$columnWithDetails = $this->addDefaultTypeToColumn($columnWithDetails);
		}
		
		// Make sure the column has a key
		if (!array_key_exists('key', $columnWithDetails)) {
			$columnWithDetails['key'] = $columnWithDetails['name'];
		}
		
		// Make sure the column has a tableColumn boolean
		if (!array_key_exists('tableColumn', $columnWithDetails)) {
			$columnWithDetails['tableColumn'] = (bool)$columnExistsInDb;
		}
		
		// Make sure the column has an orderable boolean
		if (!array_key_exists('orderable', $columnWithDetails)) {
			$columnWithDetails['orderable'] = (bool)$columnExistsInDb;
		}
		
		// Make sure the column has a searchLogic
		if (!array_key_exists('searchLogic', $columnWithDetails)) {
			$columnWithDetails['searchLogic'] = (bool)$columnExistsInDb;
		}
		
		array_filter($this->columns[$columnWithDetails['key']] = $columnWithDetails);
		
		// Make sure the column has a priority in terms of visibility
		// If no priority has been defined, use the order in the array plus one
		if (!array_key_exists('priority', $columnWithDetails)) {
			$positionInColumnsArray = (int)array_search($columnWithDetails['key'], array_keys($this->columns));
			$this->columns[$columnWithDetails['key']]['priority'] = $positionInColumnsArray + 1;
		}
		
		// If this is a relation type field and no corresponding model was specified, get it from the relation method
		// defined in the main model
		if (isset($columnWithDetails['entity']) && !isset($columnWithDetails['model'])) {
			$columnWithDetails['model'] = $this->getRelationModel($columnWithDetails['entity']);
		}
		
		return $this;
	}
	
	/**
	 * Add multiple columns at the end of the CRUD object's "columns" array.
	 *
	 * @param $columns
	 */
	public function addColumns($columns)
	{
		if (is_array($columns) && count($columns)) {
			foreach ($columns as $column) {
				$this->addColumn($column);
			}
		}
	}
	
	/**
	 * Move the most recently added column after the given target column.
	 *
	 * @param array|string $targetColumn The target column name or array.
	 * @return void
	 */
	public function afterColumn(array|string $targetColumn)
	{
		$this->moveColumn($targetColumn, false);
	}
	
	/**
	 * Move the most recently added column before the given target column.
	 *
	 * @param array|string $targetColumn The target column name or array.
	 * @return void
	 */
	public function beforeColumn(array|string $targetColumn)
	{
		$this->moveColumn($targetColumn);
	}
	
	/**
	 * Move this column to be first in the columns list.
	 *
	 * @return void
	 */
	public function makeFirstColumn()
	{
		if (!$this->columns) {
			return;
		}
		
		$firstColumn = array_keys(array_slice($this->columns, 0, 1))[0];
		$this->beforeColumn($firstColumn);
	}
	
	/**
	 * Move the most recently added column before or after the given target column. Default is before.
	 *
	 * @param array|string $targetColumn The target column name or array.
	 * @param bool $before If true, the column will be moved before the target column, otherwise it will be moved after it.
	 */
	private function moveColumn(array|string $targetColumn, bool $before = true)
	{
		// TODO: this and the moveField method from the Fields trait
		// should be refactored into a single method and moved into a common class
		$targetColumnName = is_array($targetColumn) ? $targetColumn['name'] : $targetColumn;
		
		if (array_key_exists($targetColumnName, $this->columns)) {
			$targetColumnPosition = $before
				? array_search($targetColumnName, array_keys($this->columns))
				: (array_search($targetColumnName, array_keys($this->columns)) + 1);
			
			$element = array_pop($this->columns);
			$beginningPart = array_slice($this->columns, 0, $targetColumnPosition, true);
			$endingArrayPart = array_slice($this->columns, $targetColumnPosition, null, true);
			
			$this->columns = array_merge($beginningPart, [$element['name'] => $element], $endingArrayPart);
		}
	}
	
	/**
	 * Add the default column type to the given Column, inferring the type from the database column type.
	 *
	 * @param $column
	 * @return array
	 */
	public function addDefaultTypeToColumn($column)
	{
		if (!is_array($column)) {
			return [];
		}
		
		if (array_key_exists('name', $column)) {
			$defaultType = $this->getFieldTypeFromDbColumnType($column['name']);
			
			return array_merge(['type' => $defaultType], $column);
		}
		
		return $column;
	}
	
	/**
	 * If a field or column array is missing the "label" attribute, an ugly error would be shown.
	 * So we add the field Name as a label - it's better than nothing.
	 *
	 * @param $array
	 * @return array
	 */
	public function addDefaultLabel($array)
	{
		if (!is_array($array)) {
			return [];
		}
		
		if (!array_key_exists('label', $array) && array_key_exists('name', $array)) {
			$label = $this->makeLabel($array['name']);
			
			return array_merge(['label' => $label], $array);
		}
		
		return $array;
	}
	
	/**
	 * Remove a column from the CRUD panel by name.
	 *
	 * @param string $column The column name.
	 */
	public function removeColumn(string $column)
	{
		Arr::forget($this->columns, $column);
	}
	
	/**
	 * Remove multiple columns from the CRUD panel by name.
	 *
	 * @param array $columns Array of column names.
	 */
	public function removeColumns(array $columns)
	{
		if (!empty($columns)) {
			foreach ($columns as $columnName) {
				$this->removeColumn($columnName);
			}
		}
	}
	
	/**
	 * Remove an entry from an array.
	 *
	 * @param string $entity
	 * @param array $fields
	 * @return array values
	 *
	 * @deprecated This method is no longer used by internal code and is not recommended as it does not preserve the
	 *             target array keys.
	 * @see Columns::removeColumn() to remove a column from the CRUD panel by name.
	 * @see Columns::removeColumns() to remove multiple columns from the CRUD panel by name.
	 */
	public function remove(string $entity, array $fields)
	{
		return array_values(array_filter($this->{$entity}, function ($field) use ($fields) {
			return !in_array($field['name'], $fields);
		}));
	}
	
	/**
	 * Change attributes for multiple columns.
	 *
	 * @param array $columns
	 * @param array $attributes
	 * @return void
	 */
	public function setColumnsDetails(array $columns, array $attributes)
	{
		$this->sync('columns', $columns, $attributes);
	}
	
	/**
	 * Change attributes for a certain column.
	 *
	 * @param string $column
	 * @param array $attributes
	 * @return void
	 */
	public function setColumnDetails(string $column, array $attributes)
	{
		$this->setColumnsDetails([$column], $attributes);
	}
	
	/**
	 * Set label for a specific column.
	 *
	 * @param string $column
	 * @param string $label
	 * @return void
	 */
	public function setColumnLabel(string $column, string $label)
	{
		$this->setColumnDetails($column, ['label' => $label]);
	}
	
	/**
	 * Get the relationships used in the CRUD columns.
	 *
	 * @return array
	 */
	public function getColumnsRelationships(): array
	{
		$columns = $this->getColumns();
		
		return collect($columns)
			->pluck('entity')
			->reject(fn ($value) => $value == null)
			->toArray();
	}
	
	/**
	 * Order the CRUD columns. If certain columns are missing from the given order array, they will be pushed to the
	 * new columns array in the original order.
	 *
	 * @param array $order
	 */
	public function orderColumns(array $order)
	{
		$orderedColumns = [];
		foreach ($order as $columnName) {
			if (array_key_exists($columnName, $this->columns)) {
				$orderedColumns[$columnName] = $this->columns[$columnName];
			}
		}
		if (empty($orderedColumns)) {
			return;
		}
		$remaining = array_diff_key($this->columns, $orderedColumns);
		$this->columns = array_merge($orderedColumns, $remaining);
	}
	
	/**
	 * Get a column by the id, from the associative array.
	 *
	 * @param int $columnNumber
	 * @return mixed
	 */
	public function findColumnById(int $columnNumber)
	{
		$result = array_slice($this->getColumns(), $columnNumber, 1);
		
		return reset($result);
	}
	
	/**
	 * @param string $table
	 * @param $name
	 * @return bool
	 */
	protected function hasColumn(string $table, $name)
	{
		static $cache = [];
		$columns = $cache[$table] ?? ($cache[$table] = Schema::getColumnListing($table));
		
		return in_array($name, $columns);
	}
	
	/**
	 * Get the visibility priority for the actions column
	 * in the CRUD table view.
	 *
	 * @return int The priority, from 1 to infinity. Lower is better.
	 */
	public function getActionsColumnPriority()
	{
		return (int)$this->actionsColumnPriority;
	}
	
	/**
	 * Set a certain priority for the actions column
	 * in the CRUD table view. Usually set to 10000 in order to hide it.
	 *
	 * @param int $number The priority, from 1 to infinity. Lower is better.
	 * @return self
	 */
	public function setActionsColumnPriority(int $number)
	{
		$this->actionsColumnPriority = $number;
		
		return $this;
	}
}
