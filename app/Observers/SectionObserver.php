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

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Common\JsonUtils;
use App\Models\Section;
use App\Observers\Traits\HasJsonColumn;

class SectionObserver extends BaseObserver
{
	use HasJsonColumn;
	
	/**
	 * Listen to the Entry updating event.
	 *
	 * @param \App\Models\Section $section
	 * @return void
	 */
	public function updating(Section $section)
	{
		$valuesColumn = 'field_values';
		if (isset($section->name) && isset($section->{$valuesColumn})) {
			// Get the original object values
			$original = $section->getOriginal();
			
			// Storage Disk Init.
			$disk = StorageDisk::getDisk();
			
			if (is_array($original) && array_key_exists($valuesColumn, $original)) {
				$original[$valuesColumn] = JsonUtils::jsonToArray($original[$valuesColumn]);
				
				// Remove old background_image_path from disk
				$this->deleteJsonPathFile(
					model: $section,
					column: $valuesColumn,
					path: 'background_image_path',
					filesystem: $disk,
					protectedPath: config('larapen.media.picture'),
					original: $original
				);
			}
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param \App\Models\Section $section
	 * @return void
	 */
	public function updated(Section $section)
	{
		//...
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param \App\Models\Section $section
	 * @return void
	 */
	public function saved(Section $section)
	{
		// ...
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param \App\Models\Section $section
	 * @return void
	 */
	public function deleted(Section $section)
	{
		// ...
	}
}
