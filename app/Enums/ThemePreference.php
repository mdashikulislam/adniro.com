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

namespace App\Enums;

enum ThemePreference: string
{
	use EnumToArray;
	
	case LIGHT = 'light';
	case DARK = 'dark';
	case SYSTEM = 'system';
	
	public function label(): string
	{
		return match ($this) {
			self::LIGHT => trans('enum.theme_light'),
			self::DARK => trans('enum.theme_dark'),
			self::SYSTEM => trans('enum.theme_system'),
		};
	}
}
