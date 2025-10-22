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

namespace App\Models\HasSettings\Presets\Section;

use App\Models\Section\BaseSection;
use Illuminate\Support\Facades\Storage;

class LocationsPreset extends BaseSection
{
	public static function defaultPreset(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			'show_cities'            => '1',
			'max_items'              => '19', // 14 (when 'enable_map' = 1) or 19 (when 'enable_map' = 0)
			'cache_expiration'       => getGlobalCacheTtl(),
			'show_listing_btn'       => '1',
			'enable_map'             => $value['show_map'] ?? '0',
			'map_width'              => '300',
			'map_height'             => '300',
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
	
	public static function noHero(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
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
	
	public static function fullHeightHero(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			'margins'                => self::getDefaultMarginConfiguration(),
			'prevent_header_overlap' => '1',
			'full_height'            => '0',
			'animation'              => 'fade-right',
			'animation_easing'       => null,
			'animation_duration'     => 1500,
			'animation_delay'        => null,
			'animation_offset'       => null,
			'animation_placement'    => null,
		];
		
		return array_merge($value, $defaultValue);
	}
	
	public static function overlappedNavbar(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
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
