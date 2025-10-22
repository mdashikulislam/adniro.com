<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Helpers\Common\HierarchicalData\Library\AdjacentToNested;
use App\Models\Traits\Common\HasNestedSelectOptions;
use Illuminate\Http\RedirectResponse;
use Throwable;

trait Nested
{
	public bool $isNestedEnabled = false;
	public string $parentRoute = '';
	public ?string $parentKeyColumn = null;
	public string $parentEntityName = 'entry';
	public string $parentEntityNamePlural = 'entries';
	
	public function hasNestedEntries(): bool
	{
		return in_array(HasNestedSelectOptions::class, class_uses($this->model));
	}
	
	/**
	 * @param string $route
	 * @return void
	 */
	public function setParentRoute(string $route): void
	{
		$this->parentRoute = $route;
		$this->initButtons();
	}
	
	/**
	 * @param string|null $columnName
	 */
	public function setParentKeyColumn(?string $columnName): void
	{
		$this->parentKeyColumn = $columnName;
	}
	
	/**
	 * @return string|null
	 */
	public function getParentKeyColumn(): ?string
	{
		return $this->parentKeyColumn;
	}
	
	/**
	 * @param string $singular
	 * @param string $plural
	 * @return void
	 */
	public function setParentEntityNameStrings(string $singular, string $plural): void
	{
		$this->parentEntityName = $singular;
		$this->parentEntityNamePlural = $plural;
	}
	
	/**
	 * @param $id
	 * @param $childId
	 * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
	 */
	public function getEntryWithParentAndChildKeys($id, $childId)
	{
		$entry = null;
		
		$parentKeyColumn = $this->getParentKeyColumn();
		if (!empty($parentKeyColumn)) {
			try {
				$entry = $this->model->query()
					->where($parentKeyColumn, '=', $id)
					->where($this->model->getKeyName(), '=', $childId)
					->first();
			} catch (\Throwable $e) {
				abort(500, $e->getMessage());
			}
			
			if (empty($entry)) {
				abort(404, 'Entry not found!');
			}
		}
		
		return $entry;
	}
	
	// READ
	
	public function enableParentEntity(): void
	{
		$this->isNestedEnabled = true;
	}
	
	public function disableParentEntity(): void
	{
		$this->isNestedEnabled = false;
	}
	
	public function hasParentEntity(): bool
	{
		return $this->isNestedEnabled;
	}
	
	// NODES
	
	/**
	 * Convert Adjacent List to Nested Set
	 *
	 * NOTE:
	 * - The items' order is reset, using the adjacent list's primary key order
	 * - Need to use the adjacent list model to add, update or delete nodes
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function rebuildNestedSetNodes(): RedirectResponse
	{
		$errorFound = false;
		
		$table = isset($this->model) ? $this->model->getTable() : null;
		if (empty($table)) {
			notification("Model not found.", 'error');
			
			return redirect()->back();
		}
		
		$params = [
			'adjacentTable' => $table,
			'nestedTable'   => $table,
		];
		
		$transformer = new AdjacentToNested($params);
		
		try {
			$transformer->getAndSetAdjacentItemsIds();
			$transformer->convertChildrenRecursively(0);
			$transformer->setNodesDepth();
		} catch (Throwable $e) {
			notification($e->getMessage(), 'error');
			$errorFound = true;
		}
		
		// Check if error occurred
		if (!$errorFound) {
			$message = trans('admin.action_performed_successfully');
			notification($message, 'success');
		}
		
		return redirect()->back();
	}
}
