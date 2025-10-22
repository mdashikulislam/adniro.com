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

use App\Helpers\Common\Files\Upload;
use Illuminate\Support\Facades\Storage;

/*
 * settings.style.option
 */

class StyleSetting extends BaseSetting
{
	public static function passedValidation($request)
	{
		$mediaOpPath = 'larapen.media.resize.namedOptions';
		$params = [
			[
				'attribute' => 'body_background_image_path',
				'destPath'  => 'app/logo',
				'width'     => (int)config($mediaOpPath . '.bg-body.width', 2500),
				'height'    => (int)config($mediaOpPath . '.bg-body.height', 2500),
				'ratio'     => config($mediaOpPath . '.bg-body.ratio', '1'),
				'upsize'    => config($mediaOpPath . '.bg-body.upsize', '0'),
				'filename'  => 'body-background-',
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
	
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'skin'       => 'default',
			'page_width' => '1200',
			
			'admin_logo_bg'          => 'skin3',
			'admin_navbar_bg'        => 'skin6',
			'admin_sidebar_type'     => 'full',
			'admin_sidebar_bg'       => 'skin5',
			'admin_sidebar_position' => '1',
			'admin_header_position'  => '1',
			'admin_boxed_layout'     => '0',
			'admin_dark_theme'       => '0',
		];
		
		$value = array_merge($defaultValue, $value);
		
		/** @var $disk Storage */
		$filePathList = ['body_background_image_path'];
		foreach ($value as $key => $item) {
			if (in_array($key, $filePathList)) {
				if (empty($item) || !$disk->exists($item)) {
					$value[$key] = $defaultValue[$key] ?? null;
				}
			}
		}
		
		// Append files URLs
		// body_background_image_url
		$bodyBackgroundImage = $value['body_background_image_path'] ?? $value['body_background_image'] ?? null;
		$value['body_background_image_url'] = thumbService($bodyBackgroundImage, false)->resize('bg-body')->url();
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		// Get Pre-Defined Skins By Name
		$skins = getCachedReferrerList('skins');
		$skinsByName = collect($skins)
			->mapWithKeys(fn ($item, $key) => [$key => $item['name']])
			->toArray();
		
		$fields = [];
		
		$tabName = trans('admin.style_html_frontend');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'separator_1',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'skin',
			'label'   => trans('admin.Front Skin'),
			'type'    => 'select2_from_skins',
			'options' => $skinsByName,
			'skins'   => $skins,
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'custom_skin_color',
			'label'      => trans('admin.custom_skin_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFFFFF',
			],
			'hint'       => trans('admin.custom_skin_color_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'  => 'separator_2',
			'type'  => 'custom_html',
			'value' => trans('admin.style_html_customize_front'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'separator_2_1',
			'type'  => 'custom_html',
			'value' => trans('admin.style_html_customize_front_global'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'       => 'body_background_color',
			'label'      => trans('admin.background_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FFFFFF',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'body_text_color',
			'label'      => trans('admin.text_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#292B2C',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'body_background_image_path',
			'label'   => trans('admin.background_image_label'),
			'type'    => 'image',
			'upload'  => true,
			'disk'    => $diskName,
			'default' => null,
			'wrapper' => [
				'class' => 'col-md-12',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'        => 'body_background_image_position',
			'label'       => trans('admin.bg_image_position_label'),
			'type'        => 'select2_from_array',
			'options'     => collect(getCachedReferrerList('css/background-position'))
				->mapWithKeys(fn ($item) => [$item => $item])
				->toArray(),
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		$fields[] = [
			'name'        => 'body_background_image_size',
			'label'       => trans('admin.bg_image_size_label'),
			'type'        => 'select2_from_array',
			'options'     => collect(getCachedReferrerList('css/background-size'))
				->mapWithKeys(fn ($item) => [$item => $item])
				->toArray(),
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		$fields[] = [
			'name'        => 'body_background_image_repeat',
			'label'       => trans('admin.bg_image_repeat_label'),
			'type'        => 'select2_from_array',
			'options'     => collect(getCachedReferrerList('css/background-repeat'))
				->mapWithKeys(fn ($item) => [$item => $item])
				->toArray(),
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		$fields[] = [
			'name'        => 'body_background_image_attachment',
			'label'       => trans('admin.bg_image_attachment_label'),
			'type'        => 'select2_from_array',
			'options'     => collect(getCachedReferrerList('css/background-attachment'))
				->mapWithKeys(fn ($item) => [$item => $item])
				->toArray(),
			'allows_null' => true,
			'wrapper'     => [
				'class' => 'col-md-6',
			],
			'tab'         => $tabName,
		];
		$fields[] = [
			'name'    => 'body_background_image_animation',
			'label'   => trans('admin.bg_image_animation_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'page_width',
			'label'   => trans('admin.Page Width'),
			'type'    => 'number',
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'title_color',
			'label'      => trans('admin.Titles Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#292B2C',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'progress_background_color',
			'label'      => trans('admin.Progress Background Color'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'link_color',
			'label'      => trans('admin.link_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#4682B4',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'link_hover_color',
			'label'      => trans('admin.link_hover_color_label'),
			'type'       => 'color_picker',
			'attributes' => [
				'placeholder' => '#FF8C00',
			],
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'  => 'separator_3',
			'type'  => 'custom_html',
			'value' => trans('admin.style_html_raw_css'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'separator_3_1',
			'type'  => 'custom_html',
			'value' => trans('admin.style_html_raw_css_hint'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'       => 'custom_css',
			'label'      => trans('admin.Custom CSS'),
			'type'       => 'textarea',
			'attributes' => [
				'rows' => '10',
			],
			'hint'       => trans('admin.do_not_include_style_tags'),
			'tab'        => $tabName,
		];
		
		$tabName = trans('admin.backend_title_separator');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'backend_title_separator',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'admin_logo_bg',
			'label'   => trans('admin.admin_logo_bg_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'skin1' => 'Green',
				'skin2' => 'Red',
				'skin3' => 'Blue',
				'skin4' => 'Purple',
				'skin5' => 'Black',
				'skin6' => 'White',
			],
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'admin_navbar_bg',
			'label'   => trans('admin.admin_navbar_bg_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'skin1' => 'Green',
				'skin2' => 'Red',
				'skin3' => 'Blue',
				'skin4' => 'Purple',
				'skin5' => 'Black',
				'skin6' => 'White',
			],
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'admin_sidebar_type',
			'label'   => trans('admin.admin_sidebar_type_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'full'         => 'Full',
				'mini-sidebar' => 'Mini Sidebar',
				'iconbar'      => 'Icon Bbar',
				'overlay'      => 'Overlay',
			],
			'hint'    => trans('admin.admin_sidebar_type_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'admin_sidebar_bg',
			'label'   => trans('admin.admin_sidebar_bg_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'skin1' => 'Green',
				'skin2' => 'Red',
				'skin3' => 'Blue',
				'skin4' => 'Purple',
				'skin5' => 'Black',
				'skin6' => 'White',
			],
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'admin_sidebar_position',
			'label'   => trans('admin.admin_sidebar_position_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.admin_sidebar_position_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'admin_header_position',
			'label'   => trans('admin.admin_header_position_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.admin_header_position_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'admin_boxed_layout',
			'label'   => trans('admin.admin_boxed_layout_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.admin_boxed_layout_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
}
