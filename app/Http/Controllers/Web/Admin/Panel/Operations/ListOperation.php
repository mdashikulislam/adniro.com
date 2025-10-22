<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

trait ListOperation
{
	/**
	 * Display all rows in the database for this entity.
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index()
	{
		$this->xPanel->hasAccessOrFail('list');
		
		$this->data['xPanel'] = $this->xPanel;
		$this->data['title'] = ucfirst($this->xPanel->entityNamePlural);
		
		// Get all entries if AJAX is not enabled
		if (!$this->data['xPanel']->ajaxTable) {
			$this->data['entries'] = $this->data['xPanel']->getEntries();
		}
		
		return view($this->xPanel->getListView(), $this->data);
	}
	
	/**
	 * The search function that is called by the data table.
	 *
	 * @return mixed [JSON] Array of cells in HTML form.
	 * @throws \Throwable
	 */
	public function search()
	{
		$this->xPanel->hasAccessOrFail('list');
		
		$totalRows = $filteredRows = $this->xPanel->count();
		
		// if a search term was present
		if ($this->request->input('search') && $this->request->input('search')['value']) {
			// filter the results accordingly
			$this->xPanel->applySearchTerm($this->request->input('search')['value']);
			// recalculate the number of filtered rows
			$filteredRows = $this->xPanel->count();
		}
		
		// start the results according to the datatables pagination
		if ($this->request->input('start')) {
			$this->xPanel->skip($this->request->input('start'));
		}
		
		// limit the number of results according to the datatables pagination
		if ($this->request->input('length')) {
			$this->xPanel->take($this->request->input('length'));
		}
		
		// overwrite any order set in the setup() method with the datatables order
		if ($this->request->input('order')) {
			$column_number = $this->request->input('order')[0]['column'];
			if ($this->xPanel->detailsRow) {
				$column_number = $column_number - 1;
			}
			$column_direction = $this->request->input('order')[0]['dir'];
			$column = $this->xPanel->findColumnById($column_number);
			
			if ($column['tableColumn']) {
				$this->xPanel->orderBy($column['name'], $column_direction);
			}
		}
		
		$entries = $this->xPanel->getEntries();
		
		return $this->xPanel->getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows);
	}
	
	/**
	 * Used with AJAX in the list view (datatables) to show extra information about that row that didn't fit in the table.
	 * It defaults to showing all connected translations and their CRUD buttons.
	 *
	 * It's enabled by:
	 * - setting the $crud['details_row'] variable to true;
	 * - adding the details route for the entity; ex: Route::get('page/{id}/details', 'PageCrudController@showDetailsRow');
	 *
	 * @param $id
	 * @param null $childId
	 * @return \Illuminate\View\View
	 */
	public function showDetailsRow($id, $childId = null)
	{
		$this->xPanel->hasAccessOrFail('list'); // 'details_row' or 'list'
		
		if (!empty($childId)) {
			$id = $childId;
		}
		
		// Get the info for that entry
		$this->data['xPanel'] = $this->xPanel;
		$this->data['entry'] = $this->xPanel->model->find($id);
		
		return view($this->xPanel->getDetailsRowView(), $this->data);
	}
}
