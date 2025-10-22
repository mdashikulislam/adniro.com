<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

use App\Helpers\Common\HierarchicalData\Library\AdjacentToNested;
use Illuminate\Support\Facades\Schema;

trait ReorderOperation
{
	/**
	 * Reorder the items in the database using the Nested Set pattern.
	 * Database columns needed: id, parent_id, lft, rgt, depth, name/title
	 *
	 * @param null $parentId
	 * @return \Illuminate\Contracts\View\View
	 */
	public function reorder($parentId = null)
	{
		$this->xPanel->hasAccessOrFail('reorder');
		
		// Retrieve the parent identifier
		$parentId = $this->xPanel->getParentResourceIdentifier($parentId);
		
		// Get all the entries
		$entries = $this->xPanel->getEntries();
		
		// Sort entries key by model's primary key
		$modelKeyName = $this->xPanel->getModel()->getKeyName();
		$entries = $entries->sortBy('lft')->keyBy($modelKeyName);
		
		// Get root entries
		if ($this->xPanel->isNestedEnabled) {
			$rootEntries = $entries->filter(function ($item) use ($parentId) {
				return $item->parent_id == $parentId;
			});
		} else {
			$rootEntries = $entries;
		}
		
		// Share to the view all results for that entity
		$this->data['entries'] = $entries;
		$this->data['rootEntries'] = $rootEntries;
		
		$this->data['xPanel'] = $this->xPanel;
		$this->data['title'] = trans('admin.reorder') . ' ' . $this->xPanel->entityName;
		
		return view($this->xPanel->getReorderView(), $this->data);
	}
	
	/**
	 * Save the new order, using the Nested Set pattern.
	 *
	 * Database columns needed: id, parent_id, lft, rgt, depth, name/title
	 *
	 * @return bool|string
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function saveReorder(): bool|string
	{
		// If reorder_table_permission is false, abort
		$this->xPanel->hasAccessOrFail('reorder');
		
		/** @var \Illuminate\Database\Eloquent\Model $model */
		$model = $this->xPanel->model;
		$table = $model->getTable();
		$primaryKey = $model->getKeyName();
		
		// Retrieve the parent identifier
		$parentId = $this->xPanel->getParentResourceIdentifier();
		
		$hasNestedSetFields = (Schema::hasColumn($table, 'lft') && Schema::hasColumn($table, 'rgt'));
		$hasAdjacentListField = Schema::hasColumn($table, 'parent_id');
		
		if (!$hasNestedSetFields) {
			return false;
		}
		
		$count = 0;
		$allEntries = request()->input('tree');
		
		if (is_array($allEntries) && count($allEntries)) {
			foreach ($allEntries as $entry) {
				if ($entry['item_id'] != '' && $entry['item_id'] != null) {
					$entry['parent_id'] = $parentId;
					
					$items = $model::query()->where($primaryKey, $entry['item_id'])->get();
					if ($items->count() > 0) {
						foreach ($items as $item) {
							if ($hasAdjacentListField) {
								$item->parent_id = $entry['parent_id'];
							}
							
							$item->lft = $entry['left'];
							$item->rgt = $entry['right'];
							$item->save();
						}
					} else {
						break;
					}
					
					$count++;
				}
			}
			
			// Rebuild Entries Nodes
			if ($hasAdjacentListField) {
				$this->rebuildCategoriesNodes($table);
			}
		} else {
			return false;
		}
		
		return castToString(trans('admin.reorder_success_for_x_items', ['count' => $count]));
	}
	
	/**
	 * Rebuild Entries Nodes
	 *
	 * Convert Adjacent List to Nested Set
	 * NOTE:
	 * - The items order is saved, using the 'lft' column value order
	 * - Need to use adjacent list model to add, update or delete nodes
	 *
	 * @param $table
	 * @throws \App\Exceptions\Custom\CustomException
	 * @todo: Not optimal. Find a better way to do it.
	 *
	 */
	protected function rebuildCategoriesNodes($table): void
	{
		$params = [
			'adjacentTable' => $table,
			'nestedTable'   => $table,
		];
		
		$transformer = new AdjacentToNested($params);
		$transformer->ordered = true;
		
		$transformer->getAndSetAdjacentItemsIds();
		$transformer->convertChildrenRecursively(0);
		$transformer->setNodesDepth();
	}
}
