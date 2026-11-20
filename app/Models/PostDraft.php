<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * A quick post draft (adniro:ai).
 */

class PostDraft extends Model
{
	public const NEW = 'new';
	public const DRAFTED = 'drafted';
	public const UNUSABLE = 'unusable';
	public const FAILED = 'failed';
	public const APPLIED = 'applied';
	
	protected $table = 'post_drafts';
	
	protected $guarded = ['id'];
	
	protected function casts(): array
	{
		return [
			'input_meta'    => 'array',
			'picture_paths' => 'array',
			'raw_output'    => 'array',
			'draft'         => 'array',
			'counted'       => 'boolean',
			'expires_at'    => 'datetime',
		];
	}
	
	public function getRouteKeyName(): string
	{
		return 'token';
	}
	
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id');
	}
	
	public function isExpired(): bool
	{
		return !empty($this->expires_at) && $this->expires_at->isPast();
	}
}
