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
use App\Http\Resources\ThreadMessageResource;
use App\Models\ThreadMessage;
use Illuminate\Http\JsonResponse;

class ThreadMessageService extends BaseService
{
	/**
	 * List messages
	 *
	 * @param $threadId
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries($threadId, array $params = []): JsonResponse
	{
		$perPage = getNumberOfItemsPerPage('threads_messages', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$sort = $params['sort'] ?? [];
		
		$authUser = auth(getAuthGuard())->user();
		
		// All threads messages
		$threadMessages = ThreadMessage::whereHas('thread', function ($query) use ($threadId, $authUser) {
			$query->where('thread_id', $threadId)->forUser($authUser->getAuthIdentifier());
		});
		
		if (in_array('user', $embed)) {
			$threadMessages->with('user');
		}
		
		// Sorting
		$threadMessages = $this->applySorting($threadMessages, ['created_at'], $sort);
		
		// Get rows & paginate
		$threadMessages = $threadMessages->paginate($perPage);
		$threadMessages = PaginationHelper::adjustSides($threadMessages);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$threadMessages = setPaginationBaseUrl($threadMessages);
		
		$collection = new EntityCollection(ThreadMessageResource::class, $threadMessages, $params);
		
		$message = ($threadMessages->count() <= 0) ? t('no_messages_found') : null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Get message
	 *
	 * Get a thread's message (owned by the logged user) details
	 *
	 * @param $threadId
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($threadId, $id, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		
		$authUser = auth(getAuthGuard())->user();
		
		$threadMessage = ThreadMessage::whereHas('thread', function ($query) use ($threadId, $authUser) {
			$query->where('thread_id', $threadId)->forUser($authUser->getAuthIdentifier());
		});
		
		if (in_array('thread', $embed)) {
			$threadMessage->with('thread');
		}
		
		if (in_array('user', $embed)) {
			$threadMessage->with('user');
		}
		
		$threadMessage = $threadMessage->where('id', $id)->first();
		
		abort_if(empty($threadMessage), 404, t('message_not_found'));
		
		$resource = new ThreadMessageResource($threadMessage, $params);
		
		return apiResponse()->withResource($resource);
	}
}
