<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Observers;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Models\BlogPost;

class BlogPostObserver extends BaseObserver
{
	/**
	 * Listen to the Entry saving event.
	 *
	 * @param \App\Models\BlogPost $post
	 * @return void
	 */
	public function saving(BlogPost $post): void
	{
		// Set the publication date (if it is not set)
		if (empty($post->published_at)) {
			$post->published_at = now();
		}

		// Set the author (if it is not set)
		if (empty($post->user_id) && auth()->check()) {
			$post->user_id = auth()->id();
		}

		// Normalize the targeted country code (empty value => all the countries)
		if (empty($post->country_code)) {
			$post->country_code = null;
		} else {
			$post->country_code = strtoupper($post->country_code);
		}
	}

	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param \App\Models\BlogPost $post
	 * @return void
	 */
	public function deleting(BlogPost $post): void
	{
		// Delete the post picture
		$imagePath = $post->getRawOriginal('image_path');
		if (!empty($imagePath)) {
			$disk = StorageDisk::getDisk();
			if ($disk->exists($imagePath)) {
				$disk->delete($imagePath);
			}
		}
	}
}
