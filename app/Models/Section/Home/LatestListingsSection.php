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

class LatestListingsSection extends BaseSection
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
			'name'       => 'max_items',
			'label'      => trans('admin.Max Items'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => 100,
				'step' => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'        => 'order_by',
			'label'       => trans('admin.Order By'),
			'type'        => 'select2_from_array',
			'options'     => [
				'date'   => 'Date',
				'random' => 'Random',
			],
			'allows_null' => false,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'    => 'items_in_carousel',
			'label'   => trans('admin.items_in_carousel_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.items_in_carousel_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'show_view_more_btn',
			'label'   => trans('admin.Show View More Button'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.show_view_more_btn_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'cache_expiration',
			'label'      => trans('admin.Cache Expiration Time for this section'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '0',
				'min'         => 0,
				'step'        => 1,
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
		
		return $fields;
	}
}
