<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Observers;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Models\BlogCategory;
use App\Models\BlogPost;

class BlogCategoryObserver extends BaseObserver
{
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param \App\Models\BlogCategory $category
	 * @return void
	 */
	public function deleting(BlogCategory $category): void
	{
		// Delete the category picture
		$imagePath = $category->getRawOriginal('image_path');
		if (!empty($imagePath)) {
			$disk = StorageDisk::getDisk();
			if ($disk->exists($imagePath)) {
				$disk->delete($imagePath);
			}
		}

		// Detach the category's posts (the posts are kept)
		BlogPost::query()
			->withoutGlobalScopes()
			->where('category_id', $category->getKey())
			->update(['category_id' => null]);

		// Attach the sub-categories to the parent category (if any)
		BlogCategory::query()
			->withoutGlobalScopes()
			->where('parent_id', $category->getKey())
			->update(['parent_id' => $category->parent_id]);
	}
}
