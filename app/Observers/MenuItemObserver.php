<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Observers;

use App\Models\MenuItem;
use App\Observers\Traits\HasNestedColumns;
use Throwable;

class MenuItemObserver extends BaseObserver
{
	use HasNestedColumns;
	
	/**
	 * Listen to the Entry creating event.
	 *
	 * @param MenuItem $menuItem
	 * @return void
	 */
	public function creating(MenuItem $menuItem)
	{
		// Apply the nested created actions
		return $this->creatingNestedItem($menuItem);
	}
	
	/**
	 * Listen to the Entry updating event.
	 *
	 * @param MenuItem $menuItem
	 * @return void
	 */
	public function updating(MenuItem $menuItem)
	{
		// Apply the nested updating actions
		return $this->updatingNestedItem($menuItem);
	}
	
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param MenuItem $menuItem
	 * @return void
	 */
	public function deleting($menuItem)
	{
		// Apply the nested deleting actions
		$this->deletingNestedItem($menuItem);
		
		// Delete the category's children recursively
		$this->deleteChildrenRecursively($menuItem);
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param MenuItem $menuItem
	 * @return void
	 */
	public function saved(MenuItem $menuItem)
	{
		$this->listingFormTypeConfiguration($menuItem);
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param MenuItem $menuItem
	 * @return void
	 */
	public function deleted(MenuItem $menuItem)
	{
		// ...
	}
	
	/**
	 * @param \App\Models\MenuItem $menuItem
	 * @return void
	 */
	private function listingFormTypeConfiguration(MenuItem $menuItem): void
	{
		if (!isset($menuItem->route_name)) return;
		
		$msRouteName = 'listing.create.ms.showForm';
		$ssRouteName = 'listing.create.ss.showForm';
		
		if (isMultipleStepsFormEnabled()) {
			if ($menuItem->route_name == $ssRouteName) {
				$menuItem->route_name = $msRouteName;
				$menuItem->saveQuietly();
			}
		}
		if (isSingleStepFormEnabled()) {
			if ($menuItem->route_name == $msRouteName) {
				$menuItem->route_name = $ssRouteName;
				$menuItem->saveQuietly();
			}
		}
	}
}
