<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Models\HasSettings\Presets\Section;

use App\Models\Section\BaseSection;
use Illuminate\Support\Facades\Storage;

class BlogPreset extends BaseSection
{
	public static function defaultPreset(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			'max_items'              => '3',
			'cache_expiration'       => getGlobalCacheTtl(),
			'show_view_more_btn'     => '1',
			'featured_first'         => '1',
			'margins'                => self::getDefaultMarginConfiguration(),
			'prevent_header_overlap' => '1',
			'full_height'            => '0',
			'animation'              => null,
			'animation_easing'       => null,
			'animation_duration'     => null,
			'animation_delay'        => null,
			'animation_offset'       => null,
			'animation_placement'    => null,
		];

		return array_merge($value, $defaultValue);
	}
}
