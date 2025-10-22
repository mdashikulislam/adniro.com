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

use App\Helpers\Common\PaginationHelper;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\UserSocialLoginResource;
use App\Models\UserSocialLogin;
use Illuminate\Http\JsonResponse;

class UserSocialLoginService extends BaseService
{
	/**
	 * List user social logins
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$perPage = getNumberOfItemsPerPage('pages', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$sort = $params['sort'] ?? [];
		
		// Query
		$socialLogins = UserSocialLogin::query();
		
		if (in_array('user', $embed)) {
			$socialLogins->with('user');
		}
		
		// Sorting
		$socialLogins = $this->applySorting($socialLogins, ['provider', 'created_at'], $sort);
		
		$socialLogins = $socialLogins->paginate($perPage);
		$socialLogins = PaginationHelper::adjustSides($socialLogins);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$socialLogins = setPaginationBaseUrl($socialLogins);
		
		$resourceCollection = new EntityCollection(UserSocialLoginResource::class, $socialLogins, $params);
		
		$message = ($socialLogins->count() <= 0) ? t('no_user_social_logins_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get user social login
	 *
	 * @param string|int $providerOrId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry(string|int $providerOrId, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$authUserId = auth(getAuthGuard())->user()?->getAuthIdentifier() ?? -1;
		
		$socialLogin = is_numeric($providerOrId)
			? UserSocialLogin::query()->where('id', $providerOrId)
			: UserSocialLogin::query()->where('provider', '=', $providerOrId)->where('user_id', $authUserId);
		
		if (in_array('user', $embed)) {
			$socialLogin->with('user');
		}
		
		$socialLogin = $socialLogin->first();
		
		abort_if(empty($socialLogin), 404, t('user_social_login_not_found'));
		
		$resource = new UserSocialLoginResource($socialLogin, $params);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Delete an entry
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		$data = [
			'success' => false,
			'message' => t('no_entries_deleted'),
			'result'  => null,
		];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $entryId) {
			$entry = UserSocialLogin::query()->where('id', $entryId)->first();
			
			if (!empty($entry)) {
				$res = $entry->delete();
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			$data['message'] = t('entries_deleted');
		}
		
		return apiResponse()->json($data);
	}
}
