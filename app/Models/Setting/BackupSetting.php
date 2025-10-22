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

use App\Helpers\Common\Date\TimeZoneManager;

/*
 * settings.backup.option
 */

class BackupSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		$value = is_array($value) ? $value : [];
		
		$defaultValue = [
			'disable_notifications'           => '1',
			'keep_all_backups_for_days'       => '7',
			'keep_daily_backups_for_days'     => '16',
			'keep_weekly_backups_for_weeks'   => '8',
			'keep_monthly_backups_for_months' => '4',
			'keep_yearly_backups_for_years'   => '2',
			'maximum_storage_in_megabytes'    => '5000',
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
		
		$tabName = trans('admin.backups_list_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'backups_list',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'backup_link_btn_hint',
			'type'  => 'custom_html',
			'value' => trans('admin.backup_link_btn_hint_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'backup_link_btn',
			'type'  => 'custom_html',
			'value' => trans('admin.backup_link_btn_value'),
			'tab'   => $tabName,
		];
		
		$tabName = trans('admin.backup_storage_disk_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'backup_storage_disk',
				'type'  => 'custom_html',
				'value' => trans('admin.backup_storage_disk_value'),
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'    => 'storage_disk',
			'label'   => trans('admin.storage_disk_label'),
			'type'    => 'select2_from_array',
			'options' => [
				0 => trans('admin.storage_disk_option_0'),
				1 => trans('admin.storage_disk_option_1'),
				2 => trans('admin.storage_disk_option_2'),
			],
			'hint'    => trans('admin.storage_disk_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'disable_notifications',
			'label'   => trans('admin.backup_disable_notifications_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('admin.backup_disable_notifications_hint', ['email' => config('settings.app.email')]),
			'wrapper' => ['class' => 'col-md-6 mt-3'],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.backup_schedule_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'backup_schedule',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'help_backup_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.card_body', [
				'text'    => trans('admin.help_backup', [
					'backupLocalStorage' => relativeAppPath(storage_path('backups')),
				]),
				'bgColor' => 'bg-light-warning',
			]),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'backup_sep_2',
			'type'  => 'custom_html',
			'value' => '<hr>',
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'  => 'cron_info_sep',
			'type'  => 'custom_html',
			'value' => trans('admin.cron_info_sep_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'taking_backup',
			'label'   => trans('admin.taking_backup_label'),
			'type'    => 'select2_from_array',
			'options' => self::backupFrequencies(),
			'hint'    => trans('admin.taking_backup_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'taking_backup_at',
			'label'   => trans('admin.taking_backup_at_label'),
			'type'    => 'select2_from_array',
			'options' => self::backupFrequencyAt(),
			'hint'    => trans('admin.taking_backup_at_hint', ['timeZone' => TimeZoneManager::getContextualTimeZone()]),
			'wrapper' => ['class' => 'col-md-6 taking-backup-field'],
			'tab'     => $tabName,
		];
		
		$tabName = trans('admin.backup_cleanup_sep_value');
		if (self::getPanelTabsType() == 'vertical') {
			$fields[] = [
				'name'  => 'backup_cleanup_sep',
				'type'  => 'custom_html',
				'value' => $tabName,
				'tab'   => $tabName,
			];
		}
		$fields[] = [
			'name'  => 'backup_cleanup_rules',
			'type'  => 'custom_html',
			'value' => trans('admin.backup_cleanup_rules_value'),
			'tab'   => $tabName,
		];
		$fields[] = [
			'name'    => 'keep_all_backups_for_days',
			'label'   => trans('admin.keep_all_backups_for_days_label'),
			'type'    => 'number',
			'hint'    => trans('admin.keep_all_backups_for_days_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'keep_daily_backups_for_days',
			'label'   => trans('admin.keep_daily_backups_for_days_label'),
			'type'    => 'number',
			'hint'    => trans('admin.keep_daily_backups_for_days_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'keep_weekly_backups_for_weeks',
			'label'   => trans('admin.keep_weekly_backups_for_weeks_label'),
			'type'    => 'number',
			'hint'    => trans('admin.keep_weekly_backups_for_weeks_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'keep_monthly_backups_for_months',
			'label'   => trans('admin.keep_monthly_backups_for_months_label'),
			'type'    => 'number',
			'hint'    => trans('admin.keep_monthly_backups_for_months_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'keep_yearly_backups_for_years',
			'label'   => trans('admin.keep_yearly_backups_for_years_label'),
			'type'    => 'number',
			'hint'    => trans('admin.keep_yearly_backups_for_years_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		$fields[] = [
			'name'    => 'maximum_storage_in_megabytes',
			'label'   => trans('admin.maximum_storage_in_megabytes_label'),
			'type'    => 'number',
			'hint'    => trans('admin.maximum_storage_in_megabytes_hint'),
			'wrapper' => ['class' => 'col-md-6'],
			'tab'     => $tabName,
		];
		
		return addOptionsGroupJavaScript(__NAMESPACE__, __CLASS__, $fields);
	}
	
	/**
	 * @return array
	 */
	private static function backupFrequencies(): array
	{
		return [
			'none'    => trans('admin.taking_backup_option_0'),
			'daily'   => trans('admin.taking_backup_option_1'),
			'weekly'  => trans('admin.taking_backup_option_2'),
			'monthly' => trans('admin.taking_backup_option_3'),
			'yearly'  => trans('admin.taking_backup_option_4'),
		];
	}
	
	/**
	 * @return array
	 */
	private static function backupFrequencyAt(): array
	{
		$hours = [];
		
		for ($i = 0; $i <= 23; $i++) {
			$hh = str_pad($i, 2, '0', STR_PAD_LEFT);
			for ($j = 0; $j <= 59; $j += 15) {
				$mm = str_pad($j, 2, '0', STR_PAD_LEFT);
				$hour = $hh . ':' . $mm;
				$hours[$hour] = $hour;
			}
		}
		
		return $hours;
	}
}
