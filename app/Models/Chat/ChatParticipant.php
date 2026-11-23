<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * A person in a chat conversation (adniro), on the Messenger's "threads_participants" table: how far they
 * have read, and how many messages they have not.
 */
class ChatParticipant extends Model
{
	protected $table = 'threads_participants';

	protected $fillable = ['thread_id', 'user_id', 'last_read', 'last_read_message_id', 'unread_count', 'muted_until', 'digest_sent_at'];

	protected function casts(): array
	{
		return [
			'last_read'      => 'datetime',
			'muted_until'    => 'datetime',
			'digest_sent_at' => 'datetime',
			'unread_count'   => 'integer',
		];
	}

	public function thread(): BelongsTo
	{
		return $this->belongsTo(ChatThread::class, 'thread_id');
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
	}
}
