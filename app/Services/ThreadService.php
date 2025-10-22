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

use App\Helpers\Common\Files\Upload;
use App\Helpers\Common\PaginationHelper;
use App\Http\Requests\Front\ReplyMessageRequest;
use App\Http\Requests\Front\SendMessageRequest;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PostResource;
use App\Http\Resources\ThreadResource;
use App\Models\Post;
use App\Models\Thread;
use App\Models\ThreadMessage;
use App\Models\ThreadParticipant;
use App\Models\User;
use App\Notifications\ReplySent;
use App\Notifications\SellerContacted;
use App\Services\Auth\Traits\Custom\RecognizedUser\FindPostAuthor\FillMissingUserData;
use App\Services\Thread\UpdateByTypeTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ThreadService extends BaseService
{
	use FillMissingUserData;
	use UpdateByTypeTrait;
	
	/**
	 * List threads
	 *
	 * Get all logged user's threads.
	 * Filters:
	 * - unread: Get the logged user's unread threads
	 * - started: Get the logged user's started threads
	 * - important: Get the logged user's make as important threads
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		$perPage = getNumberOfItemsPerPage('threads', $params['perPage'] ?? null, $this->perPage);
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$filter = $params['filter'] ?? null;
		$sort = $params['sort'] ?? [];
		
		$authUser = auth(getAuthGuard())->user();
		
		// All threads
		$threads = Thread::whereHas('post', function ($query) {
			$query->inCountry()->unarchived();
		});
		
		if (in_array('post', $embed)) {
			$threads->with('post');
		}
		
		if ($filter == 'unread') {
			// Get threads that have new messages or that are marked as unread
			$threads->forUserWithNewMessages($authUser->getAuthIdentifier());
		} else {
			// Get threads that user is participating in
			$threads->forUser($authUser->getAuthIdentifier())->latest('updated_at');
		}
		
		// Get threads started by this user
		if ($filter == 'started') {
			$threadTable = (new Thread())->getTable();
			$messageTable = (new ThreadMessage())->getTable();
			
			$threads->where(function ($query) use ($threadTable, $messageTable) {
				$query->select('user_id')
					->from($messageTable)
					->whereColumn($messageTable . '.thread_id', $threadTable . '.id')
					->orderBy($messageTable . '.created_at')
					->limit(1);
			}, $authUser->getAuthIdentifier());
		}
		
		// Get this user's important thread
		if ($filter == 'important') {
			$threads->where('is_important', 1);
		}
		
		// Get rows & paginate
		$threads = $threads->paginate($perPage);
		$threads = PaginationHelper::adjustSides($threads);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$threads = setPaginationBaseUrl($threads);
		
		$collection = new EntityCollection(ThreadResource::class, $threads, $params);
		
		$message = ($threads->count() <= 0) ? t('no_threads_found') : null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Get thread
	 *
	 * Get a thread (owned by the logged user) details
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$perPage = getNumberOfItemsPerPage('threads', $params['perPage'] ?? null, $this->perPage); // for the thread's messages
		
		$authUser = auth(getAuthGuard())->user();
		
		$thread = Thread::query();
		
		if (in_array('user', $embed)) {
			// See the ThreadResource
		}
		
		if (in_array('post', $embed)) {
			$thread->with('post');
		}
		
		// Call the ThreadMessageController endpoint to get paginated messages
		if (in_array('messages', $embed)) {
			// See the ThreadResource
		}
		
		if (in_array('participants', $embed)) {
			$thread->with('users');
		}
		
		$threadTable = (new Thread())->getTable();
		$thread->forUser($authUser->getAuthIdentifier())->where($threadTable . '.id', $id);
		
		$thread = $thread->first();
		
		abort_if(empty($thread), 404, t('thread_not_found'));
		
		// Mark the Thread as read
		$thread->markTheThreadAsRead($authUser->getAuthIdentifier());
		
		$resource = new ThreadResource($thread, $params);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Store thread
	 *
	 * Start a conversation. Creation of a new thread.
	 *
	 * @param $postId
	 * @param \App\Http\Requests\Front\SendMessageRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	public function store($postId, SendMessageRequest $request): JsonResponse
	{
		$postId = $request->input('post_id', $postId);
		
		if (empty($postId)) {
			$msg = 'The "post_id" parameter is required.';
			
			return apiResponse()->error($msg);
		}
		
		$authUser = auth(getAuthGuard())->user();
		
		// Get the Post
		$post = Post::unarchived()->find($postId);
		
		abort_if(empty($post), 404, t('post_not_found'));
		
		// Check and complete missing auth data
		$missingAuthDataCompleted = $this->fillMissingUserData();
		
		$thread = null;
		
		// Create Message Array
		$messageArray = $request->all();
		
		// Logged User
		if (!empty($authUser) && !empty($post->user)) {
			// Thread
			$thread = new Thread();
			$thread->post_id = $post->id;
			$thread->subject = $post->title;
			$thread->save();
			
			// Message
			$message = new ThreadMessage();
			$message->thread_id = $thread->id;
			$message->user_id = $authUser->getAuthIdentifier();
			$message->body = $request->input('body');
			$message->save();
			
			// Save and Send user's résumé
			if ($request->hasFile('file_path')) {
				// Upload File
				$destPath = 'files/' . strtolower($post->country_code) . '/' . $post->id . '/applications';
				$message->file_path = Upload::file($request->file('file_path'), $destPath);
				
				$message->save();
			}
			
			// Update Message Array
			$messageArray['name'] = $authUser->name ?? null;
			$messageArray['email'] = $authUser->email ?? null;
			$messageArray['phone'] = $authUser->phone ?? null;
			$messageArray['country_code'] = $post->country_code ?? config('country.code');
			if (!empty($message->file_path)) {
				$messageArray['file_path'] = $message->file_path;
			}
			
			// Sender
			$sender = new ThreadParticipant();
			$sender->thread_id = $thread->id;
			$sender->user_id = $authUser->getAuthIdentifier();
			$sender->last_read = now();
			$sender->save();
			
			// Recipients
			if ($request->has('recipients')) {
				$thread->addParticipant($request->input('recipients'));
			} else {
				$thread->addParticipant($post->user->id);
			}
		} else {
			// Guest (Non Logged User)
			// Update the filename
			if ($request->hasFile('file_path')) {
				$file = $request->file('file_path');
				$messageArray['file_path'] = $file->getClientOriginalName(); // Caution: Only filename is retrieved here
				$messageArray['file_data'] = base64_encode(File::get($file->getRealPath()));
			}
		}
		
		// Remove input file to prevent Laravel Queue serialization issue
		if (isset($messageArray['file_path'])) {
			if (!is_string($messageArray['file_path'])) {
				unset($messageArray['file_path']);
			}
		}
		
		// Send a message to publisher
		if (isset($messageArray['post_id'], $messageArray['email'], $messageArray['name'], $messageArray['body'])) {
			try {
				$post->notify(new SellerContacted($post, $messageArray));
			} catch (Throwable $e) {
				return apiResponse()->internalError($e->getMessage());
			}
		}
		
		$msg = t('message_has_sent_successfully_to', ['contact_name' => $post->contact_name]);
		
		$data = [
			'success' => true,
			'message' => $msg,
		];
		
		if (!empty($thread)) {
			$data['result'] = (new ThreadResource($thread))->toArray($request);
		} else {
			$data['result'] = null;
		}
		
		$extra = [];
		
		$extra['post'] = (new PostResource($post))->toArray($request);
		$extra['missingAuthDataCompleted'] = $missingAuthDataCompleted;
		
		$data['extra'] = $extra;
		
		return apiResponse()->created($data);
	}
	
	/**
	 * Update thread
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\ReplyMessageRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update($id, ReplyMessageRequest $request): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		try {
			// We use with([users => fn()]) to prevent email sending
			// to the message sender (which is the current user)
			/** @var Thread $thread */
			$thread = Thread::with([
				'post',
				'users' => function ($query) use ($authUser) {
					$query->where((new User())->getTable() . '.id', '!=', $authUser->getAuthIdentifier());
				},
			])->findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return apiResponse()->notFound(t('thread_not_found', ['id' => $id]));
		}
		
		// Re-activate the Thread for all participants
		$thread->deleted_by = null;
		$thread->save();
		
		$thread->activateAllParticipants();
		
		// Create Message Array
		$messageArray = $request->all();
		
		// Message
		$message = new ThreadMessage();
		$message->thread_id = $thread->id;
		$message->user_id = $authUser->getAuthIdentifier();
		$message->body = $request->input('body');
		$message->save();
		
		// Save and Send user's resume
		if ($request->hasFile('file_path')) {
			// Upload File
			if (!empty($thread->post)) {
				$post = $thread->post;
				$destPath = 'files/' . strtolower($post->country_code) . '/' . $post->id . '/applications';
				$message->file_path = Upload::file($request->file('file_path'), $destPath);
				
				$message->save();
			}
		}
		
		// Update Message Array
		$messageArray['country_code'] = !empty($thread->post) ? $thread->post->country_code : config('country.code');
		$messageArray['post_id'] = !empty($thread->post) ? $thread->post->id : null;
		$messageArray['name'] = $authUser->name ?? null;
		$messageArray['email'] = $authUser->email ?? null;
		$messageArray['phone'] = $authUser->phone ?? null;
		$messageArray['subject'] = t('New message about') . ': ' . $thread->post->title;
		if (!empty($message->file_path)) {
			$messageArray['file_path'] = $message->file_path;
		}
		
		// Get the listing's auth field
		$authField = $authUser->auth_field ?? getAuthField();
		$messageArray['auth_field'] = $authField;
		$messageArray['to_auth_field'] = $authField;
		if (
			!empty($thread->post) && isset($thread->post->user_id)
			&& ($authUser->getAuthIdentifier() == $thread->post->user_id)
			&& isset($thread->post->auth_field) && !empty($thread->post->auth_field)
		) {
			$messageArray['to_auth_field'] = $thread->post->auth_field;
		}
		
		// Add replier as a participant
		$participant = ThreadParticipant::firstOrCreate([
			'thread_id' => $thread->id,
			'user_id'   => $authUser->getAuthIdentifier(),
		]);
		$participant->last_read = now();
		$participant->save();
		
		// Recipients
		if ($request->has('recipients')) {
			$thread->addParticipant($request->input('recipients'));
		} else {
			$thread->addParticipant($thread->post->user->id);
		}
		
		// Remove input file to prevent Laravel Queue serialization issue
		if (isset($messageArray['file_path'])) {
			if (!is_string($messageArray['file_path'])) {
				unset($messageArray['file_path']);
			}
		}
		
		// Send Reply Notification (Email|SMS?)
		if (
			isset($messageArray['post_id'])
			&& array_key_exists('email', $messageArray)
			&& isset($messageArray['name'])
			&& isset($messageArray['body'])
		) {
			try {
				if (!isDemoDomain()) {
					if (isset($thread->users) && $thread->users->count() > 0) {
						foreach ($thread->users as $threadUser) {
							if (
								!empty($thread->post) && isset($thread->post->user_id)
								&& ($threadUser->id == $thread->post->user_id)
								&& isset($thread->post->auth_field) && !empty($thread->post->auth_field)
							) {
								// Update the listing's auth field
								$messageArray['to_auth_field'] = $thread->post->auth_field;
							}
							$messageArray['to_email'] = $threadUser->email ?? null;
							$messageArray['to_phone'] = $threadUser->phone ?? null;
							$messageArray['to_phone_hidden'] = $threadUser->phone_hidden ?? 0;
							Notification::send($threadUser, new ReplySent($messageArray));
						}
					}
				}
			} catch (Throwable $e) {
				return apiResponse()->internalError($e->getMessage());
			}
		}
		
		$data = [
			'success' => true,
			'message' => t('Your reply has been sent'),
			'result'  => (new ThreadResource($thread))->toArray($request),
		];
		
		return apiResponse()->updated($data);
	}
	
	/**
	 * Bulk updates (i.e. Bulk actions)
	 *
	 * @param string|null $ids
	 * @param string|null $actionType
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function bulkUpdate(?string $ids = null, ?string $actionType = null): JsonResponse
	{
		// Get Selected Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		return $this->updateByType($ids, $actionType);
	}
	
	/**
	 * Delete thread(s)
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $id) {
			// Get the Thread
			$thread = Thread::where((new Thread)->getTable() . '.id', $id)
				->forUser($authUser->getAuthIdentifier())
				->first();
			
			if (!empty($thread)) {
				if (empty($thread->deleted_by)) {
					// Delete the Entry for current user
					// (by updating the 'deleted_by' column without updating the 'update_at')
					Thread::withoutTimestamps(
						fn () => $thread->where('id', $thread->id)->update(['deleted_by' => $authUser->getAuthIdentifier()])
					);
					
					$res = true;
				} else {
					// If the 2nd user deletes the Entry,
					// Delete the Entry (definitely)
					if ($thread->deleted_by != $authUser->getAuthIdentifier()) {
						$res = $thread->forceDelete();
					}
				}
			}
		}
		if (!$res) {
			return apiResponse()->noContent(t('no_deletion_is_done'));
		}
		
		// Confirmation
		$count = count($ids);
		if ($count > 1) {
			$msg = t('x entities have been deleted successfully', [
				'entities' => t('messages'),
				'count'    => $count,
			]);
		} else {
			$msg = t('1 entity has been deleted successfully', [
				'entity' => t('message'),
			]);
		}
		
		return apiResponse()->success($msg);
	}
}
