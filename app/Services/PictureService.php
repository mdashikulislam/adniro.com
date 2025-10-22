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

namespace App\Services;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../Helpers/Common/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	$configForUpload = true;
	include_once $iniConfigFile;
}

use App\Helpers\Common\PaginationHelper;
use App\Http\Requests\Front\PhotoRequest;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PictureResource;
use App\Models\Picture;
use App\Services\Picture\MultiStepsPictures;
use App\Services\Picture\SingleStepPictures;
use Illuminate\Http\JsonResponse;

class PictureService extends BaseService
{
	use MultiStepsPictures, SingleStepPictures;
	
	/**
	 * List pictures
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$perPage = getNumberOfItemsPerPage('pictures', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$latest = castIntToBool($params['latest'] ?? 0);
		$postId = $params['postId'] ?? null;
		$sort = $params['sort'] ?? [];
		
		$pictures = Picture::query();
		
		if (in_array('post', $embed)) {
			$pictures->with('post');
		}
		
		if (!empty($postId)) {
			$pictures->where('post_id', $postId);
		}
		
		// Sorting
		$pictures = $this->applySorting($pictures, ['position', 'created_at'], $sort);
		
		if ($latest) {
			$picture = $pictures->first();
			
			abort_if(empty($picture), 404, t('picture_not_found'));
			
			$resource = new PictureResource($picture, $embed);
			
			return apiResponse()->withResource($resource);
		} else {
			$pictures = $pictures->paginate($perPage);
			$pictures = PaginationHelper::adjustSides($pictures);
			
			// If the request is made from the app's Web environment,
			// use the Web URL as the pagination's base URL
			$pictures = setPaginationBaseUrl($pictures);
			
			$resourceCollection = new EntityCollection(PictureResource::class, $pictures, $params);
			
			$message = ($pictures->count() <= 0) ? t('no_pictures_found') : null;
			
			return apiResponse()->withCollection($resourceCollection, $message);
		}
	}
	
	/**
	 * Get picture
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		$picture = Picture::query();
		
		if (in_array('post', $embed)) {
			$picture->with('post');
		}
		
		$picture = $picture->find($id);
		
		abort_if(empty($picture), 404, t('picture_not_found'));
		
		$resource = new PictureResource($picture, $params);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Store picture
	 *
	 * Note: This endpoint is only available for the multi steps post edition.
	 *
	 * @param \App\Http\Requests\Front\PhotoRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(PhotoRequest $request): JsonResponse
	{
		// Check if the form type is 'Single-Step Form'
		if (isSingleStepFormEnabled()) {
			abort(404);
		}
		
		return $this->multiStepsPicturesStore($request);
	}
	
	/**
	 * Reorder pictures
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reorder(array $params = []): JsonResponse
	{
		// Single-Step Form
		if (isSingleStepFormEnabled()) {
			abort(404);
		}
		
		return $this->reorderMultiStepsPictures($params);
	}
	
	/**
	 * Delete picture
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 * For newly created listings, the post's ID needs to be added in the request input with the key 'new_post_id'.
	 * The 'new_post_id' and 'new_post_tmp_token' fields need to be removed or unset during the listing edition steps.
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy($id, array $params = []): JsonResponse
	{
		// Check if the form type is 'Single-Step Form'
		if (isSingleStepFormEnabled()) {
			// abort(404);
		}
		
		return $this->deleteMultiStepsPicture($id, $params);
	}
}
