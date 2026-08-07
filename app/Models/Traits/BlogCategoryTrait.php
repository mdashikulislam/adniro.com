<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Models\Traits;

use App\Http\Controllers\Web\Admin\Panel\Library\Panel;

trait BlogCategoryTrait
{
	// ===| ADMIN PANEL METHODS |===

	public function crudNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		return '<a href="' . urlGen()->blogCategory($this) . '" target="_blank">' . $this->name . '</a>';
	}

	public function crudPostsCountColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = urlGen()->adminUrl('blog/posts?category=' . $this->getKey());

		return '<a href="' . $url . '">' . (int)$this->posts()->count() . '</a>';
	}

	// ===| OTHER METHODS |===

	/**
	 * Return the sluggable configuration array for this model.
	 *
	 * @return array
	 */
	public function sluggable(): array
	{
		return [
			'slug' => [
				'source' => ['slug', 'name'],
			],
		];
	}
}
