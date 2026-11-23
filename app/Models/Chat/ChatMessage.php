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

	protected $table = 'threads_messages';

	protected $fillable = ['thread_id', 'post_id', 'reply_to_id', 'user_id', 'type', 'body', 'file_path', 'client_uuid'];

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

	public function isImage(): bool
	{
		return $this->type === self::TYPE_IMAGE;
	}
}
