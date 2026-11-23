<?php

namespace App\Models\Chat;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/* A conversation reported to the admins (adniro). Open until an admin closes it (Admin > Users > Chat reports). */
class ThreadReport extends Model
{
	public const REASONS = ['spam', 'scam', 'abuse', 'other'];
	public const STATUS_OPEN = 'open';
	public const STATUS_CLOSED = 'closed';
	public const STATUSES = [self::STATUS_OPEN, self::STATUS_CLOSED];
	/** The number of open reports shown in the admin menu is kept for a minute under this name. */
	public const OPEN_COUNT_CACHE = 'chat:open_reports';

	protected $table = 'thread_reports';

	protected $fillable = ['thread_id', 'reporter_id', 'reason', 'note', 'status', 'closed_by', 'closed_at'];

	protected function casts(): array
	{
		return ['closed_at' => 'datetime'];
	}

	public function thread(): BelongsTo
	{
		return $this->belongsTo(ChatThread::class, 'thread_id')->withTrashed();
	}

	public function reporter(): BelongsTo
	{
		return $this->belongsTo(User::class, 'reporter_id')->withoutGlobalScopes();
	}

	public function closer(): BelongsTo
	{
		return $this->belongsTo(User::class, 'closed_by')->withoutGlobalScopes();
	}

	/** The admin menu's count of open reports, counted again on its next page. */
	public static function forgetOpenCount(): void
	{
		cache()->forget(self::OPEN_COUNT_CACHE);
	}

	public function isOpen(): bool
	{
		return $this->status === self::STATUS_OPEN;
	}

	/** The member the report is about: the other person in the conversation. */
	public function reportedUserId(): int
	{
		return (int)($this->thread?->otherParticipant((int)$this->reporter_id)?->user_id ?? 0);
	}
}
