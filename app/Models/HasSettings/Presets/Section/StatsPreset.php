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

class StatsPreset extends BaseSection
{
	public static function defaultPreset(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			'cache_expiration'       => getGlobalCacheTtl(),
			'icon_count_listings'    => 'bi bi-megaphone',
			'icon_count_users'       => 'bi bi-people',
			'icon_count_locations'   => 'bi bi-geo-alt',
			'counter_up_delay'       => 10,
			'counter_up_time'        => 2000,
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
			'animation'              => 'fade-up',
			'animation_easing'       => null,
			'animation_duration'     => 1000,
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
