<?php

namespace App\Services\ListingAssistant;

use App\Helpers\Common\Files\TmpUpload;
use App\Observers\Traits\HasImageWithThumbs;
use Illuminate\Http\UploadedFile;
use Throwable;

/*
 * Puts the photos given to the AI box into the listing form's Photos step (adniro:ai), so the
 * seller does not upload them twice. The multi-step form keeps step 2's photos in the session
 * ("picturesInput", temporary files under temporary/{uid}); this adds to that list.
 *
 * Only the photos the box added are tracked: a new fill with photos replaces them, and photos the
 * seller uploaded in step 2 are never touched. The site's photo limit is respected.
 */

final class FormPhotos
{
	use HasImageWithThumbs;

	public const SESSION_KEY = 'listing_assistant_photos';
	private const TMP_DIR = 'temporary';

	/**
	 * @param UploadedFile[] $files
	 * @return int How many photos are now in the Photos step from the box
	 */
	public function store(array $files): int
	{
		$files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile && $file->isValid()));
		if (empty($files)) {
			return 0;
		}

		if (!session()->has('uid')) {
			session()->put('uid', generateUniqueCode(9));
		}

		$pictures = $this->withoutEarlierBoxPhotos();
		$room = max(0, (int)config('settings.listing_form.pictures_limit', 5) - count($pictures));

		$added = [];
		foreach (array_slice($files, 0, $room) as $file) {
			$path = TmpUpload::image($file, self::TMP_DIR . '/' . session('uid'));
			if (!empty($path)) {
				$added[] = $path;
			}
		}

		session()->put('picturesInput', array_values(array_merge($pictures, $added)));
		session()->put(self::SESSION_KEY, $added);

		return count($added);
	}

	/**
	 * The step 2 photos without the ones a previous fill added, whose files are deleted.
	 */
	private function withoutEarlierBoxPhotos(): array
	{
		$pictures = array_values((array)session('picturesInput', []));
		// Only photos still in step 2: the seller may have deleted one, or posted the listing
		$earlier = array_values(array_intersect((array)session(self::SESSION_KEY, []), $pictures));
		if (empty($earlier)) {
			return $pictures;
		}

		foreach ($earlier as $path) {
			try {
				self::removePictureWithItsThumbs((string)$path);
			} catch (Throwable $e) {
				report($e);
			}
		}

		return array_values(array_filter($pictures, fn ($path) => !in_array($path, $earlier, true)));
	}
}
