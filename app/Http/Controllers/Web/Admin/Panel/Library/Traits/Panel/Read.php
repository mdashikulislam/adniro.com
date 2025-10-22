<?php

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel;

use App\Jobs\GeneratePostCollectionThumbnails;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

trait Read
{
	/*
	|--------------------------------------------------------------------------
	|                                   READ
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Find and retrieve the id of the current entry.
	 *
	 * @return float|int|string|null
	 */
	public function getCurrentEntryId($defaultIdentifier = null)
	{
		if ($this->entry) {
			return $this->entry->getKey();
		}
		
		$parameters = Route::current()->parameters();
		
		// Use the entity name to get the current entry
		// This makes sure the ID is correct even for nested resources
		// Otherwise use the next to last parameter
		// Otherwise return null
		// $identifier = array_values($parameters)[count($parameters) - 1] ?? null;
		$identifier = end($parameters);
		$identifier = $this->request->{$this->entityName} ?? $identifier;
		
		return (is_numeric($identifier) || is_string($identifier))
			? $identifier
			: $defaultIdentifier;
	}
	
	/**
	 * Find and retrieve the current entry.
	 *
	 * @return \Illuminate\Database\Eloquent\Model|null The row in the db or false.
	 */
	public function getCurrentEntry()
	{
		$id = $this->getCurrentEntryId();
		
		if (empty($id)) {
			return null;
		}
		
		return $this->getEntry($id);
	}
	
	/**
	 * Find and retrieve an entry in the database or fail.
	 *
	 * @param int|string|null $id
	 * @return mixed
	 */
	public function getEntry(int|string|null $id)
	{
		if (!$this->entry && !empty($id)) {
			/**
			 * @var \App\Models\Setting $entry (for example)
			 * To make some methods clickable in IDE (i.e. PhpStorm), we need a model that uses specific traits.
			 * The Setting model uses the CRUD/xPanel traits.
			 * So that needs to be changed in other projects where Setting doesn't exist or no longer use these traits.
			 */
			$entry = $this->model->findOrFail($id);
			$this->entry = $entry->withFakes();
		}
		
		return $this->entry;
	}
	
	/**
	 * Make the query JOIN all relationships used in the columns, too,
	 * so there will be less database queries overall.
	 *
	 * @return void
	 */
	public function autoEagerLoadRelationshipColumns(): void
	{
		$relationships = $this->getColumnsRelationships();
		if (count($relationships) > 0) {
			$this->with($relationships);
		}
	}
	
	/**
	 * Get all entries from the database.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function getEntries()
	{
		$this->autoEagerLoadRelationshipColumns();
		
		$entries = $this->query->get();
		
		// Generate posts images thumbnails
		if ($this->getModel()->getTable() == 'posts') {
			GeneratePostCollectionThumbnails::dispatch($entries);
		}
		
		// Add the fake columns for each entry
		foreach ($entries as $entry) {
			/** @var \App\Models\Page $entry (for example) */
			$entry->addFakes($this->getFakeColumnsAsArray());
		}
		
		return $entries;
	}
	
	/**
	 * Get the fields for the create or update forms.
	 *
	 * @param $form
	 * @param int|string|null $id
	 * @return array
	 */
	public function getFields($form, int|string|null $id = null)
	{
		return match (strtolower($form)) {
			'update' => $this->getUpdateFields($id),
			default  => $this->getCreateFields(),
		};
	}
	
	/**
	 * Check if the create/update form has upload fields.
	 * Upload fields are the ones that have "upload" => true defined on them.
	 *
	 * @param $form
	 * @param int|string|null $id
	 * @return bool
	 */
	public function hasUploadFields($form, int|string|null $id = null): bool
	{
		$fields = $this->getFields($form, $id);
		$uploadFields = Arr::where($fields, function ($item) {
			return isset($item['upload']) && $item['upload'] == true;
		});
		
		return (count($uploadFields) > 0);
	}
	
	/**
	 * Enable the DETAILS ROW functionality:.
	 *
	 * In the table view, show a plus sign next to each entry.
	 * When clicking that plus sign, an AJAX call will bring whatever content you want from the EntityCrudController::showDetailsRow($id) and show it to the user.
	 */
	public function enableDetailsRow(): void
	{
		$this->detailsRow = true;
	}
	
	/**
	 * Disable the DETAILS ROW functionality:.
	 */
	public function disableDetailsRow(): void
	{
		$this->detailsRow = false;
	}
	
	/**
	 * Set the number of rows that should be shown on the table page (list view).
	 *
	 * @param $value
	 */
	public function setDefaultPageLength($value): void
	{
		$this->defaultPageLength = $value;
	}
	
	/**
	 * Get the number of rows that should be shown on the table page (list view).
	 *
	 * @return int
	 */
	public function getDefaultPageLength(): int
	{
		// return the custom value for this crud panel, if set using setPageLength()
		if ($this->defaultPageLength) {
			return (int)$this->defaultPageLength;
		}
		
		// otherwise return the default value in the config file
		if (config('larapen.admin.default_page_length')) {
			return (int)config('larapen.admin.default_page_length');
		}
		
		return 25;
	}
	
	public function enableSearchBar(): void
	{
		$this->hideSearchBar = false;
	}
	
	public function disableSearchBar(): void
	{
		$this->hideSearchBar = true;
	}
	
	/*
	|--------------------------------------------------------------------------
	|                                EXPORT BUTTONS
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Tell the list view to show the DataTables export buttons.
	 */
	public function enableExportButtons(): void
	{
		$this->exportButtons = true;
	}
	
	/**
	 * Check if export buttons are enabled for the table view.
	 *
	 * @return bool
	 */
	public function exportButtons(): bool
	{
		return $this->exportButtons;
	}
}
