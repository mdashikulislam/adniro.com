<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

use App\Http\Requests\Admin\Request as StoreRequest;

trait CreateOperation
{
	/**
	 * Show the form for creating inserting a new row.
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create()
	{
		$this->xPanel->hasAccessOrFail('create');
		
		// Prepare the fields you need to show
		$this->data['xPanel'] = $this->xPanel;
		$this->data['saveAction'] = $this->getSaveAction();
		$this->data['fields'] = $this->xPanel->getCreateFields();
		$this->data['title'] = trans('admin.add') . ' ' . $this->xPanel->entityName;
		
		return view($this->xPanel->getCreateView(), $this->data);
	}
	
	/**
	 * Store a newly created resource in the database.
	 *
	 * @param StoreRequest|null $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function storeCrud(StoreRequest $request = null)
	{
		$this->xPanel->hasAccessOrFail('create');
		
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
			
			// Insert item in the DB
			$item = $this->xPanel->create($request->except(['redirect_after_save', '_token']));
			
			if (empty($item)) {
				notification(trans('admin.error_saving_entry'), 'error');
				
				return back();
			}
			
			// Show a success message
			notification(trans('admin.insert_success'), 'success');
			
			// Save the redirect choice for next time
			$this->setSaveAction();
			
			return $this->performSaveAction($item->getKey());
		} catch (\Throwable $e) {
			notification($e->getMessage(), 'error');
			
			return redirect()->to($this->xPanel->getUrl());
		}
	}
}
