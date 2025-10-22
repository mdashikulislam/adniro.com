<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

trait ShowOperation
{
	/**
	 * Display the specified resource.
	 *
	 * @param $id
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show($id)
	{
		$this->xPanel->hasAccessOrFail('show');
		
		// Get entry ID from Request (makes sure it's the last ID for nested resources)
		// $id = $this->xPanel->getCurrentEntryId() ?? $id;
		
		// Get the right resource identifier
		$id = $this->xPanel->getResourceIdentifier($id);
		
		// Set columns from DB
		$this->xPanel->setFromDb();
		
		// Cycle through columns
		foreach ($this->xPanel->columns as $column) {
			// Remove any autoset relationship columns
			if (array_key_exists('model', $column) && array_key_exists('autoset', $column) && $column['autoset']) {
				$this->xPanel->removeColumn($column['name']);
			}
			
			// Remove the row_number column, since it doesn't make sense in this context
			if ($column['type'] == 'row_number') {
				$this->xPanel->removeColumn($column['name']);
			}
		}
		
		// Get the info for that entry
		$this->data['entry'] = $this->xPanel->getEntry($id);
		$this->data['xPanel'] = $this->xPanel;
		$this->data['title'] = trans('admin.preview') . ' ' . $this->xPanel->entityName;
		
		// Remove preview button from stack:line
		$this->xPanel->removeButton('show');
		$this->xPanel->removeButton('delete');
		
		// Remove bulk actions columns
		$this->xPanel->removeColumns(['blank_first_column', 'bulk_actions']);
		
		return view($this->xPanel->getShowView(), $this->data);
	}
}
