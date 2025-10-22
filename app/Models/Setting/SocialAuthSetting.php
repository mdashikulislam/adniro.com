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
 * settings.social_auth.option
 */

use App\Services\Auth\App\Helpers\SocialLogin\SocialLoginButton;

class SocialAuthSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		// Settings from Old versions
		$isSocialAuthEnabled = isOldSocialAuthEnabled(null, $value);
		$isFacebookEnabled = isOldSocialAuthEnabled('facebook', $value);
		$isLinkedInEnabled = isOldSocialAuthEnabled('linkedin', $value);
		$isTwitterOauth2Enabled = isOldSocialAuthEnabled('twitterOauth2', $value);
		$isTwitterOauth1Enabled = isOldSocialAuthEnabled('twitterOauth1', $value);
		$isGoogleEnabled = isOldSocialAuthEnabled('google', $value);
		
		$defaultValue = [
			'social_auth_enabled'     => $isSocialAuthEnabled,
			'button_type'             => SocialLoginButton::LoginWithDefault->value,
			'facebook_enabled'        => $isFacebookEnabled,
			'linkedin_enabled'        => $isLinkedInEnabled,
			'twitter_oauth_2_enabled' => $isTwitterOauth2Enabled,
			'twitter_oauth_1_enabled' => $isTwitterOauth1Enabled,
			'google_enabled'          => $isGoogleEnabled,
		];
		
		return array_merge($defaultValue, $value);
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$baseUrl = config('app.url');
		
		$facebookInfo = trans('admin.facebook_oauth_info', ['baseUrl' => $baseUrl]);
		$linkedinInfo = trans('admin.linkedin_oauth_info', ['baseUrl' => $baseUrl]);
		$twitterOauth2Info = trans('admin.twitter_oauth_2_info', ['baseUrl' => $baseUrl]);
		$twitterOauth1Info = trans('admin.twitter_oauth_1_info', ['baseUrl' => $baseUrl]);
		$googleInfo = trans('admin.google_oauth_info', ['baseUrl' => $baseUrl]);
		
		if (config('plugins.domainmapping.installed')) {
			$facebookInfo .= trans('admin.facebook_oauth_domainmapping');
			$linkedinInfo .= trans('admin.linkedin_oauth_domainmapping');
			$twitterOauth2Info .= trans('admin.twitter_oauth_2_domainmapping');
			$twitterOauth1Info .= trans('admin.twitter_oauth_1_domainmapping');
			$googleInfo .= trans('admin.google_oauth_domainmapping');
		}
		
		$twitterOauth2Info .= trans('admin.twitter_oauth_2_note');
		$twitterOauth1Info .= trans('admin.twitter_oauth_1_note');
		
		$facebookInfo = trans('admin.card_light_inverse', ['content' => $facebookInfo]);
		$linkedinInfo = trans('admin.card_light_inverse', ['content' => $linkedinInfo]);
		$twitterOauth2Info = trans('admin.card_light_inverse', ['content' => $twitterOauth2Info]);
		$twitterOauth1Info = trans('admin.card_light_inverse', ['content' => $twitterOauth1Info]);
		$googleInfo = trans('admin.card_light_inverse', ['content' => $googleInfo]);
		
		$buttonTypes = SocialLoginButton::all();
		$buttonTypes = collect($buttonTypes)
			->mapWithKeys(fn ($item, $key) => [$key => $item['label'] ?? $key])
			->toArray();
		
		$fields = [];
		
		$fields[] = [
			'name'       => 'social_auth_enabled',
			'label'      => trans('admin.social_auth_enabled_label'),
			'type'       => 'checkbox_switch',
			'attributes' => [
				'data-social-network' => 'all',
			],
			'hint'       => trans('admin.social_auth_enabled_hint'),
		];
		
		$fields[] = [
			'name'    => 'generate_password',
			'label'   => trans('admin.social_auth_generate_password_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.social_auth_generate_password_hint', [
				'settingUrl' => urlGen()->adminUrl('settings/find/mail'),
			]),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		$fields[] = [
			'name'    => 'button_type',
			'label'   => trans('admin.button_type_label'),
			'type'    => 'select2_from_array',
			'options' => $buttonTypes,
			'hint'    => trans('admin.button_type_hint'),
			'wrapper' => [
				'class' => 'col-md-6',
			],
		];
		
		// facebook
		$tabName = trans('admin.facebook_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'facebook_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => ['class' => 'col-md-12'],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'       => 'facebook_enabled',
			'label'      => trans('admin.facebook_enabled_label'),
			'type'       => 'checkbox_switch',
			'attributes' => ['data-social-network' => 'facebook'],
			'hint'       => trans('admin.facebook_enabled_hint'),
			'wrapper'    => ['class' => 'col-md-12'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'facebook_oauth_info',
			'type'    => 'custom_html',
			'value'   => $facebookInfo,
			'wrapper' => ['class' => 'col-md-12 facebook'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'facebook_client_id',
			'label'   => trans('admin.facebook_client_id_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 facebook'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'facebook_client_secret',
			'label'   => trans('admin.facebook_client_secret_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 facebook'],
			'tab'     => $tabName,
		];
		
		// linkedin
		$tabName = trans('admin.linkedin_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'linkedin_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => ['class' => 'col-md-12'],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'       => 'linkedin_enabled',
			'label'      => trans('admin.linkedin_enabled_label'),
			'type'       => 'checkbox_switch',
			'attributes' => ['data-social-network' => 'linkedin'],
			'hint'       => trans('admin.linkedin_enabled_hint'),
			'wrapper'    => ['class' => 'col-md-12'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'linkedin_oauth_info',
			'type'    => 'custom_html',
			'value'   => $linkedinInfo,
			'wrapper' => ['class' => 'col-md-12 linkedin',],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'linkedin_client_id',
			'label'   => trans('admin.linkedin_client_id_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 linkedin',],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'linkedin_client_secret',
			'label'   => trans('admin.linkedin_client_secret_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 linkedin'],
			'tab'     => $tabName,
		];
		
		// twitter (OAuth 2.0)
		$tabName = trans('admin.twitter_oauth_2_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'twitter_oauth_2_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => ['class' => 'col-md-12'],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'       => 'twitter_oauth_2_enabled',
			'label'      => trans('admin.twitter_oauth_2_enabled_label'),
			'type'       => 'checkbox_switch',
			'attributes' => ['data-social-network' => 'twitter-oauth-2',],
			'hint'       => trans('admin.twitter_oauth_2_enabled_hint'),
			'wrapper'    => ['class' => 'col-md-12',],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'twitter_oauth_2_info',
			'type'    => 'custom_html',
			'value'   => $twitterOauth2Info,
			'wrapper' => ['class' => 'col-md-12 twitter-oauth-2'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'twitter_oauth_2_client_id',
			'label'   => trans('admin.twitter_oauth_2_client_id_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 twitter-oauth-2'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'twitter_oauth_2_client_secret',
			'label'   => trans('admin.twitter_oauth_2_client_secret_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 twitter-oauth-2',],
			'tab'     => $tabName,
		];
		
		// twitter (OAuth 1.0)
		$tabName = trans('admin.twitter_oauth_1_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'twitter_oauth_1_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => ['class' => 'col-md-12'],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'       => 'twitter_oauth_1_enabled',
			'label'      => trans('admin.twitter_oauth_1_enabled_label'),
			'type'       => 'checkbox_switch',
			'attributes' => ['data-social-network' => 'twitter-oauth-1',],
			'hint'       => trans('admin.twitter_oauth_1_enabled_hint'),
			'wrapper'    => ['class' => 'col-md-12'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'twitter_oauth_1_info',
			'type'    => 'custom_html',
			'value'   => $twitterOauth1Info,
			'wrapper' => ['class' => 'col-md-12 twitter-oauth-1'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'twitter_client_id',
			'label'   => trans('admin.twitter_client_id_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 twitter-oauth-1'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'twitter_client_secret',
			'label'   => trans('admin.twitter_client_secret_label'),
			'type'    => 'text',
			'wrapper' => [
				'class' => 'col-md-12 twitter-oauth-1',
			],
			'tab'     => $tabName,
		];
		
		// google
		$tabName = trans('admin.google_title');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'    => 'google_title',
				'type'    => 'custom_html',
				'value'   => $tabName,
				'wrapper' => ['class' => 'col-md-12'],
				'tab'     => $tabName,
			];
		}
		$fields[] = [
			'name'       => 'google_enabled',
			'label'      => trans('admin.google_enabled_label'),
			'type'       => 'checkbox_switch',
			'attributes' => ['data-social-network' => 'google'],
			'hint'       => trans('admin.google_enabled_hint'),
			'wrapper'    => ['class' => 'col-md-12'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'    => 'google_oauth_info',
			'type'    => 'custom_html',
			'value'   => $googleInfo,
			'wrapper' => ['class' => 'col-md-12 google'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'google_client_id',
			'label'   => trans('admin.google_client_id_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 google'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'google_client_secret',
			'label'   => trans('admin.google_client_secret_label'),
			'type'    => 'text',
			'wrapper' => ['class' => 'col-md-12 google'],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
}
