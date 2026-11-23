<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;

/*
 * A member's own chat tools (adniro): the away message and the saved replies. One row per member, made the
 * first time they save something (App\Services\Chat\ChatTools).
 */
class ChatPreference extends Model
{
	protected $table = 'chat_preferences';

	protected $fillable = ['user_id', 'away_on', 'away_message', 'saved_replies'];

	protected function casts(): array
	{
		return ['away_on' => 'boolean', 'saved_replies' => 'array'];
	}
}
