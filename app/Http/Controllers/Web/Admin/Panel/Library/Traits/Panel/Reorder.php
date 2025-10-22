<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use Illuminate\Support\Facades\Schema;

trait Reorder
{
	/*
	|--------------------------------------------------------------------------
	|                                   REORDER
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Change the order and parents of the given elements, according to the NestedSortable AJAX call.
	 *
	 * @param $request - The entire request from the NestedSortable AJAX Call.
	 * @return int - The number of items whose position in the tree has been changed.
	 */
	public function updateTreeOrder($request): int
	{
		/** @var \Illuminate\Database\Eloquent\Model $model */
		$model = $this->model;
		$table = $model->getTable();
		$hasAdjacentListField = Schema::hasColumn($table, 'parent_id');
		
		$count = 0;
		
		foreach ($request as $item) {
			$itemId = $item['item_id'] ?? null;
			if (empty($itemId)) continue;
			
			$entry = $model->find($itemId);
			if ($hasAdjacentListField) {
				$entry->parent_id = $item['parent_id'] ?? null;
			}
			$entry->depth = $item['depth'] ?? null;
			$entry->lft = $item['left'] ?? null;
			$entry->rgt = $item['right'] ?? null;
			$entry->save();
			
			$count++;
		}
		
		return $count;
	}
	
	/**
	 * Enable the Reorder functionality in the CRUD Panel for users that have the been given access to 'reorder' using:
	 * $this->crud->allowAccess('reorder');.
	 *
	 * @param string $label - Column name that will be shown on the labels.
	 * @param int $maxLevel - Maximum hierarchy level to which the elements can be nested (1 = no nesting, just reordering).
	 */
	public function enableReorder(string $label = 'name', int $maxLevel = 1)
	{
		$this->reorder = true;
		$this->reorderLabel = $label;
		$this->reorderMaxLevel = $maxLevel;
	}
	
	/**
	 * Disable the Reorder functionality in the CRUD Panel for all users.
	 */
	public function disableReorder()
	{
		$this->reorder = false;
	}
	
	/**
	 * Check if the Reorder functionality is enabled or not.
	 *
	 * @return bool
	 */
	public function isReorderEnabled(): bool
	{
		return $this->reorder;
	}
}
