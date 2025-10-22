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
 * $attributes ??= [];
 *
 * ➊ Guarantee every Select2 element has the class `select2-ajax`
 * $attributes = HtmlAttr::prepend($attributes, 'class', 'select2-ajax');
 *
 * ➋ Add two utility classes at once
 * $attributes = HtmlAttr::append($attributes, 'class', ['w-100', 'my-2']);
 *
 * ➌ Ensure a default `data-theme`, but don't overwrite an explicit one
 * $attributes = HtmlAttr::default($attributes, 'data-theme', 'light');
 */

final class HtmlAttr
{
	/**
	 * Add one or more values **in front of** the existing attribute.
	 *
	 * @param array $attrs Current attribute array (`['class' => '…']` …)
	 * @param string $key Attribute to mutate (`class`, `data-*`, `style`, …)
	 * @param string|array $value Value(s) to prepend
	 * @param string $separator How the individual parts are separated in that attribute
	 */
	public static function prepend(
		array        $attrs,
		string       $key,
		string|array $value,
		string       $separator = ' '
	): array
	{
		return self::merge($attrs, $key, $value, $separator, prepend: true);
	}
	
	/**
	 * Add one or more values **after** the existing attribute.
	 */
	public static function append(
		array        $attrs,
		string       $key,
		string|array $value,
		string       $separator = ' '
	): array
	{
		return self::merge($attrs, $key, $value, $separator, prepend: false);
	}
	
	/**
	 * Set a value only when the attribute is missing or empty.
	 */
	public static function default(
		array  $attrs,
		string $key,
		string $value
	): array
	{
		if (($attrs[$key] ?? '') === '') {
			$attrs[$key] = $value;
		}
		
		return $attrs;
	}
	
	/* --------------------------------------------------------------------- */
	
	/**
	 * Internal merge helper – keeps prepend/append DRY.
	 *
	 * @noinspection PhpPureAttributeCanBeAddedInspection
	 */
	private static function merge(
		array        $attrs,
		string       $key,
		string|array $value,
		string       $separator,
		bool         $prepend
	): array
	{
		$incoming = \is_array($value) ? \implode($separator, $value) : $value;
		$existing = $attrs[$key] ?? '';
		
		$attrs[$key] = \trim(
			$prepend
				? $incoming . $separator . $existing
				: $existing . $separator . $incoming
		);
		
		return $attrs;
	}
}

