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
 * Usage: WrapperClassDefaults::for($formLayout);
 *
 * Return the default CSS classes for the element that *wraps* a form field,
 * based on the overall form layout (default, horizontal, inline …).
 *
 * Add new layouts by extending the `MAP` array or by subclassing / replacing
 * this class (e.g. TailwindWrapperClassDefaults).
 */

final class WrapperClassDefaults
{
	// Canonical keys used throughout your codebase
	public const LAYOUT_DEFAULT = 'default';
	public const LAYOUT_HORIZONTAL = 'horizontal';
	// e.g. public const LAYOUT_INLINE = 'inline';
	
	/** @var array<string,string>  Map <layout-key> → <CSS class list> */
	private const MAP = [
		self::LAYOUT_DEFAULT    => 'mb-3 col-md-12',
		self::LAYOUT_HORIZONTAL => 'mb-3 row',
		// self::LAYOUT_INLINE     => 'mb-2 col-auto',
	];
	
	/**
	 * Get the class list for the requested layout.
	 *
	 * Falls back to the “default” layout when an unknown key is supplied.
	 */
	public static function for(string $layout): string
	{
		return self::MAP[$layout] ?? self::MAP[self::LAYOUT_DEFAULT];
	}
}
