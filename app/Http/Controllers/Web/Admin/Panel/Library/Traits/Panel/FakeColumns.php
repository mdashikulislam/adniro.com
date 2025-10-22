<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

trait FakeColumns
{
	/**
	 * Returns an array of database columns names, that are used to store fake values.
	 * Or returns ['extras'] if no columns have been found.
	 *
	 * @param string $form The CRUD form. Can be 'create', 'update' or 'both'. Default is 'create'.
	 * @param int|string|null $id Optional entity ID needed in the case of the update form.
	 * @return array The fake columns array.
	 */
	public function getFakeColumnsAsArray(string $form = 'create', int|string|null $id = null): array
	{
		$fakeFieldColumnsToEncode = [];
		
		// Get the right fields according to the form type (create/update)
		$fields = $this->getFields($form, $id);
		
		$defaultFakeColumn = 'extras';
		
		foreach ($fields as $field) {
			$isFakeable = (bool)($field['fake'] ?? false);
			$fakeColumn = $field['store_in'] ?? $defaultFakeColumn;
			
			// If it's a fake field
			if ($isFakeable) {
				// Add it to the request in its appropriate variable - the one defined, if defined
				if (!empty($fakeColumn)) {
					if (!in_array($fakeColumn, $fakeFieldColumnsToEncode, true)) {
						$fakeFieldColumnsToEncode[] = $fakeColumn;
					}
				}
			}
		}
		
		if (!count($fakeFieldColumnsToEncode)) {
			return [$defaultFakeColumn];
		}
		
		return $fakeFieldColumnsToEncode;
	}
}
