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

/*
 * settings.social_share.option
 */

class SocialShareSetting extends BaseSetting
{
	public static function passedValidation($request)
	{
		$params = [
			[
				'attribute' => 'og_image',
				'destPath'  => 'app/logo',
				'width'     => (int)config('larapen.media.resize.namedOptions.bg-header.width', 2000),
				'height'    => (int)config('larapen.media.resize.namedOptions.bg-header.height', 1000),
				'ratio'     => config('larapen.media.resize.namedOptions.bg-header.ratio', '1'),
				'upsize'    => config('larapen.media.resize.namedOptions.bg-header.upsize', '0'),
				'filename'  => 'og-',
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
	
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'facebook'        => '1',
			'twitter'         => '1',
			'linkedin'        => '1',
			'whatsapp'        => '1',
			'telegram'        => '1',
			'snapchat'        => '0',
			'messenger'       => '0',
			'pinterest'       => '0',
			'vk'              => '0',
			'tumblr'          => '0',
			'og_image'        => null,
			'og_image_width'  => config('settings.seo.og_image_width', '1200'),
			'og_image_height' => config('settings.seo.og_image_height', '630'),
		];
		
		$value = array_merge($defaultValue, $value);
		
		// Append files URLs
		// og_image_url
		$ogImage = $value['og_image'] ?? config('settings.seo.og_image');
		$ogImageWidth = !empty($value['og_image_width']) ? (int)$value['og_image_width'] : 0;
		$ogImageHeight = !empty($value['og_image_height']) ? (int)$value['og_image_height'] : 0;
		$resizeOptionsName = ($ogImageWidth > 0 && $ogImageHeight > 0) ? ($ogImageWidth . 'x' . $ogImageHeight) : 'bg-header';
		$value['og_image_url'] = thumbService($ogImage, false)->resize($resizeOptionsName)->url();
		
		return $value;
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [];
		
		$fields[] = [
			'name'  => 'social_share_title',
			'type'  => 'custom_html',
			'value' => trans('admin.social_share_title'),
		];
		$fields[] = [
			'name'  => 'social_share_info',
			'type'  => 'custom_html',
			'value' => trans('admin.social_share_info'),
		];
		
		$fields[] = [
			'name'    => 'facebook',
			'label'   => 'Facebook',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.facebook_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'twitter',
			'label'   => 'X (Twitter)',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.twitter_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'linkedin',
			'label'   => 'LinkedIn',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.linkedin_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'whatsapp',
			'label'   => 'WhatsApp',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.whatsapp_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'telegram',
			'label'   => 'Telegram',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.telegram_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'snapchat',
			'label'   => 'Snapchat',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.snapchat_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'messenger',
			'label'   => 'Facebook Messenger',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.messenger_share_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'facebook_app_id',
			'label'   => trans('admin.facebook_app_id'),
			'type'    => 'text',
			'hint'    => trans('admin.facebook_app_id_hint'),
			'wrapper' => [
				'class' => 'col-md-6 messenger',
			],
		];
		
		$fields[] = [
			'name'    => 'pinterest',
			'label'   => 'Pinterest',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.pinterest_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'vk',
			'label'   => 'VK (VKontakte)',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.vk_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'    => 'tumblr',
			'label'   => 'Tumblr',
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.tumblr_share_hint'),
			'wrapper' => [
				'class' => 'col-md-12',
			],
		];
		
		$fields[] = [
			'name'  => 'og_image_title',
			'type'  => 'custom_html',
			'value' => trans('admin.og_image_title'),
		];
		$fields[] = [
			'name'  => 'og_image_info',
			'type'  => 'custom_html',
			'value' => trans('admin.og_image_info'),
		];
		$fields[] = [
			'name'   => 'og_image',
			'label'  => trans('admin.og_image_label'),
			'type'   => 'image',
			'upload' => true,
			'disk'   => $diskName,
			'hint'   => trans('admin.og_image_hint'),
		];
		$fields[] = [
			'name'       => 'og_image_width',
			'label'      => trans('admin.width_label') . ' (' . trans('admin.og_image_label') . ')',
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '1200',
			],
			'hint'       => trans('admin.width_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'       => 'og_image_height',
			'label'      => trans('admin.height_label') . ' (' . trans('admin.og_image_label') . ')',
			'type'       => 'number',
			'attributes' => [
				'placeholder' => '630',
			],
			'hint'       => trans('admin.height_hint'),
			'wrapper'    => [
				'class' => 'col-md-6',
			],
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
}
