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

namespace App\Models\Traits;

use App\Helpers\Common\DBUtils;
use App\Http\Controllers\Web\Admin\Panel\Library\Panel;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

trait CategoryTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function rebuildNestedSetNodesTopButton(?Panel $xPanel = null): string
	{
		$url = urlGen()->adminUrl('categories/rebuild_nested_set_nodes');
		
		$msg = trans('admin.rebuild_nested_set_nodes_info');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-light shadow mb-1 confirm-simple-action" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fa-solid fa-code-branch"></i> ';
		$out .= trans('admin.rebuild_nested_set_nodes');
		$out .= '</a>';
		
		return $out;
	}
	
	public function crudNameColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = $xPanel->getUrl($this->id . '/edit');
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function crudSubCategoriesColumn(?Panel $xPanel = null, array $column = []): string
	{
		$out = '';
		
		$url = urlGen()->adminUrl("categories/{$this->id}/subcategories");
		
		$msg = trans('admin.Subcategories of category', ['category' => $this->name]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		$countSubCats = $this->children->count();
		
		$out .= '<a class="btn btn-xs btn-light" href="' . $url . '"' . $tooltip . '>';
		$out .= $countSubCats . ' ';
		$out .= ($countSubCats > 1) ? trans('admin.subcategories') : trans('admin.subcategory');
		$out .= '</a>';
		
		return $out;
	}
	
	public function crudCustomFieldsColumn(?Panel $xPanel = null, array $column = []): string
	{
		$url = urlGen()->adminUrl("categories/{$this->id}/custom_fields");
		
		$msg = trans('admin.Custom Fields of category', ['category' => $this->name]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		$countFields = $this->fields->count();
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $tooltip . '>';
		$out .= $countFields . ' ';
		$out .= ($countFields > 1) ? trans('admin.custom fields') : trans('admin.custom field');
		$out .= '</a>';
		
		return $out;
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
	
	/**
	 * Count Posts by Category
	 *
	 * @param $cityId
	 * @return array
	 */
	public static function countListingsPerCategory($cityId = null): array
	{
		$whereCity = '';
		if (!empty($cityId)) {
			$whereCity = ' AND tPost.city_id = ' . $cityId;
		}
		
		$categoriesTable = (new Category())->getTable();
		$postsTable = (new Post())->getTable();
		
		$sql = 'SELECT parent.id, COUNT(*) AS total
				FROM ' . DBUtils::table($categoriesTable) . ' AS node,
						' . DBUtils::table($categoriesTable) . ' AS parent,
						' . DBUtils::table($postsTable) . ' AS tPost
				WHERE node.lft BETWEEN parent.lft AND parent.rgt
						AND node.id = tPost.category_id
						AND tPost.country_code = :countryCode' . $whereCity . '
						AND ((tPost.email_verified_at IS NOT NULL) AND (tPost.phone_verified_at IS NOT NULL))
						AND (tPost.archived_at IS NULL)
						AND (tPost.deleted_at IS NULL)
				GROUP BY parent.id';
		$bindings = [
			'countryCode' => config('country.code'),
		];
		$cats = DB::select($sql, $bindings);
		
		return collect($cats)->keyBy('id')->toArray();
	}
}
