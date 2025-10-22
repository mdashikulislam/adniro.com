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

class StatsSection extends BaseSection
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
		$defaultFontIconSet = config('larapen.core.defaultFontIconSet', 'bootstrap');
		
		$fields = [];
		
		$tabName = trans('admin.content_option_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'content_option_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'  => 'count_listings',
			'type'  => 'custom_html',
			'value' => trans('admin.count_listings_info'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'        => 'icon_count_listings',
			'label'       => trans('admin.Icon'),
			'type'        => 'icon_picker',
			'iconSet'     => config('larapen.core.fontIconSet.' . $defaultFontIconSet . '.key'),
			'iconVersion' => config('larapen.core.fontIconSet.' . $defaultFontIconSet . '.version'),
			'wrapper'     => [
				'class' => 'col-md-2',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'custom_counts_listings',
			'label'      => trans('admin.custom_counter_up_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'step' => 1,
			],
			'hint'       => trans('admin.custom_counter_up_hint'),
			'wrapper'    => [
				'class' => 'col-md-4',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'prefix_count_listings',
			'label'   => trans('admin.prefix_counter_up_label'),
			'type'    => 'text',
			'hint'    => trans('admin.counter_up_prefix_suffix_hint'),
			'wrapper' => [
				'class' => 'col-md-3',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'suffix_count_listings',
			'label'   => trans('admin.suffix_counter_up_label'),
			'type'    => 'text',
			'hint'    => trans('admin.counter_up_prefix_suffix_hint'),
			'wrapper' => [
				'class' => 'col-md-3',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'  => 'count_users',
			'type'  => 'custom_html',
			'value' => trans('admin.count_users_info'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'        => 'icon_count_users',
			'label'       => trans('admin.Icon'),
			'type'        => 'icon_picker',
			'iconSet'     => config('larapen.core.fontIconSet.' . $defaultFontIconSet . '.key'),
			'iconVersion' => config('larapen.core.fontIconSet.' . $defaultFontIconSet . '.version'),
			'wrapper'     => [
				'class' => 'col-md-2',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'custom_counts_users',
			'label'      => trans('admin.custom_counter_up_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'step' => 1,
			],
			'hint'       => trans('admin.custom_counter_up_hint'),
			'wrapper'    => [
				'class' => 'col-md-4',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'prefix_count_users',
			'label'   => trans('admin.prefix_counter_up_label'),
			'type'    => 'text',
			'hint'    => trans('admin.counter_up_prefix_suffix_hint'),
			'wrapper' => [
				'class' => 'col-md-3',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'suffix_count_users',
			'label'   => trans('admin.suffix_counter_up_label'),
			'type'    => 'text',
			'hint'    => trans('admin.counter_up_prefix_suffix_hint'),
			'wrapper' => [
				'class' => 'col-md-3',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'  => 'count_locations',
			'type'  => 'custom_html',
			'value' => trans('admin.count_locations_info'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'        => 'icon_count_locations',
			'label'       => trans('admin.Icon'),
			'type'        => 'icon_picker',
			'iconSet'     => config('larapen.core.fontIconSet.' . $defaultFontIconSet . '.key'),
			'iconVersion' => config('larapen.core.fontIconSet.' . $defaultFontIconSet . '.version'),
			'wrapper'     => [
				'class' => 'col-md-2',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'custom_counts_locations',
			'label'      => trans('admin.custom_counter_up_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'step' => 1,
			],
			'hint'       => trans('admin.custom_counter_up_hint'),
			'wrapper'    => [
				'class' => 'col-md-4',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'prefix_count_locations',
			'label'   => trans('admin.prefix_counter_up_label'),
			'type'    => 'text',
			'hint'    => trans('admin.counter_up_prefix_suffix_hint'),
			'wrapper' => [
				'class' => 'col-md-3',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'suffix_count_locations',
			'label'   => trans('admin.suffix_counter_up_label'),
			'type'    => 'text',
			'hint'    => trans('admin.counter_up_prefix_suffix_hint'),
			'wrapper' => [
				'class' => 'col-md-3',
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
				'class' => 'col-md-6',
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
		
		$tabName = trans('admin.counter_up_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'counter_up_options',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'       => 'counter_up_delay',
			'label'      => trans('admin.counter_up_delay_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 50000,
				'step' => 1,
			],
			'hint'       => trans('admin.counter_up_delay_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'counter_up_time',
			'label'      => trans('admin.counter_up_time_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 50000,
				'step' => 1,
			],
			'hint'       => trans('admin.counter_up_time_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'  => 'disable_counter_up',
			'label' => trans('admin.disable_counter_up_label'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.disable_counter_up_hint'),
			'tab'   => $tabName,
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
			'name'    => 'hide_on_mobile',
			'label'   => trans('admin.hide_on_mobile_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.hide_on_mobile_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
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
		
		return $fields;
	}
}
