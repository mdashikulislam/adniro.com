<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The account menu's "Messenger" entry opens the live chat and is called "Chats" (adniro), in the languages
 * the chat is translated into. Other languages keep their label. Written with the query builder, so no model
 * observer runs.
 */
return new class extends Migration
{
	private const OLD_ROUTE = 'account.messages';
	private const NEW_ROUTE = 'chat.index';
	private const ICON = 'bi-chat-dots';
	private const LOCALES = ['en', 'fr', 'de', 'es', 'it', 'ru', 'ar', 'bn', 'hi'];

	public function up(): void
	{
		if (!Schema::hasTable('menu_items')) {
			return;
		}

		foreach (DB::table('menu_items')->where('route_name', self::OLD_ROUTE)->get(['id', 'label']) as $item) {
			$label = json_decode((string)$item->label, true);
			$label = is_array($label) ? $label : [];
			foreach (self::LOCALES as $locale) {
				$label[$locale] = trans('chat.chats', [], $locale);
			}

			DB::table('menu_items')->where('id', $item->id)->update([
				'route_name' => self::NEW_ROUTE,
				'icon'       => self::ICON,
				'label'      => json_encode($label, JSON_UNESCAPED_UNICODE),
			]);
		}
	}

	public function down(): void
	{
		if (Schema::hasTable('menu_items')) {
			DB::table('menu_items')->where('route_name', self::NEW_ROUTE)->update(['route_name' => self::OLD_ROUTE]);
		}
	}
};
