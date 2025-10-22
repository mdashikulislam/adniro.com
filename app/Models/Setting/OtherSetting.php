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

/*
 * settings.other.option
 */

class OtherSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'account_closure_enabled'     => '1',
			'cookie_consent_enabled'      => '0',
			'show_tips_messages'          => '1',
			'timer_new_messages_checking' => 60000,
			'wysiwyg_editor'              => 'tinymce',
			
			'carousel_slide_by_page'        => '0',
			'carousel_mouse_drag'           => '0',
			'carousel_loop'                 => '1',
			'carousel_rewind'               => '0',
			'carousel_autoplay'             => '1',
			'carousel_autoplay_timeout'     => '1500',
			'carousel_autoplay_hover_pause' => '1',
			'carousel_nav'                  => '1',
			'carousel_nav_position'         => 'bottom',
			'carousel_controls'             => '0',
			'carousel_ctrl_position'        => 'top-end',
			
			'cookie_expiration'              => 1440,
			'google_maps_javascript_api_key' => $value['googlemaps_key'] ?? null, // from old saved value
		];
		
		return array_merge($defaultValue, $value);
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		// Gmaps integration types List
		$gmapsIntegrationTypes = [
			'maps_embed' => trans('admin.google_maps_embed_api'),
			'geocoding'  => trans('admin.google_geocoding_api'),
		];
		
		// Get the gmaps integration types selectors list as JS objects
		$gmapsIntegrationTypesSelectorsJson = collect($gmapsIntegrationTypes)
			->keys()
			->mapWithKeys(fn ($item) => [$item => '.' . $item])
			->toJson();
		
		$wysiwygEditors = (array)config('larapen.options.wysiwyg');
		
		$fields = [];
		
		$fields[] = [
			'name'  => 'account_option_title',
			'type'  => 'custom_html',
			'value' => trans('admin.account_option_title'),
		];
		$fields[] = [
			'name'    => 'account_closure_enabled',
			'label'   => trans('admin.account_closure_enabled_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.account_closure_enabled_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'  => 'separator_1',
			'type'  => 'custom_html',
			'value' => trans('admin.other_html_alerts_boxes'),
		];
		$fields[] = [
			'name'    => 'cookie_consent_enabled',
			'label'   => trans('admin.Cookie Consent Enabled'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.Enable Cookie Consent Alert to comply for EU law'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'show_tips_messages',
			'label'   => trans('admin.Show Tips Notification Messages'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.show_tips_messages_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'  => 'google_maps_platform',
			'type'  => 'custom_html',
			'value' => trans('admin.google_maps_platform_title'),
		];
		$fields[] = [
			'name'    => 'google_maps_javascript_api_key',
			'label'   => trans('admin.google_maps_javascript_api_key_label'),
			'type'    => 'text',
			'hint'    => trans('admin.google_maps_javascript_api_key_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'google_maps_integration_type',
			'label'   => trans('admin.google_maps_integration_type_label'),
			'type'    => 'select2_from_array',
			'options' => $gmapsIntegrationTypes,
			'hint'    => trans('admin.google_maps_integration_type_hint', [
				'maps_embed'          => trans('admin.google_maps_embed_api'),
				'geocoding'           => trans('admin.google_geocoding_api'),
				'maps_javascript_api' => trans('admin.google_maps_javascript_api'),
			]),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'google_maps_embed_api_key',
			'label'   => trans('admin.google_maps_embed_api_key_label'),
			'type'    => 'text',
			'hint'    => trans('admin.google_maps_embed_api_key_hint', ['maps_javascript_api' => trans('admin.google_maps_embed_api')]),
			'wrapper' => [
				'class' => 'col-md-6 maps_embed',
			],
		];
		$fields[] = [
			'name'    => 'google_geocoding_api_key',
			'label'   => trans('admin.google_geocoding_api_key_label'),
			'type'    => 'text',
			'hint'    => trans('admin.google_geocoding_api_key_hint', ['maps_javascript_api' => trans('admin.google_maps_embed_api')]),
			'wrapper' => [
				'class' => 'col-md-6 geocoding',
			],
		];
		$fields[] = [
			'name'    => 'use_async_geocoding',
			'label'   => trans('admin.use_async_geocoding_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.use_async_geocoding_hint', ['geocoding' => trans('admin.google_geocoding_api')]),
			'wrapper' => [
				'class' => 'col-md-6 mt-4 geocoding',
			],
		];
		
		$fields[] = [
			'name'  => 'separator_3',
			'type'  => 'custom_html',
			'value' => trans('admin.other_html_messenger'),
		];
		$fields[] = [
			'name'       => 'timer_new_messages_checking',
			'label'      => trans('admin.Timer for New Messages Checking'),
			'type'       => 'number',
			'attributes' => [
				'min'      => 0,
				'step'     => 2000,
				'required' => true,
			],
			'hint'       => trans('admin.timer_new_messages_checking_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'  => 'separator_4',
			'type'  => 'custom_html',
			'value' => trans('admin.textarea_editor_h3'),
		];
		$fields[] = [
			'name'    => 'wysiwyg_editor',
			'label'   => trans('admin.wysiwyg_editor_label'),
			'type'    => 'select2_from_array',
			'options' => $wysiwygEditors,
			'hint'    => trans('admin.wysiwyg_editor_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		
		$navPositions = config('larapen.options.carousel.navPositions');
		$ctrlPositions = config('larapen.options.carousel.ctrlPositions');
		
		// Carousel
		$fields[] = [
			'name'  => 'carousel_title',
			'type'  => 'custom_html',
			'value' => trans('admin.carousel_title'),
		];
		$fields[] = [
			'name'    => 'carousel_slide_by_page',
			'label'   => trans('admin.carousel_slide_by_page_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.carousel_slide_by_page_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'carousel_mouse_drag',
			'label'   => trans('admin.carousel_mouse_drag_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.carousel_mouse_drag_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'carousel_loop',
			'label'   => trans('admin.carousel_loop_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.carousel_loop_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'carousel_rewind',
			'label'   => trans('admin.carousel_rewind_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.carousel_rewind_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'    => 'carousel_autoplay',
			'label'   => trans('admin.carousel_autoplay_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-3 mt-2',
			],
		];
		$fields[] = [
			'name'       => 'carousel_autoplay_timeout',
			'label'      => trans('admin.carousel_autoplay_timeout_label'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => 1500,
				'min'         => 0,
				'step'        => 1,
			],
			'wrapper'    => [
				'class' => 'col-md-3',
			],
		];
		$fields[] = [
			'name'    => 'carousel_autoplay_hover_pause',
			'label'   => trans('admin.carousel_autoplay_hover_pause_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6 mt-2',
			],
		];
		
		$fields[] = [
			'name'    => 'carousel_nav',
			'label'   => trans('admin.carousel_nav_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6 mt-3',
			],
		];
		$fields[] = [
			'name'    => 'carousel_nav_position',
			'label'   => trans('admin.carousel_nav_position_label'),
			'type'    => 'select2_from_array',
			'options' => collect($navPositions)
				->mapWithKeys(function ($item) {
					$langKey = str($item)->slug('_')->toString();
					
					return [$item => trans("admin.{$langKey}")];
				})->toArray(),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'carousel_controls',
			'label'   => trans('admin.carousel_controls_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6 mt-3',
			],
		];
		$fields[] = [
			'name'    => 'carousel_ctrl_position',
			'label'   => trans('admin.carousel_ctrl_position_label'),
			'type'    => 'select2_from_array',
			'options' => collect($ctrlPositions)
				->mapWithKeys(function ($item) {
					$langKey = str($item)->slug('_')->toString();
					
					return [$item => trans("admin.{$langKey}")];
				})->toArray(),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'  => 'separator_6',
			'type'  => 'custom_html',
			'value' => trans('admin.other_html_number_format'),
		];
		$fields[] = [
			'name'  => 'decimals_superscript',
			'label' => trans('admin.Decimals Superscript'),
			'type'  => 'checkbox_switch',
		];
		
		$fields[] = [
			'name'  => 'cookie_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.cookie_sep_value'),
		];
		$fields[] = [
			'name'    => 'cookie_expiration',
			'label'   => trans('admin.cookie_expiration_label'),
			'type'    => 'number',
			'hint'    => trans('admin.cookie_expiration_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'  => 'separator_8',
			'type'  => 'custom_html',
			'value' => trans('admin.other_html_head_js'),
		];
		$fields[] = [
			'name'       => 'js_code',
			'label'      => trans('admin.JavaScript Code'),
			'type'       => 'textarea',
			'attributes' => [
				'rows' => '10',
			],
			'hint'       => trans('admin.js_code_hint'),
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields, [
			'gmapsIntegrationTypesSelectorsJson' => $gmapsIntegrationTypesSelectorsJson,
		]);
	}
}
