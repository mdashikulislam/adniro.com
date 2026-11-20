<?php

namespace App\Observers\Ai;

use App\Models\Picture;
use App\Services\Ai\Moderation\ModerationService;
use App\Services\Ai\Moderation\ModerationTrigger;
use Throwable;

/*
 * A photo added, replaced or removed sends the listing for a new check (adniro:ai).
 * Reordering photos does not. In enforce mode the listing is hidden until that check.
 */

class PictureModerationObserver
{
	public function created(Picture $picture): void
	{
		$this->schedule($picture);
	}
	
	public function updated(Picture $picture): void
	{
		if ($picture->wasChanged('file_path')) {
			$this->schedule($picture);
		}
	}
	
	public function deleted(Picture $picture): void
	{
		$this->schedule($picture);
	}
	
	private function schedule(Picture $picture): void
	{
		try {
			$service = app(ModerationService::class);
			
			if (isFromAdminPanel()) {
				$service->acceptAdminContent($picture->post_id);
				
				return;
			}
			
			$service->markPending($picture->post_id);
			$service->schedule($picture->post_id, ModerationTrigger::PICTURES);
		} catch (Throwable $e) {
			report($e);
		}
	}
}
