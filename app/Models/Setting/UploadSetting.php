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
 * settings.upload.option
 */

class UploadSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$config = config('larapen.media.resize.namedOptions.default');
		$method = data_get($config, 'method', 'resize');
		$width = data_get($config, 'width', 900);
		$height = data_get($config, 'height', 900);
		$ratio = data_get($config, 'ratio', '1');
		$upsize = data_get($config, 'upsize', '0');
		$position = data_get($config, 'position', 'center');
		$relative = data_get($config, 'relative', false);
		$bgColor = data_get($config, 'bgColor', 'ffffff');
		
		$resizeOptionsNamesArray = array_keys((array)config('larapen.media.resize.namedOptions'));
		$defaultFileTypes = collect(getRecommendedFileFormats())->join(',');
		$defaultImageTypes = collect(getServerInstalledImageFormats())->join(',');
		// $defaultClientImageTypes = collect(getClientInstalledImageFormats())->join(',');
		
		// default
		$settingKeyPrefix = 'img_resize_default';
		
		$defaultValue = [
			'file_types'                    => $defaultFileTypes,
			'min_file_size'                 => '0',
			'max_file_size'                 => '2500',
			'image_types'                   => $defaultImageTypes,
			'image_quality'                 => '90',
			'client_image_types'            => 'jpg,png',
			'min_image_size'                => '0',
			'max_image_size'                => '2500',
			$settingKeyPrefix . '_method'   => $method,
			$settingKeyPrefix . '_width'    => $width,
			$settingKeyPrefix . '_height'   => $height,
			$settingKeyPrefix . '_ratio'    => $ratio,
			$settingKeyPrefix . '_upsize'   => $upsize,
			$settingKeyPrefix . '_position' => $position,
			$settingKeyPrefix . '_relative' => $relative,
			$settingKeyPrefix . '_bgColor'  => $bgColor,
		];
		
		$value = array_merge($defaultValue, $value);
		
		// others
		foreach ($resizeOptionsNamesArray as $optionsName) {
			$config = config('larapen.media.resize.namedOptions.' . $optionsName);
			$settingKeyPrefix = 'img_resize_' . str_replace('-', '_', $optionsName);
			
			if (!array_key_exists($settingKeyPrefix . '_method', $value)) {
				$value[$settingKeyPrefix . '_method'] = data_get($config, 'method', $method);
			}
			if (!array_key_exists($settingKeyPrefix . '_width', $value)) {
				$value[$settingKeyPrefix . '_width'] = data_get($config, 'width', $width);
			}
			if (!array_key_exists($settingKeyPrefix . '_height', $value)) {
				$value[$settingKeyPrefix . '_height'] = data_get($config, 'height', $height);
			}
			if (!array_key_exists($settingKeyPrefix . '_ratio', $value)) {
				$value[$settingKeyPrefix . '_ratio'] = data_get($config, 'ratio', $ratio);
			}
			if (!array_key_exists($settingKeyPrefix . '_upsize', $value)) {
				$value[$settingKeyPrefix . '_upsize'] = data_get($config, 'upsize', $upsize);
			}
			if (!array_key_exists($settingKeyPrefix . '_position', $value)) {
				$value[$settingKeyPrefix . '_position'] = data_get($config, 'position', $position);
			}
			if (!array_key_exists($settingKeyPrefix . '_relative', $value)) {
				$value[$settingKeyPrefix . '_relative'] = data_get($config, 'relative', $relative);
			}
			if (!array_key_exists($settingKeyPrefix . '_bgColor', $value)) {
				$value[$settingKeyPrefix . '_bgColor'] = data_get($config, 'bgColor', $bgColor);
			}
		}
		
		// Get right values
		// Numeric values (keys: upload, ...)
		foreach ($value as $k => $v) {
			if (
				(str($k)->startsWith(['img_resize_']) && str($k)->endsWith(['_width', '_height']))
				|| str($k)->endsWith(['_file_size', '_image_size'])
			) {
				$value[$k] = forceToInt($v);
			}
		}
		
		// 'bgColor' & 'relative' get format
		foreach ($resizeOptionsNamesArray as $optionsName) {
			$settingKeyPrefix = 'img_resize_' . str_replace('-', '_', $optionsName);
			
			if (array_key_exists($settingKeyPrefix . '_bgColor', $value)) {
				$value[$settingKeyPrefix . '_bgColor'] = getHtmlColor($value[$settingKeyPrefix . '_bgColor']);
				
				if (!isAdminPanel()) {
					$value[$settingKeyPrefix . '_relative'] = ($value[$settingKeyPrefix . '_relative'] == '1');
					$value[$settingKeyPrefix . '_bgColor'] = str_replace('#', '', $value[$settingKeyPrefix . '_bgColor']);
				}
			}
		}
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		// Numeric values (keys: upload, ...)
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				if (
					(str($k)->startsWith(['img_resize_']) && str($k)->endsWith(['_width', '_height']))
					|| str($k)->endsWith(['_file_size', '_image_size'])
				) {
					$value[$k] = forceToInt($v);
				}
			}
		}
		
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$and = t('_and_');
		
		$defaultFileTypes = collect(getRecommendedFileFormats())
			->map(fn ($item) => ('<code>' . $item . '</code>'))
			->join(', ', $and);
		
		$defaultImageTypes = collect(getServerInstalledImageFormats())
			->map(fn ($item) => ('<code>' . $item . '</code>'))
			->join(', ', $and);
		
		$defaultClientImageTypes = collect(getClientInstalledImageFormats())
			->map(fn ($item) => ('<code>' . $item . '</code>'))
			->join(', ', $and);
		
		$clientImageFallbackExtension = str(getClientImageFallbackExtension())
			->wrap('<code>', '</code>')
			->toString();
		
		$fields = [];
		
		$tabName = trans('admin.upload_images_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'upload_images_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'image_types',
			'label'   => trans('admin.image_types_label'),
			'type'    => 'text',
			'hint'    => trans('admin.image_types_hint', ['imageTypes' => $defaultImageTypes]),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'image_quality',
			'label'   => trans('admin.image_quality_label'),
			'type'    => 'select2_from_array',
			'options' => collect(generateNumberRange(10, 100, 10))->mapWithKeys(fn ($i) => [$i => $i])->toArray(),
			'hint'    => trans('admin.image_quality_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'image_trimming_tolerance',
			'label'   => trans('admin.image_trimming_tolerance_label'),
			'type'    => 'select2_from_array',
			'options' => collect(generateNumberRange(0, 20, 1))->mapWithKeys(fn ($i) => [$i => $i])->toArray(),
			'hint'    => trans('admin.image_trimming_tolerance_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'client_image_types',
			'label'   => trans('admin.client_image_types_label'),
			'type'    => 'text',
			'hint'    => trans('admin.client_image_types_hint', [
				'fallbackExtension' => $clientImageFallbackExtension,
				'clientImageTypes'  => $defaultClientImageTypes,
			]),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'min_image_size',
			'label'   => trans('admin.min_image_size_label'),
			'type'    => 'number',
			'hint'    => trans('admin.min_image_size_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'max_image_size',
			'label'   => trans('admin.max_image_size_label'),
			'type'    => 'number',
			'hint'    => trans('admin.max_image_size_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'image_progressive',
			'label'   => trans('admin.image_progressive_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.image_progressive_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.upload_files_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'upload_files_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'file_types',
			'label'   => trans('admin.file_types_label'),
			'type'    => 'text',
			'hint'    => trans('admin.file_types_hint', ['fileTypes' => $defaultFileTypes]),
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'min_file_size',
			'label'   => trans('admin.min_file_size_label'),
			'type'    => 'number',
			'hint'    => trans('admin.min_file_size_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'max_file_size',
			'label'   => trans('admin.max_file_size_label'),
			'type'    => 'number',
			'hint'    => trans('admin.max_file_size_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.img_resize_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'img_resize_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'img_resize_default_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.img_resize_default_sep_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_default_width',
			'label'   => trans('admin.img_resize_width_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_width_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_default_height',
			'label'   => trans('admin.img_resize_height_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_height_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_default_ratio',
			'label'   => trans('admin.img_resize_ratio_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_ratio_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_default_upsize',
			'label'   => trans('admin.img_resize_upsize_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_upsize_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		// logo
		$fields[] = [
			'name'  => 'img_resize_logo_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.img_resize_logo_sep_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_logo_method',
			'label'   => trans('admin.img_resize_type_resize_method_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizeMethods(),
			'hint'    => trans('admin.img_resize_type_resize_method_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'img_resize_logo_width',
			'label'   => trans('admin.img_resize_width_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_width_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_logo_height',
			'label'   => trans('admin.img_resize_height_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_height_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_logo_ratio',
			'label'   => trans('admin.img_resize_ratio_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_ratio_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_logo_upsize',
			'label'   => trans('admin.img_resize_upsize_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_upsize_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_logo_position',
			'label'   => trans('admin.img_resize_type_position_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizePositions(),
			'hint'    => trans('admin.img_resize_type_position_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_logo_relative',
			'label'   => trans('admin.img_resize_type_relative_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_relative_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'img_resize_logo_bgColor',
			'label'      => trans('admin.img_resize_type_bgColor_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFFFFF',
			],
			'hint'       => trans('admin.img_resize_type_bg_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-4',
			],
			'tab'        => $tabName,
		];
		
		// asset.cat
		$fields[] = [
			'name'  => 'img_resize_cat_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.img_resize_cat_sep_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_cat_width',
			'label'   => trans('admin.img_resize_width_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_width_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_cat_height',
			'label'   => trans('admin.img_resize_height_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_height_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_cat_ratio',
			'label'   => trans('admin.img_resize_ratio_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_ratio_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_cat_upsize',
			'label'   => trans('admin.img_resize_upsize_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_upsize_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.img_resize_type_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'img_resize_type_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'img_resize_small_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.img_resize_small_sep_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_sm_method',
			'label'   => trans('admin.img_resize_type_resize_method_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizeMethods(),
			'hint'    => trans('admin.img_resize_type_resize_method_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'img_resize_picture_sm_width',
			'label'   => trans('admin.img_resize_type_width_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_type_width_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_sm_height',
			'label'   => trans('admin.img_resize_type_height_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_type_height_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_sm_ratio',
			'label'   => trans('admin.img_resize_type_ratio_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_ratio_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_sm_upsize',
			'label'   => trans('admin.img_resize_type_upsize_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_upsize_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_sm_position',
			'label'   => trans('admin.img_resize_type_position_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizePositions(),
			'hint'    => trans('admin.img_resize_type_position_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_sm_relative',
			'label'   => trans('admin.img_resize_type_relative_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_relative_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'img_resize_picture_sm_bgColor',
			'label'      => trans('admin.img_resize_type_bgColor_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFFFFF',
			],
			'hint'       => trans('admin.img_resize_type_bg_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-4',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'  => 'img_resize_medium_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.img_resize_medium_sep_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_md_method',
			'label'   => trans('admin.img_resize_type_resize_method_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizeMethods(),
			'hint'    => trans('admin.img_resize_type_resize_method_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'img_resize_picture_md_width',
			'label'   => trans('admin.img_resize_type_width_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_type_width_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_md_height',
			'label'   => trans('admin.img_resize_type_height_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_type_height_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_md_ratio',
			'label'   => trans('admin.img_resize_type_ratio_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_ratio_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_md_upsize',
			'label'   => trans('admin.img_resize_type_upsize_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_upsize_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_md_position',
			'label'   => trans('admin.img_resize_type_position_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizePositions(),
			'hint'    => trans('admin.img_resize_type_position_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_md_relative',
			'label'   => trans('admin.img_resize_type_relative_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_relative_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'img_resize_picture_md_bgColor',
			'label'      => trans('admin.img_resize_type_bgColor_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFFFFF',
			],
			'hint'       => trans('admin.img_resize_type_bg_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-4',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'  => 'img_resize_large_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.img_resize_large_sep_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_lg_method',
			'label'   => trans('admin.img_resize_type_resize_method_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizeMethods(),
			'hint'    => trans('admin.img_resize_type_resize_method_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'img_resize_picture_lg_width',
			'label'   => trans('admin.img_resize_type_width_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_type_width_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_lg_height',
			'label'   => trans('admin.img_resize_type_height_label'),
			'type'    => 'number',
			'hint'    => trans('admin.img_resize_type_height_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_lg_ratio',
			'label'   => trans('admin.img_resize_type_ratio_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_ratio_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_lg_upsize',
			'label'   => trans('admin.img_resize_type_upsize_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_upsize_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_lg_position',
			'label'   => trans('admin.img_resize_type_position_label'),
			'type'    => 'select2_from_array',
			'options' => self::resizePositions(),
			'hint'    => trans('admin.img_resize_type_position_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'img_resize_picture_lg_relative',
			'label'   => trans('admin.img_resize_type_relative_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.img_resize_type_relative_hint'),
			'wrapper' => [
				'class' => 'col-md-4',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'img_resize_picture_lg_bgColor',
			'label'      => trans('admin.img_resize_type_bgColor_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFFFFF',
			],
			'hint'       => trans('admin.img_resize_type_bg_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-4',
			],
			'tab'        => $tabName,
		];
		
		if (
			doesUserHavePermission(auth()->user(), 'clear-images-thumbnails')
			|| userHasSuperAdminPermissions()
		) {
			$tabName = trans('admin.clear_images_thumbnails_sep_value');
			if (self::getPanelTabsType() == 'vertical') {
				$fields[] = [
					'name'  => 'clear_images_thumbnails_sep',
					'type'  => 'custom_html',
					'value' => $tabName,
					'tab'   => $tabName,
				];
			}
			
			if (config('settings.optimization.queue_driver') != 'sync') {
				$fields[] = [
					'name'  => 'queue_thumbnails_regeneration',
					'type'  => 'custom_html',
					'value' => trans('admin.card_light_warning', [
						'content' => trans('admin.queue_thumbnails_regeneration', [
							'queueOptionUrl' => urlGen()->adminUrl('settings/find/optimization'),
						]),
					]),
					'tab'   => $tabName,
				];
			} else {
				$fields[] = [
					'name'  => 'clear_images_thumbnails_warning',
					'type'  => 'custom_html',
					'value' => trans('admin.card_danger', [
						'content' => trans('admin.clear_images_thumbnails_warning', [
							'queueOptionUrl' => urlGen()->adminUrl('settings/find/optimization'),
						]),
					]),
					'tab'   => $tabName,
				];
			}
			
			$fields[] = [
				'name'  => 'clear_images_thumbnails_bnt',
				'type'  => 'custom_html',
				'value' => trans('admin.clear_images_thumbnails_btn_value'),
				'tab'   => $tabName,
			];
			
			$fields[] = [
				'name'  => 'clear_images_thumbnails_info',
				'type'  => 'custom_html',
				'value' => trans('admin.card_light_inverse', ['content' => trans('admin.clear_images_thumbnails_info_value')]),
				'tab'   => $tabName,
			];
		}
		
		return $fields;
	}
	
	/**
	 * @return array
	 */
	private static function resizeMethods(): array
	{
		// Note: This is not Intervention referrers
		$methods = config('larapen.media.resize.methods');
		
		return collect($methods)
			->mapWithKeys(fn ($item) => [$item => ucfirst($item)])
			->toArray();
	}
	
	/**
	 * @return array
	 */
	private static function resizePositions(): array
	{
		// Note: These are Intervention referrers
		$positions = config('larapen.media.resize.positions');
		
		return collect($positions)
			->mapWithKeys(fn ($item) => [$item => str($item)->headline()->toString()])
			->toArray();
	}
}
