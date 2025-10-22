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

namespace App\Observers\Traits\Setting;

use App\Models\MenuItem;
use App\Models\Post;

trait ListingFormTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return void
	 */
	public function listingFormUpdating($setting, $original)
	{
		$this->listingFormTypeConfiguration($setting, $original);
		$this->autoReviewedExistingPostsIfApprobationIsEnabled($setting);
	}
	
	/**
	 * @param $setting
	 * @param $original
	 * @return void
	 */
	private function listingFormTypeConfiguration($setting, $original): void
	{
		$publicationFormType = $setting->field_values['publication_form_type'] ?? null;
		$publicationFormTypeOld = $original['field_values']['publication_form_type'] ?? null;
		
		if ($publicationFormType == $publicationFormTypeOld) {
			return;
		}
		
		$msRouteName = 'listing.create.ms.showForm';
		$ssRouteName = 'listing.create.ss.showForm';
		
		if ($publicationFormType == 'multi-steps-form') {
			$menuItems = MenuItem::query()->where('route_name', $ssRouteName);
			if ($menuItems->count() > 0) {
				$menuItems->update(['route_name' => $msRouteName]);
			}
		}
		if ($publicationFormType == 'single-step-form') {
			$menuItems = MenuItem::query()->where('route_name', $msRouteName);
			if ($menuItems->count() > 0) {
				$menuItems->update(['route_name' => $ssRouteName]);
			}
		}
	}
	
	/**
	 * Auto approve all the existing listings,
	 * If the Posts Approbation feature is enabled
	 *
	 * @param $setting
	 * @return void
	 */
	private function autoReviewedExistingPostsIfApprobationIsEnabled($setting): void
	{
		// Enable Posts Approbation by User Admin (Post Review)
		// If Listing Approbation is enabled,
		// then set the reviewed field to "true" for all the existing Posts
		$listingsReviewActivation = (int)($setting->field_values['listings_review_activation'] ?? null);
		if ($listingsReviewActivation == 1) {
			Post::whereNull('reviewed_at')->update(['reviewed_at' => now()]);
		}
	}
}
