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

namespace App\Providers\AppService;

use App\Models\Language;
use App\Models\Setting;
use App\Providers\AppService\ConfigTrait\BackupConfig;
use App\Providers\AppService\ConfigTrait\LocalizationConfig;
use App\Providers\AppService\ConfigTrait\MailConfig;
use App\Providers\AppService\ConfigTrait\OptimizationConfig;
use App\Providers\AppService\ConfigTrait\SecurityConfig;
use App\Providers\AppService\ConfigTrait\SkinConfig;
use App\Providers\AppService\ConfigTrait\SmsConfig;
use App\Providers\AppService\ConfigTrait\SocialAuthConfig;
use Throwable;

trait ConfigTrait
{
	use SettingsToConfigTrait;
	use BackupConfig, LocalizationConfig, MailConfig, OptimizationConfig, SecurityConfig, SkinConfig, SmsConfig, SocialAuthConfig;
	
	/**
	 * Setup Configs
	 *
	 * @return void
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	protected function setupConfigs(): void
	{
		// Create Configs for Default Language
		$this->createDefaultLanguageConfig();
		
		// Create Configs for DB Settings
		$this->createSettingsConfig();
		
		// Updating...
		
		// Global (including cache ttl value)
		$this->updateGlobalConfigs();
		
		// Optimization: Cache
		$this->updateOptimizationConfig(config('settings.optimization'));
		
		// Localization
		$this->updateLocalizationConfig(config('settings.localization'));
		
		// Skin
		$this->updateSkinConfig(config('settings.style'));
		
		// Mail
		$this->updateMailConfig(config('settings.mail'));
		
		// SMS
		$this->updateSmsConfig(config('settings.sms'));
		
		// Security
		$this->updateSecurityConfig(config('settings.security'));
		
		// Social Auth
		$this->updateSocialAuthConfig(config('settings.social_auth'));
		
		// Backup
		$this->updateBackupConfig(config('settings.backup'));
	}
	
	/**
	 * Create Configs for Default Language
	 */
	private function createDefaultLanguageConfig(): void
	{
		/*
		 * IMPORTANT
		 * The system master/default locale (APP_LOCALE) is set in the /.env
		 * By changing the default app's language (including from the Admin Panel),
		 * the APP_LOCALE variable is updated with the language code that is selected as default language from the Admin Panel.
		 *
		 * Calling app()->getLocale() or config('app.locale') in the app (including from the Admin Panel)
		 * means usage of the APP_LOCALE variable from /.env files,
		 * since that is retrieved in by config('app.locale') from the 'config/app.php' file.
		 */
		
		try {
			// Cache Parameters
			$cacheParams = [
				'action'  => 'get.language',
				'default' => true,
			];
			
			// Get the DB default language
			$defaultLang = caching()->remember(Language::class, $cacheParams, function () {
				return Language::where('default', 1)->first();
			}, $this->systemCacheTtl);
			
			if (!empty($defaultLang)) {
				// Create DB default language settings
				config()->set('appLang', $defaultLang->toArray());
			} else {
				config()->set('appLang.code', config('app.locale'));
			}
		} catch (Throwable $e) {
			config()->set('appLang.code', config('app.locale'));
		}
	}
	
	/**
	 * Create Configs for DB Settings
	 */
	private function createSettingsConfig(): void
	{
		// Get some default values
		config()->set('settings.app.purchase_code', config('larapen.core.purchaseCode'));
		
		// Check DB connection and catch it
		try {
			
			// Cache Parameters
			$cacheParams = [
				'action' => 'get.settings',
				'active' => true,
			];
			
			// Get all settings from the database (Need to be cached to avoid performance issues)
			$settings = caching()->remember(Setting::class, $cacheParams, function () {
				return Setting::active()->get();
			}, $this->systemCacheTtl);
			
			// Bind all settings to the Laravel config
			$this->loadDatabaseSettingsToConfig($settings);
			
		} catch (Throwable $e) {
			config()->set('settings.error', true);
			config()->set('settings.message', getExceptionMessage($e));
			config()->set('settings.app.logo', config('larapen.media.logo'));
		}
	}
	
	/**
	 * Update Global Configs
	 */
	private function updateGlobalConfigs(): void
	{
		// Image Intervention
		if (isExifExtensionEnabled()) {
			config()->set('image.options.autoOrientation', true);
		}
		
		// App
		if (!empty(config('settings.app.app_name'))) {
			config()->set('settings.app.name', config('settings.app.app_name'));
		}
		config()->set('app.name', config('settings.app.name'));
		if (config('settings.app.php_specific_date_format')) {
			config()->set('larapen.core.dateFormat.default', config('larapen.core.dateFormat.php'));
			config()->set('larapen.core.datetimeFormat.default', config('larapen.core.datetimeFormat.php'));
		}
		
		// Google Maps Platform
		$mapsJavascriptApiKey = config('settings.other.google_maps_javascript_api_key');
		$mapsJavascriptApiKey = env('GOOGLE_MAPS_JAVASCRIPT_API_KEY', $mapsJavascriptApiKey);
		config()->set('services.google_maps_platform.maps_javascript_api_key', $mapsJavascriptApiKey);
		
		$mapsEmbedApiKey = config('settings.other.google_maps_embed_api_key');
		$mapsEmbedApiKey = env('GOOGLE_MAPS_EMBED_API_KEY', $mapsEmbedApiKey);
		$mapsEmbedApiKey ??= $mapsJavascriptApiKey;
		config()->set('services.google_maps_platform.maps_embed_api_key', $mapsEmbedApiKey);
		
		$geocodingApiKey = config('settings.other.google_geocoding_api_key');
		$geocodingApiKey = env('GOOGLE_GEOCODING_API_KEY', $geocodingApiKey);
		$geocodingApiKey ??= $mapsJavascriptApiKey;
		config()->set('services.google_maps_platform.geocoding_api_key', $geocodingApiKey);
		
		// Meta-tags
		config()->set('meta-tags.title', config('settings.app.slogan'));
		config()->set('meta-tags.open_graph.site_name', config('settings.app.name'));
		config()->set('meta-tags.twitter.creator', config('settings.seo.twitter_username'));
		config()->set('meta-tags.twitter.site', config('settings.seo.twitter_username'));
		
		// Cookie Consent
		$cookieConsentEnabled = config('settings.other.cookie_consent_enabled');
		$cookieConsentEnabled = env('COOKIE_CONSENT_ENABLED', $cookieConsentEnabled);
		config()->set('cookie-consent.enabled', $cookieConsentEnabled);
		
		// Admin panel
		$showPoweredBy = config('settings.footer.show_powered_by', '');
		$showPoweredBy = str_contains($showPoweredBy, 'fa')
			? (str_contains($showPoweredBy, 'fa-check-square-o') ? 1 : 0)
			: $showPoweredBy;
		config()->set('larapen.admin.show_powered_by', $showPoweredBy);
		config()->set('larapen.admin.skin', config('settings.style.admin_skin'));
		
		// Impersonate
		config()->set('laravel-impersonate.take_redirect_to', urlGen()->accountOverview());
		config()->set('laravel-impersonate.leave_redirect_to', urlGen()->adminUrl());
		
		// Is Guest can submit listings or contact Authors?
		$guestCanSubmitListings = env('GUEST_CAN_SUBMIT_LISTINGS');
		if ($guestCanSubmitListings !== null) {
			config()->set('settings.listing_form.guest_can_submit_listings', $guestCanSubmitListings);
		}
		$guestCanContactAuthors = env('GUEST_CAN_CONTACT_AUTHORS');
		if ($guestCanContactAuthors !== null) {
			config()->set('settings.listing_page.guest_can_contact_authors', $guestCanContactAuthors);
		}
	}
}
