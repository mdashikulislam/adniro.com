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

namespace App\Models\Setting;

use App\Enums\BootstrapColor;

/*
 * settings.header.option
 */

class HeaderSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk): array
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			// For dynamic sectionable pages
			'default_width_type'                      => 'default', // default, full-width, boxed
			'default_height'                          => '75', // 80 / 75 / 65 / 60
			'default_margins'                         => [],
			'default_paddings'                        => [],
			'default_dark'                            => $value['dark'] ?? '0',
			'default_shadow'                          => $value['shadow'] ?? '0',
			'default_animation'                       => $value['animation'] ?? '1',
			'default_background_color_class'          => $value['background_class'] ?? 'bg-body-tertiary', // 'bg-body-tertiary', 'bg-transparent'
			'default_background_color'                => $value['background_color'] ?? null, // '#f8f9fA'
			'default_border'                          => null, // border
			'default_border_width'                    => null,  // 'border-1' (for 1px)
			'default_border_color_class'              => null,
			'default_border_color'                    => null, // '#dee2e6'
			'default_rounded'                         => [], // rounded-3
			'default_link_color_class'                => 'link-body-emphasis', // 'link-body-emphasis', 'link-light'
			'default_link_color'                      => $value['link_color'] ?? null,
			'default_link_hover_color'                => $value['link_hover_color'] ?? null,
			'default_text_color_class'                => null,
			'default_text_color'                      => null,
			'default_item_shadow'                     => '0',
			'default_expanded_background_color_class' => 'bg-body-tertiary', // collapsed/expanded: 'bg-body-tertiary'
			'default_expanded_link_color_class'       => 'link-body-emphasis',
			'default_expanded_text_color_class'       => 'text-body-emphasis',
			
			// For non-dynamic sectionable pages
			'static_recopy_default'                   => '1', // Recopy the default header options
			'static_width_type'                       => 'default', // default, full-width, boxed
			'static_height'                           => '75',
			'static_margins'                          => [],
			'static_paddings'                         => [],
			'static_dark'                             => '0',
			'static_shadow'                           => '0',
			'static_animation'                        => '1',
			'static_background_color_class'           => 'bg-body-tertiary', // bg-body-tertiary
			'static_background_color'                 => null, // '#f8f9fA'
			'static_border'                           => 'border-bottom',
			'static_border_width'                     => 'border-1',  // 'border-1' (for 1px)
			'static_border_color_class'               => null,
			'static_border_color'                     => $value['border_bottom_color'] ?? null, // '#dee2e6'
			'static_rounded'                          => [], // rounded-3
			'static_link_color_class'                 => null,
			'static_link_color'                       => null,
			'static_link_hover_color'                 => null,
			'static_text_color_class'                 => null,
			'static_text_color'                       => null,
			'static_item_shadow'                      => '0',
			'static_expanded_background_color_class'  => 'bg-body-tertiary',
			'static_expanded_link_color_class'        => 'link-body-emphasis',
			'static_expanded_text_color_class'        => 'text-body-emphasis',
			
			// Fixed or sticky header
			'fixed_top'                               => '1',
			'fixed_height_offset'                     => 200,
			'fixed_width_type'                        => 'default', // default, full-width, boxed
			'fixed_height'                            => '75',
			'fixed_margins'                           => [],
			'fixed_paddings'                          => [],
			'fixed_dark'                              => '0',
			'fixed_shadow'                            => '1',
			'fixed_animation'                         => '1',
			'fixed_background_color_class'            => 'bg-body-tertiary', // bg-body-tertiary, bg-primary
			'fixed_background_color'                  => null,
			'fixed_border'                            => null,
			'fixed_border_width'                      => null,  // 'border-1' (for 1px)
			'fixed_border_color_class'                => null,
			'fixed_border_color'                      => null, // '#dee2e6'
			'fixed_rounded'                           => [], // rounded-3
			'fixed_link_color_class'                  => null,
			'fixed_link_color'                        => null,
			'fixed_link_hover_color'                  => null,
			'fixed_text_color_class'                  => null,
			'fixed_text_color'                        => null,
			
			'logo_width'        => '216',
			'logo_height'       => '40',
			'logo_aspect_ratio' => '1',
		];
		
		$value = array_merge($defaultValue, $value);
		$value = self::applyPreset(__CLASS__, $value);
		
		// Build CSS class list based on defined options
		$navbarTypes = ['default', 'static', 'fixed'];
		
		// Navbar CSS Classes
		foreach ($navbarTypes as $type) {
			$cssClasses = self::buildCssClasses($value, [
				"{$type}_margins"  => 'm',
				"{$type}_paddings" => 'p',
				"{$type}_rounded"  => 'rounded',
			]);
			
			$isForNotFixedHeaderType = ($type != 'fixed');
			
			// Width Type
			$widthType = $value["{$type}_width_type"] ?? 'default';
			$cssClasses[] = ($widthType == 'boxed') ? 'container' : '';
			
			// Background Color Class
			$bgColorClass = $value["{$type}_background_color_class"] ?? null;
			if (!empty($bgColorClass)) {
				$cssClasses[] = $bgColorClass;
			}
			
			// Border
			$borderClass = $value["{$type}_border"] ?? null;
			if (!empty($borderClass)) {
				$cssClasses[] = $borderClass;
			}
			
			// Border Width
			$borderWidthClass = $value["{$type}_border_width"] ?? null;
			if (!empty($borderWidthClass)) {
				$cssClasses[] = $borderWidthClass;
			}
			
			// Border Color Class
			$borderColorClass = $value["{$type}_border_color_class"] ?? null;
			if (!empty($borderColorClass)) {
				$cssClasses[] = $borderColorClass;
			}
			
			// Animation
			//if ($isForNotFixedHeaderType) {
			$isAnimationEnabled = $value["{$type}_animation"] ?? 0;
			$isAnimationEnabled = ($isAnimationEnabled == '1');
			if ($isAnimationEnabled) {
				$cssClasses[] = 'navbar-sticky';
			}
			//}
			
			// Shadow
			$isShadowEnabled = $value["{$type}_shadow"] ?? 0;
			$isShadowEnabled = ($isShadowEnabled == '1');
			if ($isShadowEnabled) {
				$cssClasses[] = 'shadow';
			}
			
			// Fixed Top
			//if ($isForNotFixedHeaderType) {
			$isFixedHeaderEnabled = $value['fixed_top'] ?? 0;
			$isFixedHeaderEnabled = ($isFixedHeaderEnabled == '1');
			if ($isFixedHeaderEnabled) {
				$cssClasses[] = 'fixed-top';
			}
			//}
			
			$value["{$type}_css_classes"] = implode(' ', $cssClasses);
		}
		
		// Container CSS Classes
		foreach ($navbarTypes as $type) {
			$cssClasses = [];
			
			// Width Type
			$widthType = $value["{$type}_width_type"] ?? 'default';
			$cssClasses[] = ($widthType == 'full-width') ? 'container-fluid' : 'container';
			
			$value["{$type}_container_css_classes"] = implode(' ', $cssClasses);
		}
		
		return self::recopyDefaultHeaderOptionsIfAllowed($value);
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		// Get Bootstrap's Background Colors
		$bgColorsByName = BootstrapColor::Background->getColorsByName();
		$formattedBgColors = BootstrapColor::Background->getFormattedColors();
		
		// Get Bootstrap's Border Side & Width List
		$bordersConfig = getCachedReferrerList('bootstrap/borders');
		$borderSideList = $bordersConfig['sides'] ?? [];
		$borderWidthList = $bordersConfig['width'] ?? [];
		
		// Get Bootstrap's Border Colors
		$borderColorsByName = BootstrapColor::Border->getColorsByName();
		$formattedBorderColors = BootstrapColor::Border->getFormattedColors();
		
		// Get Bootstrap's Link Colors
		$linkColorsByName = BootstrapColor::Link->getColorsByName();
		$formattedLinkColors = BootstrapColor::Link->getFormattedColors();
		
		// Get Bootstrap's Text Colors
		$textColorsByName = BootstrapColor::Text->getColorsByName();
		$formattedTextColors = BootstrapColor::Text->getFormattedColors();
		
		$fields = [];
		
		// Sectionable header options
		$tabName = trans('admin.header_style_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'default_header_style_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'  => 'default_header_style_info',
			'type'  => 'custom_html',
			'value' => trans('admin.header_style_info'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'    => 'default_dark',
			'label'   => trans('admin.dark_header_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.dark_header_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'default_shadow',
			'label'   => trans('admin.header_shadow_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_shadow_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'default_animation',
			'label'   => trans('admin.header_animation_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_animation_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_width_type',
			'label'       => trans('admin.header_width_type_label'),
			'type'        => 'select2_from_array',
			'options'     => [
				'default' => 'Default',
				'full'    => 'Full Width',
				'boxed'   => 'Boxed',
			],
			'allows_null' => false,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'default_height',
			'label'      => trans('admin.Header Height'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 65,
				'min'         => 0,
				'step'        => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::appendSpacingFormFields(
			fields: $fields,
			fieldName: 'default_margins',
			tab: $tabName,
			preventHeaderOverlap: false,
		);
		$fields = self::appendSpacingFormFields(
			fields: $fields,
			fieldName: 'default_paddings',
			targetProperty: 'p',
			tab: $tabName,
		);
		
		$fields[] = [
			'name'        => 'default_background_color_class',
			'label'       => trans('admin.background_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $bgColorsByName,
			'skins'       => $formattedBgColors,
			'allows_null' => true,
			'hint'        => trans('admin.background_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'default_background_color',
			'label'      => trans('admin.background_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#F8F8F8',
			],
			'hint'       => trans('admin.transparent_background_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_border',
			'label'       => trans('admin.border_label'),
			'type'        => 'select2_from_array',
			'options'     => $borderSideList,
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_border_width',
			'label'       => trans('admin.border_width_label'),
			'type'        => 'select2_from_array',
			'options'     => $borderWidthList,
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_border_color_class',
			'label'       => trans('admin.border_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $borderColorsByName,
			'skins'       => $formattedBorderColors,
			'allows_null' => true,
			'hint'        => trans('admin.border_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'default_border_color',
			'label'      => trans('admin.border_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#E8E8E8',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::appendRoundedFormFields(
			fields: $fields,
			fieldName: 'default_rounded',
			tab: $tabName,
		);
		
		$fields[] = [
			'name'        => 'default_link_color_class',
			'label'       => trans('admin.link_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $linkColorsByName,
			'skins'       => $formattedLinkColors,
			'allows_null' => true,
			'hint'        => trans('admin.link_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'default_link_color',
			'label'      => trans('admin.link_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#333',
			],
			'wrapper'    => [
				'class' => 'col-md-3',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'default_link_hover_color',
			'label'      => trans('admin.link_hover_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#000',
			],
			'wrapper'    => [
				'class' => 'col-md-3',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_text_color_class',
			'label'       => trans('admin.text_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $textColorsByName,
			'skins'       => $formattedTextColors,
			'allows_null' => true,
			'hint'        => trans('admin.text_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'default_text_color',
			'label'      => trans('admin.text_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#F8F8F8',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'default_item_shadow',
			'label'   => trans('admin.header_item_shadow_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_item_shadow_hint'),
			'wrapper' => [
				'class' => 'col-md-6 mt-3',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'  => 'default_expanded_collapse_title',
			'type'  => 'custom_html',
			'value' => trans('admin.expanded_collapse_title'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'  => 'default_expanded_collapse_info',
			'type'  => 'custom_html',
			'value' => trans('admin.expanded_collapse_info'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_expanded_background_color_class',
			'label'       => trans('admin.background_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $bgColorsByName,
			'skins'       => $formattedBgColors,
			'allows_null' => true,
			'hint'        => trans('admin.background_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_expanded_link_color_class',
			'label'       => trans('admin.link_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $linkColorsByName,
			'skins'       => $formattedLinkColors,
			'allows_null' => true,
			'hint'        => trans('admin.link_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'default_expanded_text_color_class',
			'label'       => trans('admin.text_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $textColorsByName,
			'skins'       => $formattedTextColors,
			'allows_null' => true,
			'hint'        => trans('admin.text_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		
		// Non-sectionable header options
		$tabName = trans('admin.static_header_style_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'static_header_style_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'  => 'static_header_style_info',
			'type'  => 'custom_html',
			'value' => trans('admin.static_header_style_info'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'  => 'static_recopy_default',
			'label' => trans('admin.static_header_recopy_options_label'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.static_header_recopy_options_hint'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'    => 'static_dark',
			'label'   => trans('admin.dark_header_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.dark_header_hint'),
			'wrapper' => [
				'class' => 'col-md-6 static-header',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'static_shadow',
			'label'   => trans('admin.header_shadow_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_shadow_hint'),
			'wrapper' => [
				'class' => 'col-md-6 static-header',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'static_animation',
			'label'   => trans('admin.header_animation_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_animation_hint'),
			'wrapper' => [
				'class' => 'col-md-6 static-header',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_width_type',
			'label'       => trans('admin.header_width_type_label'),
			'type'        => 'select2_from_array',
			'options'     => [
				'default' => 'Default',
				'full'    => 'Full Width',
				'boxed'   => 'Boxed',
			],
			'allows_null' => false,
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'static_height',
			'label'      => trans('admin.Header Height'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 65,
				'min'         => 0,
				'step'        => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 static-header',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::appendSpacingFormFields(
			fields: $fields,
			fieldName: 'static_margins',
			wrapper: [
				'class' => 'col-md-12 static-header',
			],
			tab: $tabName,
			preventHeaderOverlap: false,
		);
		$fields = self::appendSpacingFormFields(
			fields: $fields,
			fieldName: 'static_paddings',
			targetProperty: 'p',
			wrapper: [
				'class' => 'col-md-12 static-header',
			],
			tab: $tabName,
		);
		
		$fields[] = [
			'name'        => 'static_background_color_class',
			'label'       => trans('admin.background_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $bgColorsByName,
			'skins'       => $formattedBgColors,
			'allows_null' => true,
			'hint'        => trans('admin.background_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'static_background_color',
			'label'      => trans('admin.background_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#F8F8F8',
			],
			'hint'       => trans('admin.transparent_background_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 static-header',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_border',
			'label'       => trans('admin.border_label'),
			'type'        => 'select2_from_array',
			'options'     => $borderSideList,
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_border_width',
			'label'       => trans('admin.border_width_label'),
			'type'        => 'select2_from_array',
			'options'     => $borderWidthList,
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_border_color_class',
			'label'       => trans('admin.border_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $borderColorsByName,
			'skins'       => $formattedBorderColors,
			'allows_null' => true,
			'hint'        => trans('admin.border_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'static_border_color',
			'label'      => trans('admin.border_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#E8E8E8',
			],
			'wrapper'    => [
				'class' => 'col-md-6 static-header',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::appendRoundedFormFields(
			fields: $fields,
			fieldName: 'static_rounded',
			wrapper: [
				'class' => 'col-md-12 static-header',
			],
			tab: $tabName,
		);
		
		$fields[] = [
			'name'        => 'static_link_color_class',
			'label'       => trans('admin.link_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $linkColorsByName,
			'skins'       => $formattedLinkColors,
			'allows_null' => true,
			'hint'        => trans('admin.link_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'static_link_color',
			'label'      => trans('admin.link_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#333',
			],
			'wrapper'    => [
				'class' => 'col-md-3 static-header',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'static_link_hover_color',
			'label'      => trans('admin.link_hover_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#000',
			],
			'wrapper'    => [
				'class' => 'col-md-3 static-header',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_text_color_class',
			'label'       => trans('admin.text_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $textColorsByName,
			'skins'       => $formattedTextColors,
			'allows_null' => true,
			'hint'        => trans('admin.text_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'static_text_color',
			'label'      => trans('admin.text_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#F8F8F8',
			],
			'wrapper'    => [
				'class' => 'col-md-6 static-header',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'static_item_shadow',
			'label'   => trans('admin.header_item_shadow_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_item_shadow_hint'),
			'wrapper' => [
				'class' => 'col-md-6 mt-3 static-header',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'static_expanded_collapse_title',
			'type'    => 'custom_html',
			'value'   => trans('admin.expanded_collapse_title'),
			'wrapper' => [
				'class' => 'col-md-12 static-header',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'static_expanded_collapse_info',
			'type'    => 'custom_html',
			'value'   => trans('admin.expanded_collapse_info'),
			'wrapper' => [
				'class' => 'col-md-12 static-header',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_expanded_background_color_class',
			'label'       => trans('admin.background_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $bgColorsByName,
			'skins'       => $formattedBgColors,
			'allows_null' => true,
			'hint'        => trans('admin.background_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_expanded_link_color_class',
			'label'       => trans('admin.link_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $linkColorsByName,
			'skins'       => $formattedLinkColors,
			'allows_null' => true,
			'hint'        => trans('admin.link_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'static_expanded_text_color_class',
			'label'       => trans('admin.text_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $textColorsByName,
			'skins'       => $formattedTextColors,
			'allows_null' => true,
			'hint'        => trans('admin.text_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 static-header',
			],
			'tab'         => $tabName,
		];
		
		// Fixed header options
		$tabName = trans('admin.fixed_header_style_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'fixed_header_style_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'  => 'fixed_header_style_info',
			'type'  => 'custom_html',
			'value' => trans('admin.fixed_header_style_info'),
			'tab'   => $tabName,
		];
		
		$fields[] = [
			'name'    => 'fixed_top',
			'label'   => trans('admin.header_fixed_top_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_fixed_top_hint', ['navbarHeightOffset' => trans('admin.header_fixed_height_offset_label')]),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'fixed_height_offset',
			'label'      => trans('admin.header_fixed_height_offset_label'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 200,
				'min'         => 0,
				'step'        => 1,
			],
			'hint'       => trans('admin.header_fixed_height_offset_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 fixed-header',
			],
			'newline'    => true,
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'fixed_dark',
			'label'   => trans('admin.dark_header_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.dark_header_hint'),
			'wrapper' => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'fixed_shadow',
			'label'   => trans('admin.header_shadow_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.header_shadow_hint'),
			'wrapper' => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'        => 'fixed_width_type',
			'label'       => trans('admin.header_width_type_label'),
			'type'        => 'select2_from_array',
			'options'     => [
				'default' => 'Default',
				'full'    => 'Full Width',
				'boxed'   => 'Boxed',
			],
			'allows_null' => false,
			'wrapper'     => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'fixed_height',
			'label'      => trans('admin.Header Height'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 65,
				'min'         => 0,
				'step'        => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::appendSpacingFormFields(
			fields: $fields,
			fieldName: 'fixed_margins',
			wrapper: [
				'class' => 'col-md-12 fixed-header',
			],
			tab: $tabName,
			preventHeaderOverlap: false,
		);
		$fields = self::appendSpacingFormFields(
			fields: $fields,
			fieldName: 'fixed_paddings',
			targetProperty: 'p',
			wrapper: [
				'class' => 'col-md-12 fixed-header',
			],
			tab: $tabName,
		);
		
		$fields[] = [
			'name'        => 'fixed_background_color_class',
			'label'       => trans('admin.background_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $bgColorsByName,
			'skins'       => $formattedBgColors,
			'allows_null' => true,
			'hint'        => trans('admin.background_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'fixed_background_color',
			'label'      => trans('admin.background_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#F8F8F8',
			],
			'hint'       => trans('admin.transparent_background_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'        => 'fixed_border',
			'label'       => trans('admin.border_label'),
			'type'        => 'select2_from_array',
			'options'     => $borderSideList,
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'fixed_border_width',
			'label'       => trans('admin.border_width_label'),
			'type'        => 'select2_from_array',
			'options'     => $borderWidthList,
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'        => 'fixed_border_color_class',
			'label'       => trans('admin.border_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $borderColorsByName,
			'skins'       => $formattedBorderColors,
			'allows_null' => true,
			'hint'        => trans('admin.border_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'fixed_border_color',
			'label'      => trans('admin.border_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#E8E8E8',
			],
			'wrapper'    => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::appendRoundedFormFields(
			fields: $fields,
			fieldName: 'fixed_rounded',
			wrapper: [
				'class' => 'col-md-12 fixed-header',
			],
			tab: $tabName,
		);
		
		$fields[] = [
			'name'        => 'fixed_link_color_class',
			'label'       => trans('admin.link_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $linkColorsByName,
			'skins'       => $formattedLinkColors,
			'allows_null' => true,
			'hint'        => trans('admin.link_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'fixed_link_color',
			'label'      => trans('admin.link_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#333',
			],
			'wrapper'    => [
				'class' => 'col-md-3 fixed-header',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'fixed_link_hover_color',
			'label'      => trans('admin.link_hover_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#000',
			],
			'wrapper'    => [
				'class' => 'col-md-3 fixed-header',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'        => 'fixed_text_color_class',
			'label'       => trans('admin.text_color_class_label'),
			'type'        => 'select2_from_skins',
			'options'     => $textColorsByName,
			'skins'       => $formattedTextColors,
			'allows_null' => true,
			'hint'        => trans('admin.text_color_class_hint'),
			'wrapper'     => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'       => 'fixed_text_color',
			'label'      => trans('admin.text_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#F8F8F8',
			],
			'wrapper'    => [
				'class' => 'col-md-6 fixed-header',
			],
			'tab'        => $tabName,
		];
		
		// Logo options
		$tabName = trans('admin.header_logo_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'header_logo_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'       => 'logo_width',
			'label'      => trans('admin.logo_width_label'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 216,
				'min'         => 0,
				'step'        => 1,
			],
			'hint'       => trans('admin.logo_width_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'logo_height',
			'label'      => trans('admin.logo_height_label'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 40,
				'min'         => 0,
				'step'        => 1,
			],
			'hint'       => trans('admin.logo_height_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'logo_aspect_ratio',
			'label'   => trans('admin.logo_aspect_ratio_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.logo_aspect_ratio_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
	
	/**
	 * Recopy the default navbar options for the static-sections' pages' header
	 * (If allowed)
	 *
	 * @param array $value
	 * @return array
	 */
	private static function recopyDefaultHeaderOptionsIfAllowed(array $value): array
	{
		// Skip navbar configuration if request is coming from admin panel
		if (isFromAdminPanel()) {
			return $value;
		}
		
		$staticRecopyDefault = $value['static_recopy_default'] ?? '0';
		$isRecopyEnabled = ($staticRecopyDefault === '1');
		
		if (!$isRecopyEnabled) {
			return $value;
		}
		
		$dynamicToStaticOptionsCopy = collect($value)
			->filter(fn ($item, $key) => str_starts_with($key, 'default_'))
			->mapWithKeys(function ($item, $key) {
				$key = str($key)->replaceFirst('default_', 'static_')->toString();
				
				return [$key => $item];
			})
			->toArray();
		
		return array_merge($value, $dynamicToStaticOptionsCopy);
	}
}
