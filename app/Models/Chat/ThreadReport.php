<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/* A conversation reported to the admins (adniro). */
class ThreadReport extends Model
{
	public const REASONS = ['spam', 'scam', 'abuse', 'other'];
	public const STATUS_OPEN = 'open';

	protected $table = 'thread_reports';

	protected $fillable = ['thread_id', 'reporter_id', 'reason', 'note', 'status'];

	public function thread(): BelongsTo
	{
		return $this->belongsTo(ChatThread::class, 'thread_id')->withTrashed();
	}
}
