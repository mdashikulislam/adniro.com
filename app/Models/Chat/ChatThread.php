<?php

namespace App\Models\Chat;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
 * A live chat conversation (adniro): one per listing and buyer, on the Messenger's "threads" table.
 * A lean model for the chat: none of App\Models\Thread's appended attributes or observers.
 */
class ChatThread extends Model
{
	use SoftDeletes;

	protected $table = 'threads';

	protected $fillable = ['post_id', 'buyer_id', 'subject', 'last_message_id', 'last_message_at'];

	protected function casts(): array
	{
		return ['last_message_at' => 'datetime'];
	}

	/** The listing, even when it is archived or no longer public: the conversation outlives it. */
	public function post(): BelongsTo
	{
		return $this->belongsTo(Post::class, 'post_id')->withoutGlobalScopes();
	}

	public function buyer(): BelongsTo
	{
		return $this->belongsTo(User::class, 'buyer_id')->withoutGlobalScopes();
	}

	public function participants(): HasMany
	{
		return $this->hasMany(ChatParticipant::class, 'thread_id');
	}

	public function messages(): HasMany
	{
		return $this->hasMany(ChatMessage::class, 'thread_id');
	}

	public function lastMessage(): BelongsTo
	{
		return $this->belongsTo(ChatMessage::class, 'last_message_id');
	}

	public function scopeForUser(Builder $query, int $userId): void
	{
		$query->whereHas('participants', fn (Builder $participants) => $participants->where('user_id', $userId));
	}

	public function participantFor(int $userId): ?ChatParticipant
	{
		return $this->participants->firstWhere('user_id', $userId);
	}

	public function otherParticipant(int $userId): ?ChatParticipant
	{
		return $this->participants->first(fn (ChatParticipant $participant) => (int)$participant->user_id !== $userId);
	}
}
