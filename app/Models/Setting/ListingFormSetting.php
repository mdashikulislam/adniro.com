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
 * settings.listing_form.option
 */

class ListingFormSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'publication_form_type'       => 'multi-steps-form',
			'city_selection'              => 'modal',
			'picture_mandatory'           => '1',
			'listings_limit'              => '50',
			'pictures_limit'              => '5',
			'title_min_length'            => '2',
			'title_max_length'            => '150',
			'description_min_length'      => '5',
			'description_max_length'      => '6000',
			'tags_limit'                  => '15',
			'tags_min_length'             => '2',
			'tags_max_length'             => '30',
			'guest_can_submit_listings'   => '0',
			'permanent_listings_enabled'  => '0',
			'default_package_type'        => 'promotion',
			'mandatory_package_selection' => $value['pricing_page_enabled'] ?? '0', // from old saved value
			'utf8mb4_enabled'             => isUtf8mb4Available() ? '1' : '0',
			'allow_emojis'                => '0',
			'cat_display_type'            => 'c_bigIcon_list',
			'wysiwyg_editor'              => 'tinymce',
			'auto_registration'           => '0',
		];
		
		$value = array_merge($defaultValue, $value);
		
		// Retrieve value from old versions
		$formType = $value['publication_form_type'] ?? null;
		$formType = $formType == '1' ? 'multi-steps-form' : $formType;
		$formType = $formType == '2' ? 'single-step-form' : $formType;
		$value['publication_form_type'] = $formType ?? 'multi-steps-form';
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$formTypes = [
			'multi-steps-form' => trans('admin.publication_multi_steps_form'),
			'single-step-form' => trans('admin.publication_single_step_form'),
		];
		$wysiwygEditors = (array)config('larapen.options.wysiwyg');
		
		// Get the form types list as JS objects
		$formTypesSelectorsJson = collect($formTypes)
			->keys()
			->mapWithKeys(fn ($item) => [$item => '.' . $item])
			->toJson();
		
		$fields = [];
		
		$tabName = trans('admin.general_separator_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'general_separator_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'publication_form_type',
			'label'   => trans('admin.publication_form_type_label'),
			'type'    => 'select2_from_array',
			'options' => $formTypes,
			'hint'    => trans('admin.publication_form_type_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'one_picture_field_for_multiple_selections',
			'label'   => trans('admin.one_picture_field_for_multiple_selections_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.one_picture_field_for_multiple_selections_hint'),
			'wrapper' => ['class' => 'col-md-6 mt-4 single-step-form'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'city_selection',
			'label'   => trans('admin.city_selection_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'select' => trans('admin.city_selection_option_1'),
				'modal'  => trans('admin.city_selection_option_2'),
			],
			'hint'    => trans('admin.city_selection_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'       => 'title_min_length',
			'label'      => trans('admin.title_min_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 255,
				'step' => 1,
			],
			'hint'       => trans('admin.title_min_length_hint'),
			'wrapper'    => ['class' => 'col-md-3'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'title_max_length',
			'label'      => trans('admin.title_max_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => 255,
				'step' => 1,
			],
			'hint'       => trans('admin.title_max_length_hint'),
			'wrapper'    => ['class' => 'col-md-3'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'description_min_length',
			'label'      => trans('admin.description_min_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 16777215,
				'step' => 1,
			],
			'hint'       => trans('admin.description_min_length_hint'),
			'wrapper'    => ['class' => 'col-md-3'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'description_max_length',
			'label'      => trans('admin.description_max_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => 16777215,
				'step' => 1,
			],
			'hint'       => trans('admin.description_max_length_hint'),
			'wrapper'    => ['class' => 'col-md-3'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'price_mandatory',
			'label'   => trans('admin.price_mandatory_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.price_mandatory_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'picture_mandatory',
			'label'   => trans('admin.picture_mandatory_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.picture_mandatory_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'listings_limit',
			'label'   => trans('admin.listings_limit_label'),
			'type'    => 'number',
			'hint'    => trans('admin.listings_limit_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'pictures_limit',
			'label'   => trans('admin.pictures_limit_label'),
			'type'    => 'number',
			'hint'    => trans('admin.pictures_limit_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'tags_limit',
			'label'   => trans('admin.tags_limit_label'),
			'type'    => 'number',
			'hint'    => trans('admin.tags_limit_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'tags_min_length',
			'label'      => trans('admin.tags_min_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 0,
				'max'  => 16777215,
				'step' => 1,
			],
			'hint'       => trans('admin.tags_min_length_hint'),
			'wrapper'    => ['class' => 'col-md-3'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'tags_max_length',
			'label'      => trans('admin.tags_max_length_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => 16777215,
				'step' => 1,
			],
			'hint'       => trans('admin.tags_max_length_hint'),
			'wrapper'    => ['class' => 'col-md-3'],
			'newline'    => true,
			'tab'        => $tabName,
		];
		
		$fields[] = [
			'name'    => 'listings_review_activation',
			'label'   => trans('admin.Allow listings to be reviewed by Admins'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.listings_review_activation_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'permanent_listings_enabled',
			'label'   => trans('admin.permanent_listings_label'),
			'type'    => 'select2_from_array',
			'options' => [
				0 => trans('admin.permanent_listings_option_0'),
				4 => trans('admin.permanent_listings_option_4'),
			],
			'hint'    => trans('admin.permanent_listings_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'newline' => true,
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'    => 'mandatory_package_selection',
			'label'   => trans('admin.mandatory_package_selection_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.card_light_inverse', ['content' => trans('admin.mandatory_package_selection_hint')]),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'default_package_type',
			'label'   => trans('admin.default_package_type_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'promotion'    => mb_ucfirst(trans('admin.promotion')),
				'subscription' => mb_ucfirst(trans('admin.subscription')),
			],
			'hint'    => trans('admin.default_package_type_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		
		if (isUtf8mb4Available()) {
			$fields[] = [
				'name'    => 'utf8mb4_enabled',
				'label'   => trans('admin.utf8mb4_enabled_label'),
				'type'    => 'checkbox_switch',
				'hint'    => trans('admin.utf8mb4_enabled_hint'),
				'wrapper' => ['class' => 'col-md-6'],
				'tab'     => $tabName,
			];
			$fields[] = [
				'name'    => 'allow_emojis',
				'label'   => trans('admin.allow_emojis_label'),
				'type'    => 'checkbox_switch',
				'hint'    => trans('admin.allow_emojis_hint'),
				'wrapper' => ['class' => 'col-md-6 utf8mb4-field'],
				'newline' => true,
				'tab'     => $tabName,
			];
		}
		
		$fields[] = [
			'name'    => 'show_listing_type',
			'label'   => trans('admin.show_listing_type_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.show_listing_type_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'enable_post_uniqueness',
			'label'   => trans('admin.enable_post_uniqueness_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.enable_post_uniqueness_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'  => 'form_cat_selection_title',
			'type'  => 'custom_html',
			'value' => trans('admin.form_cat_selection_title'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'        => 'cat_display_type',
			'label'       => trans('admin.form_cat_display_type_label'),
			'type'        => 'select2_from_array',
			'options'     => [
				'c_normal_list'  => trans('admin.cat_display_type_op_1'),
				'c_border_list'  => trans('admin.cat_display_type_op_2'),
				'c_bigIcon_list' => trans('admin.cat_display_type_op_3'),
				'c_picture_list' => trans('admin.cat_display_type_op_4'),
			],
			'allows_null' => false,
			'hint'        => trans('admin.form_cat_display_type_hint'),
			'wrapper'     => ['class' => 'col-md-6'],
			'tab'         => $tabName,
		];
		
		$fields[] = [
			'name'  => 'wysiwyg_editor_title',
			'type'  => 'custom_html',
			'value' => trans('admin.wysiwyg_editor_title_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'wysiwyg_editor',
			'label'   => trans('admin.wysiwyg_editor_label'),
			'type'    => 'select2_from_array',
			'options' => $wysiwygEditors,
			'hint'    => trans('admin.wysiwyg_editor_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.listing_input_cleanup_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'listing_input_cleanup_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		
		$fields[] = [
			'name'  => 'remove_url_title',
			'type'  => 'custom_html',
			'value' => trans('admin.remove_url_title_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'remove_url_before',
			'label'   => trans('admin.remove_element_before_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.remove_element_before_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'remove_url_after',
			'label'   => trans('admin.remove_element_after_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.remove_element_after_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'  => 'remove_email_title',
			'type'  => 'custom_html',
			'value' => trans('admin.remove_email_title_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'remove_email_before',
			'label'   => trans('admin.remove_element_before_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.remove_element_before_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'remove_email_after',
			'label'   => trans('admin.remove_element_after_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.remove_element_after_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'  => 'remove_phone_title',
			'type'  => 'custom_html',
			'value' => trans('admin.remove_phone_title_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'remove_phone_before',
			'label'   => trans('admin.remove_element_before_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.remove_element_before_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'remove_phone_after',
			'label'   => trans('admin.remove_element_after_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.remove_element_after_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.guests_options_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'guests_options_title',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'guest_can_submit_listings',
			'label'   => trans('admin.Allow Guests to post Listings'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.guest_can_submit_listings_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'auto_registration',
			'label'   => trans('admin.auto_registration_label'),
			'type'    => 'select2_from_array',
			'options' => [
				0 => trans('admin.auto_registration_option_0'), // 0 => 'disabled'
				1 => trans('admin.auto_registration_option_1'), // 1 => 'enabled_shown'
				2 => trans('admin.auto_registration_option_2'), // 2 => 'enabled_hidden'
			],
			'hint'    => trans('admin.auto_registration_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields, [
			'formTypesSelectorsJson' => $formTypesSelectorsJson,
		]);
	}
}
