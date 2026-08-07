<?php
/*
 * Blog feature (custom development for adniro.com)
 */

namespace App\Models\Traits;

use App\Http\Controllers\Web\Admin\Panel\Library\Panel;

trait BlogPostTrait
{
	// ===| ADMIN PANEL METHODS |===

	public function crudTitleColumn(?Panel $xPanel = null, array $column = []): string
	{
		return '<a href="' . urlGen()->blogPost($this) . '" target="_blank">' . $this->title . '</a>';
	}

	public function crudImageColumn(?Panel $xPanel = null, array $column = []): string
	{
		$imageUrl = $this->image_url ?? null;
		if (empty($imageUrl)) {
			return '';
		}

		return '<img src="' . $imageUrl . '" alt="" style="height: 40px; width: auto; border-radius: 4px;">';
	}

	/**
	 * The post's targeted country (all the countries, when it is not set)
	 */
	public function crudCountryTargetColumn(?Panel $xPanel = null, array $column = []): string
	{
		$countryCode = $this->country_code ?? null;

		if (empty($countryCode)) {
			return '<span class="badge bg-secondary">' . trans('admin.blog_all_countries') . '</span>';
		}

		$country = $this->country ?? null;
		$countryName = $country->name ?? $countryCode;
		$countryFlagUrl = $country->flag_url ?? null;

		if (!empty($countryFlagUrl)) {
			return '<img src="' . $countryFlagUrl . '" data-bs-toggle="tooltip" title="' . $countryName . '"> ' . $countryCode;
		}

		return $countryName;
	}

	public function crudCategoryColumn(?Panel $xPanel = null, array $column = []): string
	{
		$category = $this->category;
		if (empty($category)) {
			return '<span class="badge bg-secondary">-</span>';
		}

		$url = urlGen()->adminUrl('blog/categories/' . $category->getKey() . '/edit');

		return '<a href="' . $url . '">' . $category->name . '</a>';
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
				'source' => ['slug', 'title'],
			],
		];
	}
}
