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
use App\Helpers\Common\Files\Upload;
use App\Models\Language;
use App\Models\Section\BaseSection;
use Illuminate\Support\Facades\Storage;

class SearchFormSection extends BaseSection
{
	private static array $titleAnimationFieldsMapping = [
		'animation' => 'title_animation',
		'easing'    => 'title_animation_easing',
		'duration'  => 'title_animation_duration',
		'delay'     => 'title_animation_delay',
		'offset'    => 'title_animation_offset',
		'placement' => 'title_animation_placement',
		'once'      => 'title_animation_once',
	];
	private static array $subtitleAnimationFieldsMapping = [
		'animation' => 'subtitle_animation',
		'easing'    => 'subtitle_animation_easing',
		'duration'  => 'subtitle_animation_duration',
		'delay'     => 'subtitle_animation_delay',
		'offset'    => 'subtitle_animation_offset',
		'placement' => 'subtitle_animation_placement',
		'once'      => 'subtitle_animation_once',
	];
	private static array $searchbarAnimationFieldsMapping = [
		'animation' => 'searchbar_animation',
		'easing'    => 'searchbar_animation_easing',
		'duration'  => 'searchbar_animation_duration',
		'delay'     => 'searchbar_animation_delay',
		'offset'    => 'searchbar_animation_offset',
		'placement' => 'searchbar_animation_placement',
		'once'      => 'searchbar_animation_once',
	];
	
	public static function passedValidation($request)
	{
		$params = [
			[
				'attribute' => 'background_image_path',
				'destPath'  => 'app/logo',
				'width'     => (int)config('larapen.media.resize.namedOptions.bg-header.width', 2000),
				'height'    => (int)config('larapen.media.resize.namedOptions.bg-header.height', 1000),
				'ratio'     => config('larapen.media.resize.namedOptions.bg-header.ratio', '1'),
				'upsize'    => config('larapen.media.resize.namedOptions.bg-header.upsize', '0'),
				'filename'  => 'section-header-',
				'quality'   => 100,
			],
		];
		
		foreach ($params as $param) {
			$file = $request->hasFile($param['attribute'])
				? $request->file($param['attribute'])
				: $request->input($param['attribute']);
			
			$request->request->set($param['attribute'], Upload::image($file, $param['destPath'], $param));
		}
		
		return $request;
	}
	
	public static function getFieldValues($value, $disk): array
	{
		$defaultValue = self::getDefaultPreset(__CLASS__);
		
		$value = is_array($value) ? $value : [];
		
		$languages = self::getLanguages();
		if ($languages->count() > 0) {
			foreach ($languages as $language) {
				$title = $value['title_' . $language->code] ?? t('homepage_title_text', [], 'global', $language->code);
				$subTitle = $value['sub_title_' . $language->code] ?? t('simple_fast_and_efficient', [], 'global', $language->code);
				
				$value['title_' . $language->code] = $title;
				$value['sub_title_' . $language->code] = $subTitle;
			}
		}
		
		$value = array_merge($defaultValue, $value);
		$value = self::applyPreset(__CLASS__, $value);
		
		/** @var $disk Storage */
		$filePathList = ['background_image_path'];
		foreach ($value as $key => $item) {
			if (in_array($key, $filePathList)) {
				if (empty($item) || !$disk->exists($item)) {
					$value[$key] = $defaultValue[$key] ?? null;
				}
			}
		}
		
		// Append files URLs
		// background_image_url
		$backgroundImage = $value['background_image_path'] ?? $value['background_image'] ?? null;
		$value['background_image_url'] = thumbService($backgroundImage, false)->resize('bg-header')->url();
		
		// Build CSS class list based on defined options
		$value['css_classes'] = self::buildCssClassesAsString($value);
		
		// Get animation attributes
		$htmlAttributes = self::buildAnimationHtmlAttributes(value: $value, fieldsMapping: self::$titleAnimationFieldsMapping);
		$value['title_html_attributes'] = Arr::toAttributes($htmlAttributes);
		
		$htmlAttributes = self::buildAnimationHtmlAttributes(value: $value, fieldsMapping: self::$subtitleAnimationFieldsMapping);
		$value['subtitle_html_attributes'] = Arr::toAttributes($htmlAttributes);
		
		$htmlAttributes = self::buildAnimationHtmlAttributes(value: $value, fieldsMapping: self::$searchbarAnimationFieldsMapping);
		$value['searchbar_html_attributes'] = Arr::toAttributes($htmlAttributes);
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$languages = self::getLanguages();
		
		$fields = [];
		
		// General Options
		$fields[] = [
			'name'  => 'general_options_title',
			'type'  => 'custom_html',
			'value' => trans('admin.general_options_title'),
		];
		
		$fields = self::appendSpacingFormFields(fields: $fields, fieldSeparator: 'end');
		
		$fields[] = [
			'name'  => 'hide_on_mobile',
			'label' => trans('admin.hide_on_mobile_label'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.hide_on_mobile_hint'),
		];
		
		$fields[] = [
			'name'     => 'active',
			'label'    => trans('admin.Active'),
			'type'     => 'checkbox_switch',
			'fake'     => false,
			'store_in' => null,
		];
		
		// Search Bar
		$fields[] = [
			'name'  => 'search_form_searchbar_title',
			'type'  => 'custom_html',
			'value' => trans('admin.search_form_searchbar_title'),
		];
		
		$fields[] = [
			'name'       => 'form_border_color',
			'label'      => trans('admin.Form Border Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#333',
			],
			'hint'       => trans('admin.Enter a RGB color code'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'       => 'form_border_width',
			'label'      => trans('admin.Form Border Width'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '5',
				'min'         => 0,
				'max'         => 10,
				'step'        => 1,
			],
			'hint'       => trans('admin.Enter a number with unit'),
			'wrapper'    => [
				'class' => 'col-md-3',
			],
		];
		
		$fields[] = [
			'name'       => 'form_border_radius',
			'label'      => trans('admin.Form Border Radius'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '5',
				'min'         => 0,
				'max'         => 30,
				'step'        => 1,
			],
			'hint'       => trans('admin.Enter a number with unit'),
			'wrapper'    => [
				'class' => 'col-md-3',
			],
		];
		
		$fields[] = [
			'name'       => 'form_btn_background_color',
			'label'      => trans('admin.Form Button Background Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#4682B4',
			],
			'hint'       => trans('admin.Enter a RGB color code'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'       => 'form_btn_text_color',
			'label'      => trans('admin.Form Button Text Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFF',
			],
			'hint'       => trans('admin.Enter a RGB color code'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		// NAV TABS
		$fields[] = [
			'name'    => 'search_form_extended_title',
			'type'    => 'custom_html',
			'value'   => trans('admin.search_form_extended_title'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'enable_extended_form_area',
			'label'   => trans('admin.enable_extended_form_area_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.enable_extended_form_area_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$tabName = trans('admin.search_form_background_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'search_form_background_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => [
					'class' => 'col-md-12 extended',
				],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'full_height',
			'label'   => trans('admin.full_height_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.full_height_hint'),
			'wrapper' => [
				'class' => 'col-md-6 extended',
			],
			'tab'     => $tabName,
		];
		
		$scrollCueList = [
			''             => trans('admin.disable'),
			'mouseGraphic' => trans('admin.scroll_cue_mouse'),
			'chevronText'  => trans('admin.scroll_cue_chevron'),
			'progressBar'  => trans('admin.scroll_cue_bar'),
		];
		$fields[] = [
			'name'    => 'scroll_cue',
			'label'   => trans('admin.scroll_cue_label'),
			'type'    => 'select2_from_array',
			'options' => $scrollCueList,
			'hint'    => trans('admin.scroll_cue_hint'),
			'wrapper' => [
				'class' => 'col-md-6 extended full-height',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'background_color',
			'label'      => trans('admin.Background Color'),
			'type'       => 'color_picker',
			'attributes' => ['placeholder' => '#444'],
			'hint'       => trans('admin.Enter a RGB color code'),
			'wrapper'    => [
				'class' => 'col-md-12 extended',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'background_image_path',
			'label'   => trans('admin.Background Image'),
			'type'    => 'image',
			'upload'  => true,
			'disk'    => $diskName,
			'hint'    => trans('admin.search_form_background_image_hint'),
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'background_image_darken',
			'label'      => trans('admin.background_image_darken_label'),
			'type'       => 'range',
			'attributes' => [
				'placeholder' => '0.5',
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.05,
				'style'       => 'padding: 0;',
			],
			'default'    => 0,
			'hint'       => trans('admin.background_image_darken_hint'),
			'wrapper'    => [
				'class' => 'col-md-6 extended',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'       => 'height',
			'label'      => trans('admin.Height'),
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '500',
				'min'         => 45,
				'max'         => 2000,
				'step'        => 1,
			],
			'hint'       => trans('admin.Enter a value greater than 50px'),
			'wrapper'    => [
				'class' => 'col-md-6 extended',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'parallax',
			'label'   => trans('admin.Enable Parallax Effect'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-12 mt-3 extended',
			],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.search_form_title_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'search_form_title_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => [
					'class' => 'col-md-12 extended',
				],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'hide_title',
			'label'   => trans('admin.hide_title_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'title_dynamic_variables_stats_hint',
			'type'    => 'custom_html',
			'value'   => trans('admin.dynamic_variables_stats_hint'),
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		
		if ($languages->count() > 0) {
			foreach ($languages as $language) {
				$fields[] = [
					'name'       => 'title_' . $language->code,
					'label'      => mb_ucfirst(trans('admin.title')) . ' (' . $language->name . ')',
					'attributes' => [
						'placeholder' => t('homepage_title_text', [], 'global', $language->code),
					],
					'wrapper'    => [
						'class' => 'col-md-12 extended',
					],
					'tab'        => $tabName,
				];
			}
		}
		
		$fields[] = [
			'name'    => 'search_form_titles_color',
			'type'    => 'custom_html',
			'value'   => trans('admin.search_form_titles_color'),
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'big_title_color',
			'label'      => trans('admin.Big Title Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFF',
			],
			'hint'       => trans('admin.Enter a RGB color code'),
			'wrapper'    => [
				'class' => 'col-md-6 extended',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::animationFields(
			fields: $fields,
			fieldsMapping: self::$titleAnimationFieldsMapping,
			fieldsTitle: trans('admin.title_animation_title'),
			tab: $tabName,
			filterClass: 'extended'
		);
		
		$tabName = trans('admin.search_form_subtitle_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'search_form_subtitles',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => [
					'class' => 'col-md-12 extended',
				],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'hide_subtitle',
			'label'   => trans('admin.hide_subtitle_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'subtitle_dynamic_variables_stats_hint',
			'type'    => 'custom_html',
			'value'   => trans('admin.dynamic_variables_stats_hint'),
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		
		if ($languages->count() > 0) {
			foreach ($languages as $language) {
				$fields[] = [
					'name'       => 'sub_title_' . $language->code,
					'label'      => trans('admin.Sub Title') . ' (' . $language->name . ')',
					'attributes' => [
						'placeholder' => t('simple_fast_and_efficient', [], 'global', $language->code),
					],
					'wrapper'    => [
						'class' => 'col-md-12 extended',
					],
					'tab'        => $tabName,
				];
			}
		}
		
		$fields[] = [
			'name'    => 'search_form_subtitles_color',
			'type'    => 'custom_html',
			'value'   => trans('admin.search_form_subtitles_color'),
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'sub_title_color',
			'label'      => trans('admin.Sub Title Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFF',
			],
			'hint'       => trans('admin.Enter a RGB color code'),
			'wrapper'    => [
				'class' => 'col-md-6 extended',
			],
			'tab'        => $tabName,
		];
		
		$fields = self::animationFields(
			fields: $fields,
			fieldsMapping: self::$subtitleAnimationFieldsMapping,
			fieldsTitle: trans('admin.subtitle_animation_title'),
			tab: $tabName,
			filterClass: 'extended'
		);
		
		$tabName = trans('admin.search_form_searchbar_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'ex_search_form_searchbar_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => [
					'class' => 'col-md-12 extended',
				],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'hide_searchbar',
			'label'   => trans('admin.hide_searchbar_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-12 extended',
			],
			'tab'     => $tabName,
		];
		$fields = self::animationFields(
			fields: $fields,
			fieldsMapping: self::$searchbarAnimationFieldsMapping,
			fieldsTitle: trans('admin.searchbar_animation_title'),
			tab: $tabName,
			filterClass: 'extended'
		);
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
	
	/**
	 * Get active languages
	 *
	 * @return mixed
	 */
	private static function getLanguages(): mixed
	{
		$cacheParams = [
			'action' => 'get.languages',
			'active' => true,
		];
		
		return caching()->remember(Language::class, $cacheParams, function () {
			return Language::query()->active()->get();
		});
	}
}
