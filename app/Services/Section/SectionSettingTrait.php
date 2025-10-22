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

namespace App\Services\Section;

trait SectionSettingTrait
{
	/**
	 * @param array|null $values
	 * @return array|null
	 */
	protected function searchFormSettings(?array $values = []): ?array
	{
		// Load Country's Background Image
		$countryBackgroundImage = config('country.background_image_path');
		if (isset($this->disk)) {
			if (!empty($countryBackgroundImage) && $this->disk->exists($countryBackgroundImage)) {
				$values['background_image_path'] = $countryBackgroundImage;
			}
		}
		
		$appLocale = config('app.locale');
		
		// Title: Count Posts & Users
		if (!empty($values['title_' . $appLocale])) {
			$title = $values['title_' . $appLocale];
			$title = replaceGlobalPatterns($title);
			
			$values['title_' . $appLocale] = $title;
		}
		
		// SubTitle: Count Posts & Users
		if (!empty($values['sub_title_' . $appLocale])) {
			$subTitle = $values['sub_title_' . $appLocale];
			$subTitle = replaceGlobalPatterns($subTitle);
			
			$values['sub_title_' . $appLocale] = $subTitle;
		}
		
		return $values;
	}
}
