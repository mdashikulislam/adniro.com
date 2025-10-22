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

namespace App\Models\HasSettings\Presets\Setting;

use App\Models\Setting\BaseSetting;
use Illuminate\Support\Facades\Storage;

class HeaderPreset extends BaseSetting
{
	public static function defaultPreset(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			// For dynamic sectionable pages
			'default_width_type'                     => 'default', // default, full-width, boxed
			'default_height'                         => '75', // 80 / 75 / 65 / 60
			'default_margins'                        => [],
			'default_paddings'                       => [],
			'default_dark'                           => '0',
			'default_shadow'                         => '0',
			'default_animation'                      => '1',
			'default_background_color_class'         => 'bg-body-tertiary', // 'bg-body-tertiary', 'bg-transparent'
			'default_background_color'               => null, // '#f8f9fA'
			'default_border'                         => null, // border
			'default_border_width'                   => null, // 'border-1' (for 1px)
			'default_border_color_class'             => null,
			'default_border_color'                   => null, // '#dee2e6'
			'default_rounded'                        => [], // rounded-3
			'default_link_color_class'               => 'link-body-emphasis', // 'link-body-emphasis', 'link-light'
			'default_link_color'                     => null,
			'default_link_hover_color'               => null,
			'default_text_color_class'               => null,
			'default_text_color'                     => null,
			'default_item_shadow'                    => '0',
			'default_expanded_background_color_class'      => 'bg-body-tertiary', // collapsed/expanded: 'bg-body-tertiary'
			
			// For non-dynamic sectionable pages
			'static_recopy_default'                  => '0', // Recopy the default header options
			'static_width_type'                      => 'default', // default, full-width, boxed
			'static_height'                          => '75',
			'static_margins'                         => [],
			'static_paddings'                        => [],
			'static_dark'                            => '0',
			'static_shadow'                          => '0',
			'static_animation'                       => '1',
			'static_background_color_class'          => 'bg-body-tertiary', // bg-body-tertiary
			'static_background_color'                => null, // '#f8f9fA'
			'static_border'                          => 'border-bottom',
			'static_border_width'                    => 'border-1', // 'border-1' (for 1px)
			'static_border_color_class'              => null,
			'static_border_color'                    => null, // '#dee2e6'
			'static_rounded'                         => [], // rounded-3
			'static_link_color_class'                => null,
			'static_link_color'                      => null,
			'static_link_hover_color'                => null,
			'static_text_color_class'                => null,
			'static_text_color'                      => null,
			'static_item_shadow'                     => '0',
			'static_expanded_background_color_class' => 'bg-body-tertiary',
			
			// Fixed or sticky header
			'fixed_top'                              => '1',
			'fixed_height_offset'                    => 200,
			'fixed_width_type'                       => 'default', // default, full-width, boxed
			'fixed_height'                           => '75',
			'fixed_margins'                          => [],
			'fixed_paddings'                         => [],
			'fixed_dark'                             => '0',
			'fixed_shadow'                           => '1',
			'fixed_animation'                        => '1',
			'fixed_background_color_class'           => 'bg-body-tertiary', // bg-body-tertiary, bg-primary
			'fixed_background_color'                 => null,
			'fixed_border'                           => null,
			'fixed_border_width'                     => null, // 'border-1' (for 1px)
			'fixed_border_color_class'               => null,
			'fixed_border_color'                     => null, // '#dee2e6'
			'fixed_rounded'                          => [], // rounded-3
			'fixed_link_color_class'                 => null,
			'fixed_link_color'                       => null,
			'fixed_link_hover_color'                 => null,
			'fixed_text_color_class'                 => null,
			'fixed_text_color'                       => null,
		];
		
		return array_merge($value, $defaultValue);
	}
	
	public static function overlappedNavbar(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			// For dynamic sectionable pages
			'default_width_type'                     => 'default', // default, full-width, boxed
			'default_height'                         => '75', // 80 / 75 / 65 / 60
			'default_margins'                        => [],
			'default_paddings'                       => [],
			'default_dark'                           => '0',
			'default_shadow'                         => '0',
			'default_animation'                      => '1',
			'default_background_color_class'         => 'bg-transparent', // 'bg-body-tertiary', 'bg-transparent'
			'default_background_color'               => null, // '#f8f9fA'
			'default_border'                         => null, // border
			'default_border_width'                   => null, // 'border-1' (for 1px)
			'default_border_color_class'             => null,
			'default_border_color'                   => null, // '#dee2e6'
			'default_rounded'                        => [], // rounded-3
			'default_link_color_class'               => 'link-light', // 'link-body-emphasis', 'link-light'
			'default_link_color'                     => null,
			'default_link_hover_color'               => null,
			'default_text_color_class'               => 'text-light',
			'default_text_color'                     => null,
			'default_item_shadow'                    => '1',
			'default_expanded_background_color_class'      => 'bg-body-tertiary', // collapsed/expanded: 'bg-body-tertiary'
			
			// For non-dynamic sectionable pages
			'static_recopy_default'                  => '0', // Recopy the default header options
			'static_width_type'                      => 'default', // default, full-width, boxed
			'static_height'                          => '75',
			'static_margins'                         => [],
			'static_paddings'                        => [],
			'static_dark'                            => '0',
			'static_shadow'                          => '0',
			'static_animation'                       => '1',
			'static_background_color_class'          => 'bg-body-tertiary', // bg-body-tertiary
			'static_background_color'                => null, // '#f8f9fA'
			'static_border'                          => 'border-bottom',
			'static_border_width'                    => 'border-1', // 'border-1' (for 1px)
			'static_border_color_class'              => null,
			'static_border_color'                    => null, // '#dee2e6'
			'static_rounded'                         => [], // rounded-3
			'static_link_color_class'                => null,
			'static_link_color'                      => null,
			'static_link_hover_color'                => null,
			'static_text_color_class'                => null,
			'static_text_color'                      => null,
			'static_item_shadow'                     => '0',
			'static_expanded_background_color_class' => 'bg-body-tertiary',
			
			// Fixed or sticky header
			'fixed_top'                              => '1',
			'fixed_height_offset'                    => 200,
			'fixed_width_type'                       => 'default', // default, full-width, boxed
			'fixed_height'                           => '75',
			'fixed_margins'                          => [],
			'fixed_paddings'                         => [],
			'fixed_dark'                             => '0',
			'fixed_shadow'                           => '1',
			'fixed_animation'                        => '1',
			'fixed_background_color_class'           => 'bg-body-tertiary', // bg-body-tertiary, bg-primary
			'fixed_background_color'                 => null,
			'fixed_border'                           => null,
			'fixed_border_width'                     => null, // 'border-1' (for 1px)
			'fixed_border_color_class'               => null,
			'fixed_border_color'                     => null, // '#dee2e6'
			'fixed_rounded'                          => [], // rounded-3
			'fixed_link_color_class'                 => null,
			'fixed_link_color'                       => null,
			'fixed_link_hover_color'                 => null,
			'fixed_text_color_class'                 => null,
			'fixed_text_color'                       => null,
		];
		
		return array_merge($value, $defaultValue);
	}
	
	public static function glassStyleFixedNavbar(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			// For dynamic sectionable pages
			'default_width_type'                     => 'default', // default, full-width, boxed
			'default_height'                         => '75', // 80 / 75 / 65 / 60
			'default_margins'                        => [],
			'default_paddings'                       => [],
			'default_dark'                           => '0',
			'default_shadow'                         => '0',
			'default_animation'                      => '1',
			'default_background_color_class'         => 'bg-transparent', // 'bg-body-tertiary', 'bg-transparent'
			'default_background_color'               => null, // '#f8f9fA', rgba(255, 255, 255, 0.25)
			'default_border'                         => null, // border
			'default_border_width'                   => null, // 'border-1' (for 1px)
			'default_border_color_class'             => null,
			'default_border_color'                   => null, // '#dee2e6'
			'default_rounded'                        => [], // rounded-3
			'default_link_color_class'               => 'link-body-emphasis', // 'link-body-emphasis', 'link-light'
			'default_link_color'                     => null,
			'default_link_hover_color'               => null,
			'default_text_color_class'               => 'text-light',
			'default_text_color'                     => null,
			'default_item_shadow'                    => '0',
			'default_expanded_background_color_class'      => 'bg-body-tertiary', // collapsed/expanded: 'bg-body-tertiary'
			
			// For non-dynamic sectionable pages
			'static_recopy_default'                  => '0', // Recopy the default header options
			'static_width_type'                      => 'default', // default, full-width, boxed
			'static_height'                          => '75',
			'static_margins'                         => [],
			'static_paddings'                        => [],
			'static_dark'                            => '0',
			'static_shadow'                          => '0',
			'static_animation'                       => '1',
			'static_background_color_class'          => 'bg-body-tertiary', // bg-body-tertiary
			'static_background_color'                => null, // '#f8f9fA'
			'static_border'                          => 'border-bottom', // border-bottom
			'static_border_width'                    => 'border-1', // 'border-1' (for 1px)
			'static_border_color_class'              => null,
			'static_border_color'                    => null, // '#dee2e6'
			'static_rounded'                         => [], // rounded-3
			'static_link_color_class'                => null,
			'static_link_color'                      => null,
			'static_link_hover_color'                => null,
			'static_text_color_class'                => null,
			'static_text_color'                      => null,
			'static_item_shadow'                     => '0',
			'static_expanded_background_color_class' => 'bg-body-tertiary',
			
			// Fixed or sticky header
			'fixed_top'                              => '1',
			'fixed_height_offset'                    => 200,
			'fixed_width_type'                       => 'default', // default, full-width, boxed
			'fixed_height'                           => '75',
			'fixed_margins'                          => [],
			'fixed_paddings'                         => [],
			'fixed_dark'                             => '0',
			'fixed_shadow'                           => '1',
			'fixed_animation'                        => '1',
			'fixed_background_color_class'           => null, // bg-body-tertiary, bg-primary
			'fixed_background_color'                 => 'rgba(255, 255, 255, 0.25)', // rgba(255, 255, 255, 0.25)
			'fixed_border'                           => null, // border-bottom
			'fixed_border_width'                     => 'border-1', // 'border-1' (for 1px)
			'fixed_border_color_class'               => 'border-light-subtle',
			'fixed_border_color'                     => null, // '#dee2e6'
			'fixed_rounded'                          => [], // rounded-3
			'fixed_link_color_class'                 => null,
			'fixed_link_color'                       => null,
			'fixed_link_hover_color'                 => null,
			'fixed_text_color_class'                 => null,
			'fixed_text_color'                       => null,
		];
		
		return array_merge($value, $defaultValue);
	}
	
	public static function boxedToFullWidthNavbar(array $value = [], ?Storage $disk = null): array
	{
		$margins = [
			[
				'side'       => 'mt',
				'breakpoint' => null,
				'size'       => '4',
			],
		];
		$paddings = [
			[
				'side'       => 'px',
				'breakpoint' => null,
				'size'       => '2',
			],
		];
		$rounded = [
			[
				'side' => 'rounded',
				'size' => '3',
			],
		];
		
		$defaultValue = [
			// For dynamic sectionable pages
			'default_width_type'                     => 'boxed', // default, full-width, boxed
			'default_height'                         => '60', // 80 / 75 / 65 / 60
			'default_margins'                        => $margins,
			'default_paddings'                       => $paddings,
			'default_dark'                           => '1',
			'default_shadow'                         => '0',
			'default_animation'                      => '1',
			'default_background_color_class'         => 'bg-body-tertiary', // 'bg-body-tertiary', 'bg-transparent'
			'default_background_color'               => null, // '#f8f9fA'
			'default_border'                         => null, // border
			'default_border_width'                   => null, // 'border-1' (for 1px)
			'default_border_color_class'             => null,
			'default_border_color'                   => null, // '#dee2e6'
			'default_rounded'                        => $rounded, // rounded-3
			'default_link_color_class'               => 'link-body-emphasis', // 'link-body-emphasis', 'link-light'
			'default_link_color'                     => null,
			'default_link_hover_color'               => null,
			'default_text_color_class'               => 'text-light',
			'default_text_color'                     => null,
			'default_item_shadow'                    => '0',
			'default_expanded_background_color_class'      => 'bg-body-tertiary', // collapsed/expanded: 'bg-body-tertiary'
			
			// For non-dynamic sectionable pages
			'static_recopy_default'                  => '0', // Recopy the default header options
			'static_width_type'                      => 'default', // default, full-width, boxed
			'static_height'                          => '75',
			'static_margins'                         => [],
			'static_paddings'                        => [],
			'static_dark'                            => '0',
			'static_shadow'                          => '0',
			'static_animation'                       => '1',
			'static_background_color_class'          => 'bg-body-tertiary', // bg-body-tertiary
			'static_background_color'                => null, // '#f8f9fA'
			'static_border'                          => 'border-bottom',
			'static_border_width'                    => 'border-1', // 'border-1' (for 1px)
			'static_border_color_class'              => null,
			'static_border_color'                    => null, // '#dee2e6'
			'static_rounded'                         => [], // rounded-3
			'static_link_color_class'                => null,
			'static_link_color'                      => null,
			'static_link_hover_color'                => null,
			'static_text_color_class'                => null,
			'static_text_color'                      => null,
			'static_item_shadow'                     => '0',
			'static_expanded_background_color_class' => 'bg-body-tertiary',
			
			// Fixed or sticky header
			'fixed_top'                              => '1',
			'fixed_height_offset'                    => 200,
			'fixed_width_type'                       => 'full-width', // default, full-width, boxed
			'fixed_height'                           => '75',
			'fixed_margins'                          => [],
			'fixed_paddings'                         => [],
			'fixed_dark'                             => '0',
			'fixed_shadow'                           => '1',
			'fixed_animation'                        => '1',
			'fixed_background_color_class'           => 'bg-body-tertiary', // bg-body-tertiary, bg-primary
			'fixed_background_color'                 => null,
			'fixed_border'                           => null,
			'fixed_border_width'                     => null, // 'border-1' (for 1px)
			'fixed_border_color_class'               => null,
			'fixed_border_color'                     => null, // '#dee2e6'
			'fixed_rounded'                          => [], // rounded-3
			'fixed_link_color_class'                 => null,
			'fixed_link_color'                       => null,
			'fixed_link_hover_color'                 => null,
			'fixed_text_color_class'                 => null,
			'fixed_text_color'                       => null,
		];
		
		return array_merge($value, $defaultValue);
	}
	
	public static function borderBottom(array $value = [], ?Storage $disk = null): array
	{
		$defaultValue = [
			// For dynamic sectionable pages
			'default_width_type'                     => 'default', // default, full-width, boxed
			'default_height'                         => '75', // 80 / 75 / 65 / 60
			'default_margins'                        => [],
			'default_paddings'                       => [],
			'default_dark'                           => '0',
			'default_shadow'                         => '0',
			'default_animation'                      => '1',
			'default_background_color_class'         => 'bg-body-tertiary', // 'bg-body-tertiary', 'bg-transparent'
			'default_background_color'               => null, // '#f8f9fA'
			'default_border'                         => 'border-bottom', // border
			'default_border_width'                   => 'border-1', // 'border-1' (for 1px)
			'default_border_color_class'             => null,
			'default_border_color'                   => null, // '#dee2e6'
			'default_rounded'                        => [], // rounded-3
			'default_link_color_class'               => 'link-body-emphasis', // 'link-body-emphasis', 'link-light'
			'default_link_color'                     => null,
			'default_link_hover_color'               => null,
			'default_text_color_class'               => null,
			'default_text_color'                     => null,
			'default_item_shadow'                    => '0',
			'default_expanded_background_color_class'      => 'bg-body-tertiary', // collapsed/expanded: 'bg-body-tertiary'
			
			// For non-dynamic sectionable pages
			'static_recopy_default'                  => '0', // Recopy the default header options
			'static_width_type'                      => 'default', // default, full-width, boxed
			'static_height'                          => '75',
			'static_margins'                         => [],
			'static_paddings'                        => [],
			'static_dark'                            => '0',
			'static_shadow'                          => '0',
			'static_animation'                       => '1',
			'static_background_color_class'          => 'bg-body-tertiary', // bg-body-tertiary
			'static_background_color'                => null, // '#f8f9fA'
			'static_border'                          => 'border-bottom',
			'static_border_width'                    => 'border-1', // 'border-1' (for 1px)
			'static_border_color_class'              => null,
			'static_border_color'                    => null, // '#dee2e6'
			'static_rounded'                         => [], // rounded-3
			'static_link_color_class'                => null,
			'static_link_color'                      => null,
			'static_link_hover_color'                => null,
			'static_text_color_class'                => null,
			'static_text_color'                      => null,
			'static_item_shadow'                     => '0',
			'static_expanded_background_color_class' => 'bg-body-tertiary',
			
			// Fixed or sticky header
			'fixed_top'                              => '1',
			'fixed_height_offset'                    => 200,
			'fixed_width_type'                       => 'default', // default, full-width, boxed
			'fixed_height'                           => '75',
			'fixed_margins'                          => [],
			'fixed_paddings'                         => [],
			'fixed_dark'                             => '0',
			'fixed_shadow'                           => '1',
			'fixed_animation'                        => '1',
			'fixed_background_color_class'           => 'bg-body-tertiary', // bg-body-tertiary, bg-primary
			'fixed_background_color'                 => null,
			'fixed_border'                           => null,
			'fixed_border_width'                     => null, // 'border-1' (for 1px)
			'fixed_border_color_class'               => null,
			'fixed_border_color'                     => null, // '#dee2e6'
			'fixed_rounded'                          => [], // rounded-3
			'fixed_link_color_class'                 => null,
			'fixed_link_color'                       => null,
			'fixed_link_hover_color'                 => null,
			'fixed_text_color_class'                 => null,
			'fixed_text_color'                       => null,
		];
		
		return array_merge($value, $defaultValue);
	}
}
