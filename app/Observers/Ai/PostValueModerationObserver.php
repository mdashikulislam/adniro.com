<?php

namespace App\Observers\Ai;

use App\Models\PostValue;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationTrigger;
use Throwable;

/*
 * A custom field value added, changed or removed sends the listing for a new check (adniro:ai).
 */

class PostValueModerationObserver
{
	public function created(PostValue $value): void
	{
		$this->schedule($value);
	}
	
	public function updated(PostValue $value): void
	{
		if ($value->wasChanged(['value', 'option_id'])) {
			$this->schedule($value);
		}
	}
	
	public function deleted(PostValue $value): void
	{
		$this->schedule($value);
	}
	
	private function schedule(PostValue $value): void
	{
		try {
			app(ModerationService::class)->schedule($value->post_id, ModerationTrigger::FIELDS);
		} catch (Throwable $e) {
			report($e);
		}
	}
}
