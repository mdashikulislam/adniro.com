<?php

namespace App\Models\Chat;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
 * A chat message (adniro), on the Messenger's "threads_messages" table. A photo's file is in "file_path",
 * on the private disk (App\Services\Chat\ChatPhotos).
 */
class ChatMessage extends Model
{
	use SoftDeletes;

	public const TYPE_TEXT = 'text';
	public const TYPE_IMAGE = 'image';
	/** A price proposed for the listing the message is about ("offer_amount", "offer_status"). */
	public const TYPE_OFFER = 'offer';
	/** The other person's answer to an offer: "reply_to_id" is the offer, "offer_status" the answer. */
	public const TYPE_OFFER_ANSWER = 'offer_answer';

	public const OFFER_PENDING = 'pending';
	public const OFFER_ACCEPTED = 'accepted';
	public const OFFER_DECLINED = 'declined';
	/** A newer offer was made on the same listing in this conversation. */
	public const OFFER_REPLACED = 'replaced';

	protected $table = 'threads_messages';

	protected $fillable = ['thread_id', 'post_id', 'reply_to_id', 'user_id', 'type', 'body', 'file_path', 'client_uuid', 'unsent_at', 'offer_amount', 'offer_currency', 'offer_status', 'is_auto'];

	protected function casts(): array
	{
		return ['unsent_at' => 'datetime', 'is_auto' => 'boolean'];
	}

	public function thread(): BelongsTo
	{
		return $this->belongsTo(ChatThread::class, 'thread_id');
	}

	/** The listing this message is about: set on the first message sent from that listing's "Chat with seller". */
	public function post(): BelongsTo
	{
		return $this->belongsTo(Post::class, 'post_id')->withoutGlobalScopes();
	}

	/** The earlier message this one replies to (none once that message is deleted). */
	public function replyTo(): BelongsTo
	{
		return $this->belongsTo(self::class, 'reply_to_id');
	}

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
	}

	/** Taken back by its sender: shown to both people as "Message deleted"; the row is kept for reports. */
	public function isUnsent(): bool
	{
		return $this->unsent_at !== null;
	}

	public function isImage(): bool
	{
		return $this->type === self::TYPE_IMAGE;
	}

	public function isOffer(): bool
	{
		return $this->type === self::TYPE_OFFER;
	}

	public function isOfferAnswer(): bool
	{
		return $this->type === self::TYPE_OFFER_ANSWER;
	}
}
