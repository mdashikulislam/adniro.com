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

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Models\Category;

trait StyleTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return mixed
	 */
	public function styleUpdating($setting, $original)
	{
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		// Remove old body_background_image from disk
		$this->deleteJsonPathFile(
			model: $setting,
			column: 'field_values',
			path: 'body_background_image_path',
			filesystem: $disk,
			protectedPath: config('larapen.media.picture'),
			original: $original
		);
		
		return $this->applyLogoMaxDimensionsLimit($setting, $original);
	}
	
	/**
	 * Saved
	 *
	 * @param $setting
	 */
	public function styleSaved($setting)
	{
		$this->updateCategoriesPicturesPaths($setting);
	}
	
	/**
	 * Apply the logo's maximum dimensions limit
	 *
	 * @param $setting
	 * @param $original
	 * @return mixed
	 */
	private function applyLogoMaxDimensionsLimit($setting, $original)
	{
		if (!isset($setting->field_values)) {
			return $setting;
		}
		
		$fieldValues = $setting->field_values;
		
		// Logo Max. Dimensions
		$logoMaxWidth = config('larapen.media.resize.namedOptions.logo-max.width', 430);
		$logoMaxHeight = config('larapen.media.resize.namedOptions.logo-max.height', 80);
		if (!empty(config('settings.header.height'))) {
			$logoMaxHeight = forceToInt(config('settings.header.height'));
			if (empty($logoMaxHeight)) {
				$logoMaxHeight = 80;
			}
		}
		
		// Logo Default Dimensions
		$logoDefaultWidth = config('larapen.media.resize.namedOptions.logo.width', 216);
		$logoDefaultHeight = config('larapen.media.resize.namedOptions.logo.height', 40);
		
		// Logo Dimensions
		$logoWidth = forceToInt($fieldValues['logo_width'] ?? $logoDefaultWidth);
		$logoHeight = forceToInt($fieldValues['logo_height'] ?? $logoDefaultHeight);
		if (empty($logoWidth)) {
			$logoWidth = $logoDefaultWidth;
		}
		if (empty($logoHeight)) {
			$logoHeight = $logoDefaultHeight;
		}
		if ($logoWidth > $logoMaxWidth) {
			$logoWidth = $logoMaxWidth;
		}
		if ($logoHeight > $logoMaxHeight) {
			$logoHeight = $logoMaxHeight;
		}
		
		$fieldValues['logo_width'] = $logoWidth;
		$fieldValues['logo_height'] = $logoHeight;
		
		$setting->field_values = $fieldValues;
		
		return $setting;
	}
	
	/**
	 * @param $setting
	 */
	private function updateCategoriesPicturesPaths($setting): void
	{
		// If the Default Front Skin is changed, then update its assets paths (like categories pictures, etc.)
		$skin = $setting->field_values['skin'] ?? null;
		if (!empty($skin)) {
			$categories = Category::roots()->get();
			if ($categories->count() > 0) {
				foreach ($categories as $category) {
					$canSave = false;
					
					// If the Category contains a skinnable icon,
					// Change it by the selected skin icon.
					if (str_contains($category->image_path, 'app/categories/') && !str_contains($category->image_path, '/custom/')) {
						$pattern = '/app\/categories\/[^\/]+\//ui';
						$replacement = 'app/categories/' . $skin . '/';
						$picture = preg_replace($pattern, $replacement, $category->image_path);
						if (!empty($picture)) {
							$category->image_path = $picture;
							$canSave = true;
						}
					}
					
					// (Optional)
					// If the Category contains a skinnable default icon,
					// Change it by the selected skin default icon.
					if (str_contains($category->image_path, 'app/default/categories/fa-folder-')) {
						$pattern = '/app\/default\/categories\/fa-folder-[^.]+\./ui';
						$replacement = 'app/default/categories/fa-folder-' . $skin . '.';
						$picture = preg_replace($pattern, $replacement, $category->image_path);
						if (!empty($picture)) {
							$category->image_path = $picture;
							$canSave = true;
						}
					}
					
					if ($canSave) {
						$category->save();
					}
				}
			}
		}
	}
}
