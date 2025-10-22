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
 * settings.pagination.option
 */

class PaginationSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'per_page'                   => 10,
			'categories_per_page'        => 12,
			'cities_per_page'            => 40,
			'payments_per_page'          => 10,
			'posts_per_page'             => 12,
			'saved_posts_per_page'       => 10,
			'saved_search_per_page'      => 20,
			'subadmin1_per_page'         => 39,
			'subadmin2_per_page'         => 38,
			'subscriptions_per_page'     => 10,
			'threads_per_page'           => 20,
			'threads_messages_per_page'  => 10,
			'similar_posts_limit'        => 4,
			'categories_limit'           => 50,
			'cities_limit'               => 50,
			'auto_complete_cities_limit' => 25,
			'subadmin1_select_limit'     => 200,
			'subadmin2_select_limit'     => 5000,
			'cities_select_limit'        => 25,
			'reviews_per_page'           => plugin_exists('reviews') ? 10 : null,
		];
		
		return array_merge($defaultValue, $value);
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$singleUrl = urlGen()->adminUrl('settings/find/single');
		
		$fields = [];
		
		$fields[] = [
			'name'  => 'per_page_info',
			'type'  => 'custom_html',
			'value' => trans('admin.per_page_info', ['url' => urlGen()->adminUrl('settings/reset/pagination')]),
		];
		$fields[] = [
			'name'  => 'per_page_title',
			'type'  => 'custom_html',
			'value' => trans('admin.per_page_title'),
		];
		$fields[] = [
			'name'       => 'per_page',
			'label'      => trans('admin.per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
			'newline'    => true,
		];
		
		$fields[] = [
			'name'       => 'categories_per_page',
			'label'      => trans('admin.categories_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'cities_per_page',
			'label'      => trans('admin.cities_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'posts_per_page',
			'label'      => trans('admin.posts_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage('posts'),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'payments_per_page',
			'label'      => trans('admin.payments_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'saved_posts_per_page',
			'label'      => trans('admin.saved_posts_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'saved_search_per_page',
			'label'      => trans('admin.saved_search_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'subadmin1_per_page',
			'label'      => trans('admin.subadmin1_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'subadmin2_per_page',
			'label'      => trans('admin.subadmin2_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'subscriptions_per_page',
			'label'      => trans('admin.subscriptions_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'threads_per_page',
			'label'      => trans('admin.threads_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'threads_messages_per_page',
			'label'      => trans('admin.threads_messages_per_page_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_per_page_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		if (plugin_exists('reviews')) {
			$fields[] = [
				'name'       => 'reviews_per_page',
				'label'      => trans('reviews::messages.reviews_per_page_label'),
				'type'       => 'number',
				'attributes' => [
					'min'  => 1,
					'max'  => getMaxItemsPerPage(),
					'step' => 1,
				],
				'hint'       => trans('admin.specific_per_page_hint'),
				'wrapper'    => [
					'class' => 'col-md-6',
				],
			];
		}
		
		$fields[] = [
			'name'  => 'pagination_limit_title',
			'type'  => 'custom_html',
			'value' => trans('admin.pagination_limit_title'),
		];
		$fields[] = [
			'name'  => 'pagination_limit_info',
			'type'  => 'custom_html',
			'value' => trans('admin.pagination_limit_info', [
				'sectionsUrl'     => urlGen()->adminUrl('homepage/sections'),
				'citiesUrl'       => urlGen()->adminUrl('sections/find/locations'),
				'categoriesUrl'   => urlGen()->adminUrl('sections/find/categories'),
				'postsUrl'        => urlGen()->adminUrl('sections/find/latest_listings'),
				'premiumPostsUrl' => urlGen()->adminUrl('sections/find/premium_listings'),
				'companiesUrl'    => urlGen()->adminUrl('sections/find/companies'),
			]),
		];
		$fields[] = [
			'name'       => 'similar_posts_limit',
			'label'      => trans('admin.similar_posts_limit_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage('posts'),
				'step' => 1,
			],
			'hint'       => trans('admin.similar_posts_limit_hint', ['url' => $singleUrl]),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'categories_limit',
			'label'      => trans('admin.categories_limit_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.categories_limit_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'cities_limit',
			'label'      => trans('admin.cities_limit_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.cities_limit_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'auto_complete_cities_limit',
			'label'      => trans('admin.auto_complete_cities_limit_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.auto_complete_cities_limit_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		$fields[] = [
			'name'  => 'pagination_limit_location_title',
			'type'  => 'custom_html',
			'value' => trans('admin.pagination_limit_location_title'),
		];
		$fields[] = [
			'name'  => 'pagination_limit_location_info',
			'type'  => 'custom_html',
			'value' => trans('admin.pagination_limit_location_info', ['url' => $singleUrl]),
		];
		$fields[] = [
			'name'       => 'subadmin1_select_limit',
			'label'      => trans('admin.subadmin1_select_limit_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage('subadmin1_select'),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_limit_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'subadmin2_select_limit',
			'label'      => trans('admin.subadmin2_select_limit_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage('subadmin2_select'),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_limit_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'cities_select_limit',
			'label'      => trans('admin.cities_select_limit_label'),
			'type'       => 'number',
			'attributes' => [
				'min'  => 1,
				'max'  => getMaxItemsPerPage(),
				'step' => 1,
			],
			'hint'       => trans('admin.specific_limit_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		return $fields;
	}
}
