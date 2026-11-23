<?php

namespace App\Models\Setting;

use App\Services\Chat\ChatSettings;

/*
 * settings.chat.option (adniro): Admin > Settings > Chat. The live chat on or off, photos, offers, the unread-messages
 * email, the send limits and how long a message can be taken back.
 * Values are read through App\Services\Chat\ChatSettings, which keeps each one inside a safe range.
 */

class ChatSetting extends BaseSetting
{
	public static function getFieldValues($value, $disk)
	{
		return array_merge(ChatSettings::DEFAULTS, is_array($value) ? $value : []);
	}
	
	public static function setFieldValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName): array
	{
		$fields = [];
		
		foreach (['enabled', 'photos_enabled', 'offers_enabled'] as $name) {
			$fields[] = self::switchField($name, 'col-md-12 mt-3');
		}
		$fields[] = self::numberField('messages_per_minute', 1);
		$fields[] = self::numberField('messages_per_day', 10);
		$fields[] = self::numberField('unsend_minutes', 0);
		$fields[] = self::switchField('unread_email_enabled', 'col-md-12 mt-3');
		$fields[] = self::numberField('unread_email_delay_minutes', 1);
		
		return $fields;
	}
	
	private static function switchField(string $name, string $wrapperClass): array
	{
		return [
			'name'    => $name,
			'label'   => trans('chat_admin.setting_' . $name . '_label'),
			'type'    => 'checkbox_switch',
			'hint'    => trans('chat_admin.setting_' . $name . '_hint'),
			'wrapper' => ['class' => $wrapperClass],
		];
	}
	
	private static function numberField(string $name, int $min): array
	{
		return [
			'name'       => $name,
			'label'      => trans('chat_admin.setting_' . $name . '_label'),
			'type'       => 'number',
			'attributes' => ['min' => $min, 'step' => 1],
			'hint'       => trans('chat_admin.setting_' . $name . '_hint'),
			'wrapper'    => ['class' => 'col-md-6'],
		];
	}
}
