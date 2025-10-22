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

namespace App\Http\Controllers\Api;

use App\Services\CategoryService;
use App\Services\FieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Categories
 */
class CategoryController extends BaseController
{
	protected CategoryService $categoryService;
	protected FieldService $fieldService;
	
	/**
	 * @param \App\Services\CategoryService $categoryService
	 * @param \App\Services\FieldService $fieldService
	 */
	public function __construct(CategoryService $categoryService, FieldService $fieldService)
	{
		parent::__construct();
		
		$this->categoryService = $categoryService;
		$this->fieldService = $fieldService;
	}
	
	/**
	 * List categories
	 *
	 * @queryParam parentId int The ID of the parent category of the sub categories to retrieve. Example: 0
	 * @queryParam nestedIncluded int If parent ID is not provided, are nested entries will be included? - Possible values: 0,1. Example: 0
	 * @queryParam embed string The Comma-separated list of the category relationships for Eager Loading - Possible values: parent,children. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: lft. Example: -lft
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 * @queryParam page int Items page number. From 1 to ("total items" divided by "items per page value - perPage"). Example: 1
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): JsonResponse
	{
		$params = [
			'cacheExpiration' => request()->integer('cacheExpiration'),
			'perPage'         => request()->integer('perPage'),
			'page'            => request()->integer('page'),
			'embed'           => request()->input('embed'),
			'nestedIncluded'  => (request()->input('nestedIncluded') == 1),
		];
		
		$parentId = request()->integer('parentId');
		
		return $this->categoryService->getEntries($parentId, $params);
	}
	
	/**
	 * Get category
	 *
	 * Get category by its unique slug or ID.
	 *
	 * @queryParam parentCatSlug string The slug of the parent category to retrieve used when category's slug provided instead of ID. Example: automobiles
	 *
	 * @urlParam slugOrId string required The slug or ID of the category. Example: cars
	 *
	 * @param int|string $slugOrId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show(int|string $slugOrId): JsonResponse
	{
		$parentSlug = is_numeric($slugOrId) ? null : request()->input('parentCatSlug');
		
		return $this->categoryService->getEntry($slugOrId, $parentSlug);
	}
	
	/**
	 * List category's fields
	 *
	 * @bodyParam language_code string The code of the user's spoken language. Example: en
	 * @bodyParam post_id int required The unique ID of the post. Example: 1
	 *
	 * Note:
	 * - Called when showing Post's creation or edit forms
	 * - POST method is used instead of GET due to big JSON data sending (errors & old)
	 *
	 * @param $categoryId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCustomFields($categoryId, Request $request): JsonResponse
	{
		$params = [
			'postId'       => $request->input('postId'),
			'errors'       => $request->input('errors'),
			'oldInput'     => $request->input('oldInput'),
			'languageCode' => $request->input('languageCode'),
			'sort'         => '-lft',
		];
		
		return $this->fieldService->getCategoryFields($categoryId, $params);
	}
}
