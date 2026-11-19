<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * One moderation decision for a listing (adniro:ai).
 */

class PostModeration extends Model
{
	protected $table = 'post_moderations';
	
	protected $fillable = [
		'post_id',
		'trigger',
		'source',
		'mode',
		'content_hash',
		'verdict',
		'decided_status',
		'rule',
		'applied_status',
		'categories',
		'confidence',
		'user_reason',
		'internal_reason',
		'locale',
		'model',
		'policy_version',
		'ai_usage_log_id',
		'reviewer_id',
		'notified_at',
		'error',
	];
	
	protected function casts(): array
	{
		return [
			'categories'  => 'array',
			'notified_at' => 'datetime',
		];
	}
	
	public function post(): BelongsTo
	{
		return $this->belongsTo(Post::class, 'post_id')->withoutGlobalScopes();
	}
	
	public function usageLog(): BelongsTo
	{
		return $this->belongsTo(AiUsageLog::class, 'ai_usage_log_id');
	}
	
	public function reviewer(): BelongsTo
	{
		return $this->belongsTo(User::class, 'reviewer_id');
	}
}
