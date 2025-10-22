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

class SearchFormPreset extends BaseSection
{
	public static function defaultPreset(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			'enable_extended_form_area' => '1',
			'background_image_path'     => null,
			'background_image_darken'   => 0.0,
			'height'                    => '525',
			'margins'                   => self::getDefaultMarginConfiguration(),
			'prevent_header_overlap'    => '1',
			'full_height'               => '0',
			'scroll_cue'                => null,
		];
		
		$animationFields = ['title', 'subtitle', 'searchbar'];
		$animationAttributes = [
			"animation"           => null,
			"animation_easing"    => null,
			"animation_duration"  => null,
			"animation_delay"     => null,
			"animation_offset"    => null,
			"animation_placement" => null,
		];
		foreach ($animationFields as $fieldName) {
			foreach ($animationAttributes as $attrKey=> $attrValue) {
				$defaultValue["{$fieldName}_{$attrKey}"] = $attrValue;
			}
		}
		
		return array_merge($value, $defaultValue);
	}
	
	public static function noHero(array $value = [], ?Storage $disk = null): array
	{
		$margins = [
			[
				'side'       => 'mt',
				'breakpoint' => null,
				'size'       => '4',
			],
		];
		
		$defaultValue = [
			'enable_extended_form_area' => '0',
			'prevent_header_overlap'    => '1',
			'margins'                   => array_merge(self::getDefaultMarginConfiguration(), $margins),
			'form_border_radius'        => '8',
		];
		
		return array_merge($value, $defaultValue);
	}
	
	public static function fullHeightHero(array $value = [], ?Storage $disk = null): array
	{
		$titlePrefix = 'title_';
		$subtitlePrefix = 'subtitle_';
		$searchBarPrefix = 'searchbar_';
		
		$defaultValue = [
			'enable_extended_form_area' => '1',
			'background_image_path'     => null,
			'background_image_darken'   => 0.0,
			'height'                    => '525',
			'margins'                   => self::getDefaultMarginConfiguration(),
			'form_border_radius'        => null,
			'prevent_header_overlap'    => '0', // overlapped
			'full_height'               => '1',
			'scroll_cue'                => 'mouseGraphic', // 'mouseGraphic', 'chevronText'
			
			"{$titlePrefix}animation"           => 'fade-right',
			"{$titlePrefix}animation_easing"    => null,
			"{$titlePrefix}animation_duration"  => null,
			"{$titlePrefix}animation_delay"     => null,
			"{$titlePrefix}animation_offset"    => null,
			"{$titlePrefix}animation_placement" => null,
			
			"{$subtitlePrefix}animation"           => 'fade-left',
			"{$subtitlePrefix}animation_easing"    => null,
			"{$subtitlePrefix}animation_duration"  => null,
			"{$subtitlePrefix}animation_delay"     => null,
			"{$subtitlePrefix}animation_offset"    => null,
			"{$subtitlePrefix}animation_placement" => null,
			
			"{$searchBarPrefix}animation"           => 'zoom-in-up',
			"{$searchBarPrefix}animation_easing"    => null,
			"{$searchBarPrefix}animation_duration"  => null,
			"{$searchBarPrefix}animation_delay"     => null,
			"{$searchBarPrefix}animation_offset"    => null,
			"{$searchBarPrefix}animation_placement" => null,
		];
		
		return array_merge($value, $defaultValue);
	}
	
	public static function overlappedNavbar(array $value = [], ?Storage $disk = null): array
	{
		$titlePrefix = 'title_';
		$subtitlePrefix = 'subtitle_';
		$searchBarPrefix = 'searchbar_';
		
		$defaultValue = [
			'enable_extended_form_area' => '1',
			'background_image_path'     => null,
			'background_image_darken'   => 0.0,
			'height'                    => '600',
			'margins'                   => self::getDefaultMarginConfiguration(),
			'form_border_radius'        => null,
			'prevent_header_overlap'    => '0', // overlapped
			'full_height'               => '0',
			'scroll_cue'                => 'mouseGraphic',
			
			"{$titlePrefix}animation"           => 'fade-right',
			"{$titlePrefix}animation_easing"    => null,
			"{$titlePrefix}animation_duration"  => null,
			"{$titlePrefix}animation_delay"     => null,
			"{$titlePrefix}animation_offset"    => null,
			"{$titlePrefix}animation_placement" => null,
			
			"{$subtitlePrefix}animation"           => 'fade-left',
			"{$subtitlePrefix}animation_easing"    => null,
			"{$subtitlePrefix}animation_duration"  => null,
			"{$subtitlePrefix}animation_delay"     => null,
			"{$subtitlePrefix}animation_offset"    => null,
			"{$subtitlePrefix}animation_placement" => null,
			
			"{$searchBarPrefix}animation"           => 'zoom-in-up',
			"{$searchBarPrefix}animation_easing"    => null,
			"{$searchBarPrefix}animation_duration"  => null,
			"{$searchBarPrefix}animation_delay"     => null,
			"{$searchBarPrefix}animation_offset"    => null,
			"{$searchBarPrefix}animation_placement" => null,
		];
		
		return array_merge($value, $defaultValue);
	}
}
