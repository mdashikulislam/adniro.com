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

namespace App\Http\Resources;

use App\Models\ThreadMessage;
use Illuminate\Http\Request;

class ThreadResource extends BaseResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		/** @var \App\Models\Thread $this */
		if (!isset($this->id)) return [];
		
		$perPage = (int)data_get($this->params, 'perPage', 10);
		
		$entity = [
			'id' => $this->id,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$appendedColumns = $this->getAppends();
		foreach ($appendedColumns as $column) {
			$entity[$column] = $this->{$column} ?? null;
		}
		
		$entity['updated_at'] = $this->updated_at ?? null;
		$entity['latest_message'] = $this->latest_message ?? null;
		
		$authUser = auth(getAuthGuard())->user();
		
		if (in_array('user', $this->embed)) {
			if (!empty($authUser)) {
				$entity['user'] = new UserResource($authUser, $this->params);
			}
		}
		
		if (in_array('post', $this->embed)) {
			$entity['post'] = new PostResource($this->whenLoaded('post'), $this->params);
		}
		
		if (in_array('messages', $this->embed)) {
			// Get the Thread's Messages
			$messages = collect();
			if (!empty($authUser)) {
				$messages = ThreadMessage::query()
					->notDeletedByUser($authUser->getAuthIdentifier())
					->where('thread_id', $this->id)
					->with('user')
					->orderByDesc('id');
				$messages = $messages->paginate($perPage);
			}
			
			$messagesCollection = new EntityCollection(ThreadMessageResource::class, $messages, $this->params);
			$message = ($messages->count() <= 0) ? t('no_messages_found') : null;
			$entity['messages'] = apiResponse()->withCollection($messagesCollection, $message)->getData(true);
		}
		
		if (in_array('participants', $this->embed)) {
			$users = $this->whenLoaded('users');
			$usersCollection = new EntityCollection(UserResource::class, $users, $this->params);
			$entity['participants'] = $usersCollection->toArray($request, true);
		}
		
		return $entity;
	}
}
