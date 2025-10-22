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
 * settings.seo.option
 */

class SeoSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'robots_txt'                        => getDefaultRobotsTxtContent(),
			'robots_txt_sm_indexes'             => '1',
			'multi_country_urls'                => config('larapen.core.multiCountryUrls'),
			'listing_hashed_id_enabled'         => '0',
			'listing_hashed_id_seo_redirection' => '1',
		];
		
		return array_merge($defaultValue, $value);
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [];
		
		$tabName = trans('admin.verification_tools_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'verification_tools_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'google_site_verification',
			'label'   => trans('admin.google_site_verification_label'),
			'type'    => 'text',
			'hint'    => trans('admin.seo_site_verification_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'msvalidate',
			'label'   => trans('admin.msvalidate_label'),
			'type'    => 'text',
			'hint'    => trans('admin.seo_site_verification_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'yandex_verification',
			'label'   => trans('admin.yandex_verification_label'),
			'type'    => 'text',
			'hint'    => trans('admin.seo_site_verification_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'twitter_username',
			'label'   => trans('admin.twitter_username_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.robots_txt_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'robots_txt_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'robots_txt_info',
			'type'  => 'custom_html',
			'value' => trans('admin.robots_txt_info_value', ['domain' => url('/')]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'       => 'robots_txt',
			'label'      => trans('admin.robots_txt_label'),
			'type'       => 'textarea',
			'attributes' => ['rows' => '5'],
			'hint'       => trans('admin.robots_txt_hint'),
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'robots_txt_sm_indexes',
			'label'   => trans('admin.robots_txt_sm_indexes_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.robots_txt_sm_indexes_hint', ['indexes' => getSitemapsIndexes(true)]),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		
		$fields[] = [
			'name'  => 'no_index',
			'type'  => 'custom_html',
			'value' => trans('admin.no_index_title'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_categories',
			'label'   => trans('admin.no_index_categories_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_categories_qs',
			'label'   => trans('admin.no_index_categories_qs_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_cities',
			'label'   => trans('admin.no_index_cities_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_cities_qs',
			'label'   => trans('admin.no_index_cities_qs_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_users',
			'label'   => trans('admin.no_index_users_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_users_username',
			'label'   => trans('admin.no_index_users_username_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_tags',
			'label'   => trans('admin.no_index_tags_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_filters_orders',
			'label'   => trans('admin.no_index_filters_orders_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_no_result',
			'label'   => trans('admin.no_index_no_result_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_listing_report',
			'label'   => trans('admin.no_index_listing_report_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'no_index_all',
			'label'   => trans('admin.no_index_all_label'),
			'type'    => 'checkbox_switch',
			'wrapper' => ['class' => 'col-12'],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.listing_id_hashing_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'listing_id_hashing',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'listing_hashed_id_enabled',
			'label'   => trans('admin.listing_hashed_id_enabled_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.listing_hashed_id_enabled_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'listing_hashed_id_seo_redirection',
			'label'   => trans('admin.listing_hashed_id_seo_redirection_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.listing_hashed_id_seo_redirection_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		
		// Get the permalinks patterns list
		$permalinks = self::getPermalinks();
		
		// Get the drivers selectors list as JS objects
		$permalinksJson = collect($permalinks)->toJson();
		
		$tabName = trans('admin.seo_permalink_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'seo_permalink_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'seo_permalink_warning_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.seo_permalink_warning'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'listing_permalink',
			'label'   => trans('admin.listing_permalink_label'),
			'type'    => 'select2_from_array',
			'options' => $permalinks,
			'hint'    => trans('admin.listing_permalink_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'listing_permalink_ext',
			'label'   => trans('admin.permalink_ext_label'),
			'type'    => 'select2_from_array',
			'options' => self::getPermalinkExt(),
			'hint'    => trans('admin.permalink_ext_hint') . '<br>' . trans('admin.listing_permalink_ext_hint'),
			'wrapper' => ['class' => 'col-md-12'],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.seo_html_multi_country_urls');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'separator_4',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'separator_4_1',
			'type'  => 'custom_html',
			'value' => trans('admin.multi_country_urls_optimization_warning'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'multi_country_urls',
			'label' => trans('admin.Enable The Multi-countries URLs'),
			'type'  => 'checkbox_switch',
			'hint'  => trans('admin.multi_country_urls_optimization_hint'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'separator_4_2',
			'type'  => 'custom_html',
			'value' => trans('admin.multi_country_urls_optimization_info'),
			'tab'   => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields, [
			'permalinksJson' => $permalinksJson,
		]);
	}
	
	/**
	 * @return array
	 */
	private static function getPermalinks(): array
	{
		$permalinks = config('larapen.options.permalink.post');
		
		return collect($permalinks)
			->mapWithKeys(fn ($item) => [$item => $item])
			->toArray();
	}
	
	/**
	 * @return array
	 */
	private static function getPermalinkExt(): array
	{
		$extensions = config('larapen.options.permalinkExt');
		
		return collect($extensions)
			->mapWithKeys(fn ($item) => [$item => !empty($item) ? $item : '&nbsp;'])
			->toArray();
	}
}
