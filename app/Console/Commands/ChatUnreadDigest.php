<?php

namespace App\Console\Commands;

use App\Services\Chat\ChatDigest;
use Illuminate\Console\Command;

/* Emails members who have left chat messages unread (adniro). Runs every minute from the scheduler. */
class ChatUnreadDigest extends Command
{
	protected $signature = 'chat:unread-digest';

	protected $description = 'Email members who have unread chat messages: one email, not one per message';

	public function handle(): int
	{
		$sent = ChatDigest::send();
		$this->info("Unread digest: {$sent} email(s) sent.");

		return self::SUCCESS;
	}
}
