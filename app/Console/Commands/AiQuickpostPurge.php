<?php

namespace App\Console\Commands;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Models\PostDraft;
use Illuminate\Console\Command;
use Throwable;

/*
 * php artisan ai:quickpost-purge (adniro:ai), scheduled hourly.
 *
 * Deletes expired quick post drafts, and temporary uploads older than 48 hours. Temporary
 * uploads are the photos and files of listings that were never finished, from quick post and
 * from the normal form alike; nothing else ever cleaned them up.
 */

class AiQuickpostPurge extends Command
{
	public const TEMP_DIR = 'temporary';
	
	protected $signature = 'ai:quickpost-purge
		{--hours=48 : Delete temporary uploads older than this}
		{--dry-run : Only count what would be deleted}';
	
	protected $description = 'Delete expired quick post drafts and old temporary uploads (adniro:ai).';
	
	public function handle(): int
	{
		$dryRun = (bool)$this->option('dry-run');
		$cutoff = now()->subHours(max(1, (int)$this->option('hours')))->getTimestamp();
		
		// Expired drafts
		$drafts = PostDraft::query()->whereNotNull('expires_at')->where('expires_at', '<', now());
		$draftCount = (clone $drafts)->count();
		if (!$dryRun) {
			$drafts->delete();
		}
		
		// Old temporary uploads
		$disk = StorageDisk::getDisk();
		$fileCount = 0;
		$dirCount = 0;
		
		try {
			if ($disk->exists(self::TEMP_DIR)) {
				foreach ($disk->allFiles(self::TEMP_DIR) as $path) {
					if (str_ends_with($path, 'index.html') || str_ends_with($path, '.gitignore')) {
						continue;
					}
					try {
						if ($disk->lastModified($path) < $cutoff) {
							$fileCount++;
							if (!$dryRun) {
								$disk->delete($path);
							}
						}
					} catch (Throwable $e) {
					}
				}
				
				// Folders left empty, deepest first
				$dirs = $disk->allDirectories(self::TEMP_DIR);
				usort($dirs, fn ($a, $b) => substr_count($b, '/') <=> substr_count($a, '/'));
				foreach ($dirs as $dir) {
					if (empty($disk->allFiles($dir)) && empty($disk->directories($dir))) {
						$dirCount++;
						if (!$dryRun) {
							$disk->deleteDirectory($dir);
						}
					}
				}
			}
		} catch (Throwable $e) {
			$this->error('Could not read the temporary folder: ' . $e->getMessage());
			
			return self::FAILURE;
		}
		
		$this->info(($dryRun ? 'Would delete' : 'Deleted') . ": {$draftCount} expired drafts, {$fileCount} temporary files, {$dirCount} empty folders.");
		
		return self::SUCCESS;
	}
}
