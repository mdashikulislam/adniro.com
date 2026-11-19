<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * One row per AI call (adniro:ai). Written by App\Services\Ai\LaravelAiClient.
 */

class AiUsageLog extends Model
{
	protected $table = 'ai_usage_logs';
	
	public const UPDATED_AT = null;
	
	protected $guarded = ['id'];
	
	protected function casts(): array
	{
		return [
			'cost_usd'     => 'decimal:6',
			'was_fallback' => 'boolean',
			'meta'         => 'array',
			'created_at'   => 'datetime',
		];
	}
}
