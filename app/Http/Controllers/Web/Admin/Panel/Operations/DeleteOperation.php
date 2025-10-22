<?php

namespace App\Http\Controllers\Web\Admin\Panel\Operations;

trait DeleteOperation
{
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param $id
	 * @return int
	 */
	public function destroy($id)
	{
		$this->xPanel->hasAccessOrFail('delete');
		
		// Get entry ID from Request (makes sure it's the last ID for nested resources)
		// $id = $this->xPanel->getCurrentEntryId() ?? $id;
		
		// Get the right resource identifier
		$id = $this->xPanel->getResourceIdentifier($id);
		
		return $this->xPanel->delete($id);
	}
	
	/**
	 * Delete multiple entries in one go.
	 *
	 * Info: Not used. Check out the BulkActionsOperation file
	 *
	 * @return array
	 */
	public function bulkDelete()
	{
		$this->xPanel->hasAccessOrFail('delete');
		
		$ids = $this->request->input('entryId');
		$ids = is_array($ids) ? $ids : [];
		
		$deletedEntries = [];
		foreach ($ids as $id) {
			if ($entry = $this->xPanel->model->find($id)) {
				$deletedEntries[] = $entry->delete();
			}
		}
		
		return $deletedEntries;
	}
}
