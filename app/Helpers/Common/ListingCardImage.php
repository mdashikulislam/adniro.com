<?php

namespace App\Helpers\Common;

/*
 * Attributes for a listing card picture (adniro).
 *
 * The browser's own lazy loading covers the WebP <source> of a <picture> too; the "lazyload" class
 * only swapped the <img> source, so WebP thumbnails loaded at once. Width and height reserve the
 * space (the medium thumbnail size), so the page does not jump while pictures load.
 */

final class ListingCardImage
{
	/** @return array<string, string|int> */
	public static function attributes(string $class): array
	{
		return [
			'class'    => $class,
			'loading'  => 'lazy',
			'decoding' => 'async',
			'width'    => max(1, (int)config('settings.upload.img_resize_picture_md_width', 320)),
			'height'   => max(1, (int)config('settings.upload.img_resize_picture_md_height', 240)),
		];
	}
}
