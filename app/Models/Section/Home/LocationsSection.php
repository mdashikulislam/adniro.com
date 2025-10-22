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

namespace App\Models\Section\Home;

use App\Helpers\Common\Arr;
use App\Models\Section\BaseSection;

class LocationsSection extends BaseSection
{
	public static function getFieldValues($value, $disk): array
	{
		$defaultValue = self::getDefaultPreset(__CLASS__);
		
		$value = is_array($value) ? $value : [];
		$value = array_merge($defaultValue, $value);
		$value = self::applyPreset(__CLASS__, $value);
		
		// Build CSS class list based on defined options
		$value['css_classes'] = self::buildCssClassesAsString($value);
		
		// Get animation attributes
		$value['html_attributes'] = Arr::toAttributes(self::buildAnimationHtmlAttributes($value));
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [];
		
		$tabName = trans('admin.locations_locations_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'locations_locations_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'    => 'show_cities',
			'label'   => trans('admin.Show the Country Cities'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'show_listing_btn',
			'label'   => trans('admin.Show the bottom button'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'background_color',
			'label'      => trans('admin.Background Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#c7c5c1',
			],
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'border_width',
			'label'      => trans('admin.Border Width'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '1',
			],
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'border_color',
			'label'      => trans('admin.Border Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#c7c5c1',
			],
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'text_color',
			'label'      => trans('admin.Text Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#c7c5c1',
			],
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'link_color',
			'label'      => trans('admin.link_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#c7c5c1',
			],
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'link_hover_color',
			'label'      => trans('admin.link_hover_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#c7c5c1',
			],
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'max_items',
			'label'      => trans('admin.max_cities_label'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 12,
			],
			'hint'       => trans('admin.max_cities_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'items_cols',
			'label'   => trans('admin.Cities Columns'),
			'type'    => 'select2_from_array',
			'options' => [
				3 => '3',
				2 => '2',
				1 => '1',
			],
			'hint'    => trans('admin.This option is applied only when the map is displayed'),
			'wrapper' => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'cache_expiration',
			'label'      => trans('admin.Cache Expiration Time for this section'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '0',
			],
			'hint'       => trans('admin.section_cache_expiration_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 cities-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'     => 'active',
			'label'    => trans('admin.Active'),
			'type'     => 'checkbox_switch',
			'fake'     => false,
			'store_in' => null,
			'tab'      => $tabName,
		];
		
		$tabName = trans('admin.svg_map_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'svg_map_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'  => 'svg_map_info',
			'type'  => 'custom_html',
			'value' => trans('admin.card_light_warning', [
				'content' => trans('admin.svg_map_info', [
					'svgMapsFilesDir' => getRelativePath(config('larapen.core.maps.path')),
				]),
			]),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'    => 'enable_map',
			'label'   => trans('admin.enable_map_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.enable_map_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_width',
			'label'      => trans('admin.maps_width'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '300',
			],
			'default'    => '300',
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_height',
			'label'      => trans('admin.maps_height'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '300',
			],
			'default'    => '300',
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_background_color',
			'label'      => trans('admin.maps_background_color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => 'transparent',
			],
			'hint'       => trans('admin.Enter a RGB color code or the word transparent'),
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_border',
			'label'      => trans('admin.maps_border'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#c7c5c1',
			],
			'hint'       => trans('admin.Enter a RGB color code or the word transparent'),
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_hover_border',
			'label'      => trans('admin.maps_hover_border'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#c7c5c1',
			],
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_border_width',
			'label'      => trans('admin.maps_border_width'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 4,
			],
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_color',
			'label'      => trans('admin.maps_color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#f2f0eb',
			],
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'map_hover',
			'label'      => trans('admin.maps_hover'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#4682B4',
			],
			'wrapper'    => [
				'class' => 'col-md-6 map-field',
			],
			'tab'        => $tabName,
		];
		
		$tabName = trans('admin.spacing_option_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'spacing_option_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields = self::appendSpacingFormFields($fields, tab: $tabName, fieldSeparator: 'end');
		
		$fields[] = [
			'name'    => 'full_height',
			'label'   => trans('admin.full_height_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.full_height_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'  => 'hide_on_mobile',
			'label' => trans('admin.hide_on_mobile_label'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.hide_on_mobile_hint'),
			'tab'   => $tabName,
		];
		
		$tabName = trans('admin.animation_option_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'animation_option_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields = self::animationFields(
			fields: $fields,
			fieldsTitle: false,
			tab: $tabName
		);
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
}
