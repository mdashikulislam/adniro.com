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

use App\Services\ThreadMessageService;
use Illuminate\Http\JsonResponse;

/**
 * @group Threads
 */
class ThreadMessageController extends BaseController
{
	protected ThreadMessageService $threadMessageService;
	
	/**
	 * @param \App\Services\ThreadMessageService $threadMessageService
	 */
	public function __construct(ThreadMessageService $threadMessageService)
	{
		parent::__construct();
		
		$this->threadMessageService = $threadMessageService;
	}
	
	/**
	 * List messages
	 *
	 * Get all thread's messages
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the post relationships for Eager Loading - Possible values: user. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @urlParam threadId int required The thread's ID. Example: 293
	 *
	 * @param $threadId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index($threadId): JsonResponse
	{
		$params = [
			'perPage' => request()->integer('perPage'),
			'embed'   => request()->input('embed'),
		];
		
		return $this->threadMessageService->getEntries($threadId, $params);
	}
	
	/**
	 * Get message
	 *
	 * Get a thread's message (owned by the logged user) details
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the post relationships for Eager Loading - Possible values: thread,user. Example: null
	 *
	 * @urlParam threadId int required The thread's ID. Example: 293
	 * @urlParam id int required The thread's message's ID. Example: 3545
	 *
	 * @param $threadId
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($threadId, $id): JsonResponse
	{
		$params = [
			'embed' => request()->input('embed'),
		];
		
		return $this->threadMessageService->getEntry($threadId, $id, $params);
	}
}
