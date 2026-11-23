<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;

/*
 * A member's own chat choices (adniro): the away message and the saved replies (App\Services\Chat\ChatTools),
 * and what they let others see (App\Services\Chat\ChatPrivacy). One row per member, made the first time they
 * save something.
 */
class ChatPreference extends Model
{
	protected $table = 'chat_preferences';

	protected $fillable = ['user_id', 'away_on', 'away_message', 'saved_replies', 'shows_online', 'shows_seen'];

	protected function casts(): array
	{
		return ['away_on' => 'boolean', 'saved_replies' => 'array', 'shows_online' => 'boolean', 'shows_seen' => 'boolean'];
	}
}
