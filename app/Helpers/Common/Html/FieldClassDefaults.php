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

namespace App\Helpers\Common\Html;

/*
 * $fieldBaseClass = FieldClassDefaults::for($type, $viewName);
 */

final class FieldClassDefaults
{
	/** @var string[] */
	private const CHECKBOX_RADIO_TYPES = ['checkbox', 'radio'];
	
	/** @var string[] */
	private const BUTTON_VIEWS = ['checkbox-btn', 'radio-btn'];
	
	/**
	 * Resolve the base Bootstrap class(es) for a form field.
	 *
	 * @param string $type The raw HTML `type` attribute (e.g. checkbox, radio, text, select …).
	 * @param string $viewName The Blade (or view) variant you are rendering
	 *                           (e.g. checkbox-btn, color-bs …).
	 * @return string            A space-separated list of Bootstrap classes.
	 */
	public static function for(string $type, string $viewName): string
	{
		// Special-cases first – makes the intent very clear.
		if (in_array($type, self::CHECKBOX_RADIO_TYPES, true)) {
			return in_array($viewName, self::BUTTON_VIEWS, true)
				? 'btn-check'                 // "button" style inputs
				: 'form-check-input';         // traditional check/radio inputs
		}
		
		// Single-purpose combinations.
		if ($type === 'select') {
			return 'form-select';
		}
		
		if ($type === 'color' && $viewName === 'color-bs') {
			return 'form-control form-control-color';
		}
		
		if ($type === 'range' && $viewName === 'range-bs') {
			return 'form-range';
		}
		
		// Fallback: most inputs share Bootstrap’s standard control class.
		return 'form-control';
	}
}

