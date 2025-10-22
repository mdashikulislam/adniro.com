<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Helpers\Common\DBUtils;
use Illuminate\Database\Eloquent\Builder;

trait Search
{
	/*
	|--------------------------------------------------------------------------
	|                                   SEARCH
	|--------------------------------------------------------------------------
	*/
	
	public bool $ajaxTable = true;
	public bool $responsiveTable = true;
	
	/**
	 * Add conditions to the CRUD query for a particular search term.
	 *
	 * @param $searchTerm (Whatever string the user types in the search bar.)
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function applySearchTerm($searchTerm): Builder
	{
		return $this->query->where(function ($query) use ($searchTerm) {
			foreach ($this->getColumns() as $column) {
				if (!isset($column['type'])) {
					abort(400, 'Missing column type when trying to apply search term.');
				}
				
				$this->applySearchLogicForColumn($query, $column, $searchTerm);
			}
		});
	}
	
	/**
	 * Apply the search logic for each CRUD column.
	 *
	 * @param $query
	 * @param $column
	 * @param $searchTerm
	 * @return void
	 */
	public function applySearchLogicForColumn($query, $column, $searchTerm)
	{
		$columnType = $column['type'];
		
		// If there's a particular search logic defined, apply that one
		if (isset($column['searchLogic'])) {
			$searchLogic = $column['searchLogic'];
			
			// If a closure was passed, execute it
			if (is_callable($searchLogic)) {
				return $searchLogic($query, $column, $searchTerm);
			}
			
			// If a string was passed, search like it was that column type
			if (is_string($searchLogic)) {
				$columnType = $searchLogic;
			}
			
			// If false was passed, don't search this column
			if (!$searchLogic) {
				return;
			}
		}
		
		// Sensible fallback search logic, if none was explicitly given
		if ($column['tableColumn']) {
			$singleSelectionFields = ['text', 'email'];
			$multiSelectionsFields = ['select_multiple', 'select'];
			
			// If the MySQL version is 8 or greater, don't use 'LIKE' statement for date or datetime column types
			if (!DBUtils::isMySqlMinVersion(8)) {
				$singleSelectionFields = array_merge($singleSelectionFields, ['date', 'datetime']);
			}
			
			if (in_array($columnType, $singleSelectionFields)) {
				$query->orWhere($column['name'], 'like', '%' . $searchTerm . '%');
			} else if (in_array($columnType, $multiSelectionsFields)) {
				$query->orWhereHas($column['entity'], function ($q) use ($column, $searchTerm) {
					$q->where($column['attribute'], 'like', '%' . $searchTerm . '%');
				});
			}
		}
	}
	
	/**
	 * Tell the list view to use AJAX for loading multiple rows.
	 *
	 * @deprecated 3.3.0 All tables are AjaxTables starting with 3.3.0.
	 */
	public function enableAjaxTable(): void
	{
		$this->ajaxTable = true;
	}
	
	/**
	 * Check if ajax is enabled for the table view.
	 *
	 * @return bool
	 * @deprecated 3.3.0 Since all tables use ajax, this will soon be removed.
	 */
	public function ajaxTable(): bool
	{
		return $this->ajaxTable;
	}
	
	/**
	 * Tell the list view to NOT show a responsive DataTable.
	 *
	 * @param bool $value
	 */
	public function setResponsiveTable(bool $value = true)
	{
		$this->responsiveTable = $value;
	}
	
	/**
	 * Check if responsiveness is enabled for the table view.
	 *
	 * @return bool
	 */
	public function getResponsiveTable()
	{
		return $this->responsiveTable;
	}
	
	/**
	 * Remember to show a responsive table.
	 */
	public function enableResponsiveTable()
	{
		$this->setResponsiveTable(true);
	}
	
	/**
	 * Remember to show a table with horizontal scrolling.
	 */
	public function disableResponsiveTable()
	{
		$this->setResponsiveTable(false);
	}
	
	/**
	 * Get the HTML of the cells in a table row, for a certain DB entry.
	 *
	 * @param object $entry A database entry of the current entity;
	 * @param bool|int $rowNumber The number shown to the user as row number (index);
	 * @return array Array of HTML cell contents.
	 * @throws \Throwable
	 */
	public function getRowViews(object $entry, bool|int $rowNumber = false): array
	{
		$rowItems = [];
		foreach ($this->columns as $column) {
			$rowItems[] = $this->getCellView($column, $entry, $rowNumber);
		}
		
		// Add the buttons as the last column
		if ($this->buttons->where('stack', 'line')->count()) {
			$rowItems[] = view('admin.panel.inc.button_stack', ['stack' => 'line'])
				->with('xPanel', $this)
				->with('entry', $entry)
				->with('row_number', $rowNumber)
				->render();
		}
		
		// Add the details_row buttons as the first column
		if ($this->detailsRow) {
			$detailsRowButton = view('admin.panel.columns.details_row_button')
				->with('xPanel', $this)
				->with('entry', $entry)
				->with('row_number', $rowNumber)
				->render();
			
			array_unshift($rowItems, $detailsRowButton);
			// $rowItems[0] = $detailsRowButton . $rowItems[0]; // buggy! @toRemove
		}
		
		return $rowItems;
	}
	
	/**
	 * Get the HTML of a cell, using the column types.
	 *
	 * @param array $column
	 * @param object $entry A database entry of the current entity;
	 * @param bool|int $rowNumber The number shown to the user as row number (index);
	 * @return string Returns HTML content
	 * @throws \Throwable
	 */
	public function getCellView(array $column, object $entry, bool|int $rowNumber = false): string
	{
		$cellViewName = $this->getCellViewName($column);
		
		return $this->renderCellView($cellViewName, $column, $entry, $rowNumber);
	}
	
	/**
	 * Get the name of the view to load for the cell.
	 *
	 * @param array $column
	 * @return string
	 */
	private function getCellViewName(array $column)
	{
		// Return custom column if view_namespace attribute is set
		if (isset($column['view_namespace']) && isset($column['type'])) {
			return $column['view_namespace'] . '.' . $column['type'];
		}
		
		if (isset($column['type'])) {
			// If the column has been overwritten return that one
			if (view()->exists('vendor.admin.panel.columns.' . $column['type'])) {
				return 'vendor.admin.panel.columns.' . $column['type'];
			}
			
			// Return the column from the package
			return 'admin.panel.columns.' . $column['type'];
		}
		
		// Fallback to text column
		return 'admin.panel.columns.text';
	}
	
	/**
	 * Render the given view.
	 *
	 * @param string $view
	 * @param array $column
	 * @param object $entry
	 * @param bool|int $rowNumber The number shown to the user as row number (index)
	 *
	 * @return string
	 * @throws \Throwable
	 */
	private function renderCellView(string $view, array $column, object $entry, bool|int $rowNumber = false)
	{
		if (!view()->exists($view)) {
			$view = 'admin.panel.columns.text'; // Fallback to text column
		}
		
		return view($view)
			->with('xPanel', $this)
			->with('column', $column)
			->with('entry', $entry)
			->with('rowNumber', $rowNumber)
			->render();
	}
	
	/**
	 * Created the array to be fed to the data table.
	 *
	 * @param $entries [Eloquent results].
	 * @param $totalRows
	 * @param $filteredRows
	 * @param bool|int $startIndex
	 * @return array
	 * @throws \Throwable
	 */
	public function getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows, int|bool $startIndex = false): array
	{
		$rows = [];
		
		foreach ($entries as $row) {
			$rowStartIndex = ($startIndex === false) ? false : ++$startIndex;
			$rows[] = $this->getRowViews($row, $rowStartIndex);
		}
		
		return [
			'draw'            => (isset($this->request['draw']) ? (int)$this->request['draw'] : 0),
			'recordsTotal'    => $totalRows,
			'recordsFiltered' => $filteredRows,
			'data'            => $rows,
		];
	}
}
