<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models;

use App\Helpers\Common\JsonUtils;

trait HasFakeFields
{
	/*
	|--------------------------------------------------------------------------
	| Methods for Fake Fields functionality (used in PageManager).
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Add fake fields as regular attributes, even though they are stored as JSON.
	 *
	 * @param array $columns - the database columns that contain the JSONs
	 * @return void
	 */
	public function addFakes(array $columns = ['extras']): void
	{
		foreach ($columns as $column) {
			$columnContents = $this->{$column};
			
			if ($this->shouldDecodeFake($column)) {
				$columnContents = JsonUtils::jsonToArray($columnContents);
			}
			
			if (is_array($columnContents) || is_object($columnContents) || $columnContents instanceof \Traversable) {
				if (count($columnContents)) {
					// Set manually the fake column with $columnContents data
					$this->setAttribute($column, $columnContents);
					
					/*
					 * The loop below is not useful and should be monitored (or need to be removed soon)
					 * because it can cause the actual columns of the entity table to be overwritten.
					 * Hence the importance of checking if an attribute does not exist before defining it.
					 */
					foreach ($columnContents as $fakeFieldName => $fakeFieldValue) {
						if (!$this->hasAttribute($fakeFieldName)) {
							$this->setAttribute($fakeFieldName, $fakeFieldValue);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Return the entity with fake fields as attributes.
	 *
	 * @param array $columns - the database columns that contain the JSONs
	 * @return $this
	 */
	public function withFakes(array $columns = [])
	{
		$columnCount = (is_array($columns) || $columns instanceof \Countable)
			? count($columns)
			: 0;
		
		if ($columnCount == 0) {
			$model = '\\' . get_class($this);
			$columns = (property_exists($model, 'fakeColumns')) ? $this->fakeColumns : ['extras'];
		}
		
		$this->addFakes($columns);
		
		return $this;
	}
	
	/**
	 * Determine if this fake column should be json_decoded.
	 *
	 * @param $column string fake column name
	 *
	 * @return bool
	 */
	public function shouldDecodeFake(string $column): bool
	{
		return !array_key_exists($column, $this->getArrayCasts());
	}
	
	/**
	 * Determine if this fake column should get json_encoded or not.
	 *
	 * @param $column string fake column name
	 *
	 * @return bool
	 */
	public function shouldEncodeFake(string $column): bool
	{
		return !array_key_exists($column, $this->getArrayCasts());
	}
	
	/**
	 * @return array
	 */
	private function getArrayCasts(): array
	{
		return collect($this->casts)
			->filter(fn ($item) => ($item == 'array'))
			->toArray();
	}
}
