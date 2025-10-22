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

namespace App\Observers\Traits;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Common\Files\Tools\FileStorage;
use App\Observers\UserObserver;

trait HasImageWithThumbs
{
	/**
	 * Remove Picture With Its Thumbnails
	 *
	 * @param string|null $filePath
	 * @param string|null $defaultPicture
	 */
	public static function removePictureWithItsThumbs(?string $filePath, ?string $defaultPicture = null): void
	{
		if (empty($filePath)) {
			return;
		}
		
		if (empty($defaultPicture)) {
			$defaultPicture = config('larapen.media.picture');
			if (str_contains(get_called_class(), UserObserver::class)) {
				$defaultPicture = config('larapen.media.avatar');
			}
		}
		
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		if (str_starts_with($filePath, 'uploads' . DIRECTORY_SEPARATOR)) {
			$filePath = str_replace('uploads' . DIRECTORY_SEPARATOR, '', $filePath);
		}
		
		// Get the picture filename (without path)
		$filename = basename($filePath);
		$filenameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);
		
		// Get the picture's directory
		$fileDir = dirname($filePath);
		
		if (!empty($fileDir) && $disk->exists($fileDir)) {
			if ($disk->directoryExists($fileDir)) {
				// Get all the files in the main picture's directory (recursively)
				$files = $disk->allFiles($fileDir);
				if (!empty($files)) {
					foreach ($files as $file) {
						// Don't delete the default picture
						if (str_contains($file, $defaultPicture)) {
							continue;
						}
						// Delete the picture with its thumbs (by making a search with the picture original filename)
						if (str_contains($file, $filenameWithoutExtension)) {
							$disk->delete($file);
						}
					}
				}
				
				if (!str_contains($filePath, $defaultPicture)) {
					FileStorage::removeEmptySubDirs($disk, $fileDir);
				}
			}
		}
	}
}
