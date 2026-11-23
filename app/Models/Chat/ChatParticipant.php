<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * A person in a chat conversation (adniro), on the Messenger's "threads_participants" table: how far they
 * have read, how many messages they have not, and what they did with it in their own list: deleted it
 * ("deleted_at"), archived it ("archived_at") or muted it ("muted_until"). None of it is seen by the other person.
 */
class ChatParticipant extends Model
{
	protected $table = 'threads_participants';

	protected $fillable = ['thread_id', 'user_id', 'last_read', 'last_read_message_id', 'cleared_message_id', 'unread_count', 'muted_until', 'archived_at', 'digest_sent_at', 'away_replied_at'];

	protected function casts(): array
	{
		return [
			'last_read'      => 'datetime',
			'muted_until'    => 'datetime',
			'archived_at'    => 'datetime',
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

	public function isMuted(): bool
	{
		return $this->muted_until !== null && $this->muted_until->isFuture();
	}

	public function isArchived(): bool
	{
		return $this->archived_at !== null;
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
