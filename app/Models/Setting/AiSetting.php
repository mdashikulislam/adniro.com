<?php

namespace App\Models\Setting;

use App\Models\AiModel;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Schema;
use Throwable;

/*
 * settings.ai.option (adniro:ai)
 *
 * Providers, keys and models are managed in Admin > AI Providers and Admin > AI Models.
 * This screen turns AI on or off, assigns a model to each feature and sets the daily budget.
 *
 * The AI client reads these values fresh from the database on every call
 * (see App\Services\Ai\AiSettings), so queue workers never use stale values.
 */

class AiSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'enabled'                      => '0',
			'moderation_model_id'          => null,
			'moderation_fallback_model_id' => null,
			'listing_assistant_model_id'           => null,
			'listing_assistant_fallback_model_id'  => null,
			'transcription_model_id'       => null,
			'daily_budget_usd'             => '5',
			'daily_call_limit'             => '500',
			'moderation_mode'              => 'off',
			'moderation_user_daily_limit'  => '30',
			'moderation_post_daily_limit'  => '10',
			'moderation_when_unavailable'  => 'approve',
			'moderation_alert_admins'      => '1',
			'listing_assistant_enabled'            => '0',
			'listing_assistant_guests'             => '0',
			'listing_assistant_member_limit'       => '20',
			'listing_assistant_member_period'      => 'day',
			'listing_assistant_guest_limit'        => '5',
			'listing_assistant_guest_period'       => 'day',
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
		
		// General
		$tabName = trans('ai.setting_tab_general');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'ai_general_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'ai_general_info',
			'type'  => 'custom_html',
			'value' => self::generalInfoHtml(),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'enabled',
			'label'   => trans('ai.setting_enabled_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('ai.setting_enabled_hint'),
			'wrapper' => ['class' => 'col-md-12 mt-3'],
			'tab'     => $tabName,
		];
		
		// Models
		$tabName = trans('ai.setting_tab_models');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'ai_models_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'ai_models_info',
			'type'  => 'custom_html',
			'value' => '<div class="alert alert-light">' . e(trans('ai.setting_models_info')) . '</div>',
			'tab'   => $tabName,
		];
		
		$visionOptions = self::modelOptions('moderation');
		$transcriptionOptions = self::modelOptions('transcription');
		
		foreach ([
			'moderation' => 'moderation',
			'listing_assistant' => 'listing_assistant',
		] as $prefix => $feature) {
			$fields[] = [
				'name'        => $prefix . '_model_id',
				'label'       => trans('ai.setting_' . $prefix . '_model_label'),
				'type'        => 'select2_from_array',
				'options'     => $visionOptions,
				'allows_null' => true,
				'hint'        => trans('ai.setting_' . $prefix . '_model_hint'),
				'wrapper'     => ['class' => 'col-md-6'],
				'tab'         => $tabName,
			];
			$fields[] = [
				'name'        => $prefix . '_fallback_model_id',
				'label'       => trans('ai.setting_fallback_model_label'),
				'type'        => 'select2_from_array',
				'options'     => $visionOptions,
				'allows_null' => true,
				'hint'        => trans('ai.setting_fallback_model_hint'),
				'wrapper'     => ['class' => 'col-md-6'],
				'tab'         => $tabName,
			];
		}
		$fields[] = [
			'name'        => 'transcription_model_id',
			'label'       => trans('ai.setting_transcription_model_label'),
			'type'        => 'select2_from_array',
			'options'     => $transcriptionOptions,
			'allows_null' => true,
			'hint'        => trans('ai.setting_transcription_model_hint'),
			'wrapper'     => ['class' => 'col-md-6'],
			'tab'         => $tabName,
		];
		
		// Moderation
		$tabName = trans('ai.setting_tab_moderation');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'ai_moderation_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'ai_moderation_info',
			'type'  => 'custom_html',
			'value' => '<div class="alert alert-light">' . e(trans('ai.setting_moderation_info')) . '</div>' . self::moderationModeHtml(),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'moderation_mode',
			'label'   => trans('ai.setting_moderation_mode_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'off'    => trans('ai.setting_moderation_mode_off'),
				'shadow' => trans('ai.setting_moderation_mode_shadow'),
				'enforce' => trans('ai.setting_moderation_mode_enforce'),
			],
			'hint'    => trans('ai.setting_moderation_mode_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'moderation_when_unavailable',
			'label'   => trans('ai.setting_moderation_unavailable_label'),
			'type'    => 'select2_from_array',
			'options' => [
				'approve' => trans('ai.setting_moderation_unavailable_approve'),
				'review'  => trans('ai.setting_moderation_unavailable_review'),
			],
			'hint'    => trans('ai.setting_moderation_unavailable_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'moderation_alert_admins',
			'label'   => trans('ai.setting_moderation_alert_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('ai.setting_moderation_alert_hint'),
			'wrapper' => ['class' => 'col-md-6 mt-4'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'       => 'moderation_user_daily_limit',
			'label'      => trans('ai.setting_moderation_user_limit_label'),
			'type'       => 'number',
			'attributes' => ['min' => 0, 'step' => 1],
			'hint'       => trans('ai.setting_moderation_user_limit_hint'),
			'wrapper'    => ['class' => 'col-md-6'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'moderation_post_daily_limit',
			'label'      => trans('ai.setting_moderation_post_limit_label'),
			'type'       => 'number',
			'attributes' => ['min' => 0, 'step' => 1],
			'hint'       => trans('ai.setting_moderation_post_limit_hint'),
			'wrapper'    => ['class' => 'col-md-6'],
			'tab'        => $tabName,
		];
		
		// AI listing assistant (the AI box in the listing form)
		$tabName = trans('ai.setting_tab_listing_assistant');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'ai_listing_assistant_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'ai_listing_assistant_info',
			'type'  => 'custom_html',
			'value' => '<div class="alert alert-light">' . e(trans('ai.setting_listing_assistant_info')) . '</div>',
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'listing_assistant_enabled',
			'label'   => trans('ai.setting_listing_assistant_enabled_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('ai.setting_listing_assistant_enabled_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'listing_assistant_guests',
			'label'   => trans('ai.setting_listing_assistant_guests_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('ai.setting_listing_assistant_guests_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$periodOptions = [
			'day'   => trans('ai.period_day'),
			'week'  => trans('ai.period_week'),
			'month' => trans('ai.period_month'),
			'none'  => trans('ai.period_none'),
		];
		foreach (['member', 'guest'] as $who) {
			$fields[] = [
				'name'       => 'listing_assistant_' . $who . '_limit',
				'label'      => trans('ai.setting_listing_assistant_' . $who . '_limit_label'),
				'type'       => 'number',
				'attributes' => ['min' => 0, 'step' => 1],
				'hint'       => trans('ai.setting_listing_assistant_limit_hint'),
				'wrapper'    => ['class' => 'col-md-3'],
				'tab'        => $tabName,
			];
			$fields[] = [
				'name'    => 'listing_assistant_' . $who . '_period',
				'label'   => trans('ai.setting_listing_assistant_period_label'),
				'type'    => 'select2_from_array',
				'options' => $periodOptions,
				'hint'    => trans('ai.setting_listing_assistant_period_hint'),
				'wrapper' => ['class' => 'col-md-3'],
				'tab'     => $tabName,
			];
		}
		
		// Budget
		$tabName = trans('ai.setting_tab_budget');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'ai_budget_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'       => 'daily_budget_usd',
			'label'      => trans('ai.setting_daily_budget_label'),
			'type'       => 'number',
			'attributes' => ['min' => 0, 'step' => '0.01'],
			'hint'       => trans('ai.setting_daily_budget_hint'),
			'wrapper'    => ['class' => 'col-md-6'],
			'tab'        => $tabName,
		];
		$fields[] = [
			'name'       => 'daily_call_limit',
			'label'      => trans('ai.setting_daily_call_limit_label'),
			'type'       => 'number',
			'attributes' => ['min' => 0, 'step' => 1],
			'hint'       => trans('ai.setting_daily_call_limit_hint'),
			'wrapper'    => ['class' => 'col-md-6'],
			'tab'        => $tabName,
		];
		
		return $fields;
	}
	
	/**
	 * Models that can be assigned to the given feature: active, of an active provider,
	 * with the right capabilities, and (for structured features) a passed "Test model".
	 */
	private static function modelOptions(string $feature): array
	{
		try {
			if (!Schema::hasTable('ai_models')) {
				return [];
			}
			
			return AiModel::query()
				->with('provider')
				->where('active', 1)
				->orderBy('ai_provider_id')
				->orderBy('model_id')
				->get()
				->filter(fn (AiModel $model) => $model->isEligibleFor($feature))
				->mapWithKeys(fn (AiModel $model) => [(string)$model->id => $model->fullName()])
				->toArray();
		} catch (Throwable $e) {
			return [];
		}
	}
	
	/**
	 * Says which mode is really in effect: enforce needs models that passed the evaluation.
	 */
	private static function moderationModeHtml(): string
	{
		try {
			$settings = app(\App\Services\Ai\AiSettings::class);
			if ($settings->moderationModeSetting() !== 'enforce' || $settings->moderationModelsPassedEval()) {
				return '';
			}
			
			return '<div class="alert alert-warning">' . e(trans('ai.setting_moderation_enforce_blocked')) . '</div>';
		} catch (Throwable $e) {
			return '';
		}
	}
	
	private static function generalInfoHtml(): string
	{
		$providersUrl = urlGen()->adminUrl('ai_providers');
		$modelsUrl = urlGen()->adminUrl('ai_models');
		
		$usage = '';
		try {
			if (Schema::hasTable('ai_usage_logs')) {
				$today = AiUsageLog::query()->where('created_at', '>=', now()->startOfDay());
				$calls = (clone $today)->count();
				$cost = (float)(clone $today)->sum('cost_usd');
				$usage = '<p class="mb-0">' . e(trans('ai.setting_usage_today', [
						'calls' => $calls,
						'cost'  => number_format($cost, 4),
					])) . '</p>';
			}
		} catch (Throwable $e) {
		}
		
		return '<div class="alert alert-light">'
			. '<p>' . e(trans('ai.setting_general_info')) . '</p>'
			. '<p>'
			. '<a class="btn btn-sm btn-primary" href="' . e($providersUrl) . '">' . e(trans('ai.providers')) . '</a> '
			. '<a class="btn btn-sm btn-primary" href="' . e($modelsUrl) . '">' . e(trans('ai.models')) . '</a>'
			. '</p>'
			. $usage
			. '</div>';
	}
}
