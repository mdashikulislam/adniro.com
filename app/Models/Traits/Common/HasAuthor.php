<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Models\Traits\Common;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasAuthor
{
	public static function bootHasAuthor(): void
	{
		$authUserId = auth()->user()?->getAuthIdentifier() ?? null;
		
		static::creating(function (Model $model) use($authUserId) {
			$model->created_by = $authUserId;
		});
		
		static::updating(function (Model $model) use($authUserId) {
			$model->updated_by = $authUserId;
		});
		
		static::deleting(function (Model $model) use($authUserId) {
			if (in_array(SoftDeletes::class, class_uses($model))) {
				$model->updated_by = $authUserId;
				$model->save();
			}
		});
	}
	
	public function author(): BelongsTo
	{
		return $this->belongsTo(User::class, 'created_by');
	}
	
	public function editor(): BelongsTo
	{
		return $this->belongsTo(User::class, 'updated_by');
	}
}

