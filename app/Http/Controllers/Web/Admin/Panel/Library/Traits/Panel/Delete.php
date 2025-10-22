<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

trait Delete
{
	/*
	|--------------------------------------------------------------------------
	|                                   DELETE
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Delete a row from the database.
	 *
	 * @param int|string $id The id of the item to be deleted.
	 * @return string True if the item was deleted.
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the model was not found.
	 *
	 * TODO: should this delete items with relations to it too?
	 */
	public function delete(int|string $id)
	{
		/** @var \Illuminate\Database\Eloquent\Model $model */
		$model = $this->model;
		
		// return $model->destroy($id);
		
		/** @var \App\Models\Page $entry (for example) */
		$entry = $model->findOrFail($id);
		
		return (string)$entry->delete();
	}
}
