<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * A person in a chat conversation (adniro), on the Messenger's "threads_participants" table: how far they
 * have read, how many messages they have not, and whether they have hidden it from their list ("deleted_at").
 */
class ChatParticipant extends Model
{
	protected $table = 'threads_participants';

	protected $fillable = ['thread_id', 'user_id', 'last_read', 'last_read_message_id', 'cleared_message_id', 'unread_count', 'muted_until', 'digest_sent_at'];

	protected function casts(): array
	{
		return [
			'last_read'      => 'datetime',
			'muted_until'    => 'datetime',
			'digest_sent_at' => 'datetime',
			'unread_count'   => 'integer',
		];
	}

	/**
	 * Not hidden by the member. "deleted_at" on this row means the member removed the conversation from their
	 * own list (the other person keeps it); it comes back with the next message (ChatMessages).
	 */
	public function scopeVisible(Builder $query): void
	{
		$query->whereNull($this->getTable() . '.deleted_at');
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
