<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

use App\Http\Requests\Admin\Request as UpdateRequest;

trait UpdateOperation
{
	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param $id
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function edit($id)
	{
		$this->xPanel->hasAccessOrFail('update');
		
		// Get entry ID from Request (makes sure it's the last ID for nested resources)
		// $id = $this->xPanel->getCurrentEntryId() ?? $id;
		
		// Get the right resource identifier
		$id = $this->xPanel->getResourceIdentifier($id);
		
		// Get the info for that entry
		$this->data['entry'] = $this->xPanel->getEntry($id);
		$this->data['xPanel'] = $this->xPanel;
		$this->data['saveAction'] = $this->getSaveAction();
		$this->data['fields'] = $this->xPanel->getUpdateFields($id);
		$this->data['title'] = trans('admin.edit') . ' ' . $this->xPanel->entityName;
		
		$this->data['id'] = $id;
		
		return view($this->xPanel->getEditView(), $this->data);
	}
	
	/**
	 * Update the specified resource in the database.
	 *
	 * @param UpdateRequest|null $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function updateCrud(UpdateRequest $request = null)
	{
		$this->xPanel->hasAccessOrFail('update');
		
		// Fallback to global request instance
		if (is_null($request)) {
			$request = request()->instance();
		}
		
		try {
			// Replace empty values with NULL, so that it will work with MySQL strict mode on
			foreach ($request->input() as $key => $value) {
				if (is_string($value) && trim($value) === '') {
					$request->request->set($key, null);
				}
			}
			
			// Update the row in the DB
			$item = $this->xPanel->update(
				$request->get($this->xPanel->model->getKeyName()),
				$request->except('redirect_after_save', '_token')
			);
			
			if (empty($item)) {
				notification(trans('admin.error_saving_entry'), 'error');
				
				return back();
			}
			
			// Retrieve the 'was_manually_changed' attribute value
			// Check the syncPivot() method in
			// app/Http/Controllers/Web/Admin/Panel/Library/Traits/Panel/Create.php
			$wasManuallyChanged = $item->was_manually_changed ?? false;
			
			if (!$item->wasChanged() && !$wasManuallyChanged) {
				notification(t('observer_nothing_has_changed'), 'warning');
				
				return redirect()->back()->withInput();
			}
			
			// Show a success message
			notification(trans('admin.update_success'), 'success');
			
			// Save the redirect choice for next time
			$this->setSaveAction();
			
			return $this->performSaveAction($item->getKey());
		} catch (\Throwable $e) {
			notification($e->getMessage(), 'error');
			
			return redirect()->to($this->xPanel->getUrl());
		}
	}
}
