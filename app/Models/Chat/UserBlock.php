<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;

/* A member who has blocked another member (adniro): neither can message the other. */
class UserBlock extends Model
{
	protected $table = 'user_blocks';

	protected $fillable = ['blocker_id', 'blocked_id'];
}
