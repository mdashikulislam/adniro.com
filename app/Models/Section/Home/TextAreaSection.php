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
use App\Models\Language;
use App\Models\Section\BaseSection;

class TextAreaSection extends BaseSection
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
		$wysiwygEditor = config('settings.other.wysiwyg_editor');
		$wysiwygEditorViewPath = 'views/admin/panel/fields/' . $wysiwygEditor . '.blade.php';
		
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
			'name'  => 'dynamic_variables_hint',
			'type'  => 'custom_html',
			'value' => trans('admin.dynamic_variables_hint'),
			'tab'   => $tabName,
		];
		
		$languages = self::getLanguages();
		if ($languages->count() > 0) {
			foreach ($languages as $language) {
				$titleLabel = mb_ucfirst(trans('admin.title')) . ' (' . $language->name . ')';
				$bodyLabel = trans('admin.body_label') . ' (' . $language->name . ')';
				
				$fields[] = [
					'name'       => 'title_' . $language->code,
					'label'      => $titleLabel,
					'type'       => 'text',
					'attributes' => [
						'placeholder' => $titleLabel,
					],
					'wrapper'    => [
						'class' => 'col-md-12',
					],
					'tab'        => $tabName,
				];
				$fields[] = [
					'name'       => 'body_' . $language->code,
					'label'      => $bodyLabel,
					'type'       => ($wysiwygEditor != 'none' && file_exists(resource_path($wysiwygEditorViewPath)))
						? $wysiwygEditor
						: 'textarea',
					'attributes' => [
						'placeholder' => $bodyLabel,
						'id'          => 'description',
						'rows'        => 5,
					],
					'hint'       => trans('admin.body_hint') . ' (' . $language->name . ')',
					'wrapper'    => [
						'class' => 'col-md-12',
					],
					'tab'        => $tabName,
				];
				
				$fields[] = [
					'name'  => 'seo_start_' . $language->code,
					'type'  => 'custom_html',
					'value' => '<hr style="border: 1px dashed #EFEFEF;" class="my-3">',
					'tab'   => $tabName,
				];
			}
		}
		
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
