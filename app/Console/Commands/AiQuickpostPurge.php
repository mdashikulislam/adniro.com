<?php

namespace App\Console\Commands;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Models\PostDraft;
use Illuminate\Console\Command;
use Throwable;

/*
 * php artisan ai:quickpost-purge (adniro:ai), scheduled hourly.
 *
 * Deletes expired quick post drafts (counted ones are emptied and kept 35 days for the AI post
 * limit), and temporary uploads older than 48 hours. Temporary
 * uploads are the photos and files of listings that were never finished, from quick post and
 * from the normal form alike; nothing else ever cleaned them up.
 */

class AiQuickpostPurge extends Command
{
	public const TEMP_DIR = 'temporary';
	
	/** Longer than the longest limit period (a month). */
	public const COUNTED_RETENTION_DAYS = 35;
	
	/** Columns emptied when a counted draft expires. */
	private const HEAVY_COLUMNS = ['input_text', 'input_meta', 'picture_paths', 'raw_output', 'draft', 'error'];
	
	protected $signature = 'ai:quickpost-purge
		{--hours=48 : Delete temporary uploads older than this}
		{--dry-run : Only count what would be deleted}';
	
	protected $description = 'Delete expired quick post drafts and old temporary uploads (adniro:ai).';
	
	public function handle(): int
	{
		$dryRun = (bool)$this->option('dry-run');
		$cutoff = now()->subHours(max(1, (int)$this->option('hours')))->getTimestamp();
		
		// Expired drafts. Counted ones are what the AI post limit counts, so they stay, stripped of
		// the text, photos and AI output, until no period can need them.
		$expired = PostDraft::query()->whereNotNull('expires_at')->where('expires_at', '<', now());
		$draftCount = (clone $expired)->where('counted', false)->count();
		$strippedCount = (clone $expired)->where('counted', true)->count();
		$oldCounted = PostDraft::query()->where('counted', true)->where('created_at', '<', now()->subDays(self::COUNTED_RETENTION_DAYS));
		$draftCount += (clone $oldCounted)->count();
		if (!$dryRun) {
			(clone $expired)->where('counted', false)->delete();
			(clone $expired)->where('counted', true)->update(array_fill_keys(self::HEAVY_COLUMNS, null) + ['expires_at' => null]);
			$oldCounted->delete();
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
		
		$this->info(($dryRun ? 'Would delete' : 'Deleted') . ": {$draftCount} drafts ({$strippedCount} counted drafts kept for the AI post limit, emptied), {$fileCount} temporary files, {$dirCount} empty folders.");
		
		return self::SUCCESS;
	}
}
