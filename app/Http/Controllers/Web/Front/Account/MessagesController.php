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

namespace App\Http\Controllers\Web\Front\Account;

use App\Http\Controllers\Web\Front\Account\Traits\MessagesTrait;
use App\Http\Requests\Front\ReplyMessageRequest;
use App\Http\Requests\Front\SendMessageRequest;
use App\Services\ThreadService;
use App\Services\UserService;
use Bedigit\Breadcrumbs\BreadcrumbFacade;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class MessagesController extends AccountBaseController
{
	use MessagesTrait;
	
	protected ThreadService $threadService;
	
	private int $perPage = 10;
	
	/**
	 * @param \App\Services\UserService $userService
	 * @param \App\Services\ThreadService $threadService
	 */
	public function __construct(UserService $userService, ThreadService $threadService)
	{
		parent::__construct($userService);
		
		$this->threadService = $threadService;
		
		$perPage = config('settings.pagination.per_page');
		$this->perPage = is_int($perPage) ? $perPage : $this->perPage;
	}
	
	/**
	 * Show all the message threads to the user.
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
	 */
	public function index()
	{
		// Get the threads related to the logged-in user
		$queryParams = [
			'perPage' => request()->integer('perPage'),
			'embed'   => request()->input('embed'),
			'filter'  => request()->input('filter'),
			'sort'    => request()->input('sort'),
		];
		$data = getServiceData($this->threadService->getEntries($queryParams));
		
		$apiMessage = data_get($data, 'message');
		$apiResult = data_get($data, 'result');
		
		$appName = config('settings.app.name', 'Site Name');
		$title = t('messenger_inbox') . ' - ' . $appName;
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', t('messenger_inbox'));
		
		if (isFromAjax()) {
			$threads = (array)data_get($apiResult, 'data');
			$totalThreads = (array)data_get($apiResult, 'meta.total');
			
			$result = [
				'threads' => view('front.account.messenger.threads.threads', ['totalThreads' => $totalThreads, 'threads' => $threads])->render(),
				'links'   => view('front.account.messenger.threads.links', ['apiResult' => $apiResult])->render(),
			];
			
			return ajaxResponse()->json($result);
		}
		
		// Breadcrumb
		BreadcrumbFacade::add(t('messenger'));
		
		return view('front.account.messenger.index', compact('apiResult'));
	}
	
	/**
	 * Shows a message thread.
	 *
	 * @param $id
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function show($id)
	{
		// Get the thread
		$queryParams = [
			'embed'   => 'user,post,messages,participants',
			'perPage' => $this->perPage, // for the thread's messages
		];
		$data = getServiceData($this->threadService->getEntry($id, $queryParams));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Error Found
		if (!data_get($data, 'success')) {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->to(urlGen()->getAccountBasePath() . '/messages');
		}
		
		$thread = data_get($data, 'result');
		
		// Message Collection
		// $errorMessage = data_get($thread, 'messages.message');
		$apiResult = data_get($thread, 'messages.result');
		$messages = (array)data_get($apiResult, 'data');
		$totalMessages = (int)data_get($apiResult, 'meta.total', 0);
		$linksRender = view('front.account.messenger.messages.pagination', ['apiResult' => $apiResult])->render();
		
		// Meta Tags
		MetaTag::set('title', t('Messages Received'));
		MetaTag::set('description', t('Messages Received'));
		
		// Reverse the collection order like Messenger
		$messages = collect($messages)->reverse()->toArray();
		
		if (isFromAjax()) {
			$result = [
				'totalMessages' => $totalMessages,
				'messages'      => view(
					'front.account.messenger.messages.messages',
					[
						'thread'        => $thread,
						'totalMessages' => $totalMessages,
						'messages'      => $messages,
					]
				)->render(),
				'links'         => $linksRender,
			];
			
			return ajaxResponse()->json($result);
		}
		
		return view('front.account.messenger.show', compact('thread', 'totalMessages', 'messages', 'linksRender'));
	}
	
	/**
	 * Stores a new message thread.
	 * Contact the Post's Author
	 * Note: This method does not call with AJAX
	 *
	 * @param $postId
	 * @param \App\Http\Requests\Front\SendMessageRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store($postId, SendMessageRequest $request)
	{
		// Store new thread
		$data = getServiceData($this->threadService->store($postId, $request));
		
		// Parsing the API response
		$message = data_get($data, 'message');
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			$message = $message ?? t('unknown_error');
			flash($message)->error();
			
			return redirect()->back()->withInput($request->except('file_path'));
		}
		
		// Get Post
		$post = data_get($data, 'extra.post');
		
		if (!empty($post)) {
			return redirect()->to(urlGen()->post($post));
		} else {
			return redirect()->back();
		}
	}
	
	/**
	 * Adds a new message to a current thread.
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\ReplyMessageRequest $request
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function update($id, ReplyMessageRequest $request)
	{
		if (!isFromAjax()) {
			return;
		}
		
		// Update the thread
		$data = getServiceData($this->threadService->update($id, $request));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message', t('unknown_error'));
		
		$result = [
			'success' => (bool)data_get($data, 'success'),
			'msg'     => $message,
		];
		
		return ajaxResponse()->json($result, $status);
	}
	
	/**
	 * Actions on the Threads
	 *
	 * @param $threadId
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function actions($threadId = null)
	{
		if (!isFromAjax()) {
			return;
		}
		
		// Get entries ID(s)
		$ids = getSelectedEntryIds($threadId, request()->input('entries'), asString: true);
		$actionType = request()->input('type');
		
		// Bulk actions
		$data = getServiceData($this->threadService->bulkUpdate($ids, $actionType));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message', t('unknown_error'));
		
		$result = [
			'type'    => $actionType,
			'success' => (bool)data_get($data, 'success'),
			'msg'     => $message,
		];
		if (!empty($threadId)) {
			$result['baseUrl'] = request()->url();
		}
		
		return ajaxResponse()->json($result, $status);
	}
	
	/**
	 * Delete Thread
	 *
	 * @param null $threadId
	 * @return \Illuminate\Http\JsonResponse|void
	 */
	public function destroy($threadId = null)
	{
		if (!isFromAjax()) {
			return;
		}
		
		// Get entries ID(s)
		$ids = getSelectedEntryIds($threadId, request()->input('entries'), asString: true);
		
		// Bulk actions
		$data = getServiceData($this->threadService->destroy($ids));
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message', t('unknown_error'));
		
		$result = [
			'type'    => 'delete',
			'success' => (bool)data_get($data, 'success'),
			'msg'     => $message,
		];
		if (!empty($threadId)) {
			$result['baseUrl'] = request()->url();
		}
		
		return ajaxResponse()->json($result, $status);
	}
}
