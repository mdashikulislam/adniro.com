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
 * settings.social_link.option
 */

class SocialLinkSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'facebook_page_url' => '#',
			'twitter_url'       => '#',
			'linkedin_url'      => '#',
			'pinterest_url'     => '#',
			'instagram_url'     => '#',
			'tiktok_url'        => '#',
			'youtube_url'       => '#',
			'vimeo_url'         => '#',
			'vk_url'            => '#',
			'tumblr_url'        => '',
			'flickr_url'        => '',
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
		
		$fields[] = [
			'name'  => 'social_link_info',
			'type'  => 'custom_html',
			'value' => trans('admin.social_link_info'),
		];
		$fields[] = [
			'name'  => 'facebook_page_url',
			'label' => trans('admin.facebook_page_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'twitter_url',
			'label' => trans('admin.twitter_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'linkedin_url',
			'label' => trans('admin.linkedin_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'pinterest_url',
			'label' => trans('admin.pinterest_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'instagram_url',
			'label' => trans('admin.instagram_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'tiktok_url',
			'label' => trans('admin.tiktok_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'youtube_url',
			'label' => trans('admin.youtube_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'vimeo_url',
			'label' => trans('admin.vimeo_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'vk_url',
			'label' => trans('admin.vk_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'tumblr_url',
			'label' => trans('admin.tumblr_url'),
			'type'  => 'text',
		];
		$fields[] = [
			'name'  => 'flickr_url',
			'label' => trans('admin.flickr_url'),
			'type'  => 'text',
		];
		
		return $fields;
	}
}
