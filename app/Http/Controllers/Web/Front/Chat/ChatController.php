<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Web\Front\FrontController;
use App\Models\Post;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatConversations;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatPhotos;
use App\Services\Chat\ChatPresenter;
use App\Services\Chat\ChatUnread;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

/*
 * The chat pages (adniro): the inbox, an open conversation, and a new conversation about a listing (nothing is
 * saved until the first message). One view; the page's first data is in it, so it needs no extra request.
 */
class ChatController extends FrontController
{
	public function index(Request $request): View
	{
		return $this->page($request);
	}

	public function show(Request $request, int $thread): View
	{
		$userId = (int)$request->user()->id;
		$chatThread = ChatAccess::threadFor($thread, $userId)->load(ChatPresenter::THREAD_RELATIONS);

		return $this->page($request, [
			'open' => ['thread' => ChatPresenter::thread($chatThread, $userId)] + ChatMessages::history($chatThread),
		]);
	}

	public function draft(Request $request, int $post): View|RedirectResponse
	{
		$user = $request->user();
		$listing = Post::query()->unarchived()->with(['picture', 'user'])->findOrFail($post);

		$existing = ChatConversations::find((int)$listing->id, (int)$user->id);
		if ($existing !== null) {
			return redirect()->route('chat.show', ['thread' => $existing->id]);
		}

		$reason = ChatConversations::cannotStartReason($listing, $user);
		if ($reason !== null) {
			flash($reason)->error();

			return redirect()->to(urlGen()->post($listing));
		}

		return $this->page($request, [
			'draft' => [
				'post'      => ChatPresenter::post($listing),
				'other'     => ChatPresenter::person($listing->user, (int)$listing->user_id),
				'start_url' => route('chat.start', ['post' => $listing->id]),
			],
		]);
	}

	private function page(Request $request, array $state = []): View
	{
		$user = $request->user();
		$userId = (int)$user->id;
		$filter = $request->query('filter') === ChatConversations::FILTER_UNREAD ? ChatConversations::FILTER_UNREAD : null;

		$chatState = array_merge([
			'me'            => ['id' => $userId, 'name' => (string)$user->name],
			'filter'        => $filter,
			'inbox'         => ChatConversations::inbox($userId, $filter),
			'open'          => null,
			'draft'         => null,
			'unread_total'  => ChatUnread::total($userId),
			'quick_replies' => array_values((array)trans('chat.quick_replies')),
			'limits'        => [
				'body_max'       => ChatMessages::BODY_MAX,
				'photo_max_kb'   => ChatPhotos::MAX_UPLOAD_KB,
				'history_page'   => ChatMessages::PAGE_SIZE,
			],
			'urls'          => [
				'inbox'    => route('chat.index'),
				'thread'   => route('chat.show', ['thread' => '__ID__']),
				'threads'  => route('chat.api.threads'),
				'open'     => route('chat.api.thread', ['thread' => '__ID__']),
				'messages' => route('chat.api.messages', ['thread' => '__ID__']),
				'read'     => route('chat.api.read', ['thread' => '__ID__']),
				'block'    => route('chat.api.block', ['user' => '__ID__']),
				'report'   => route('chat.api.report', ['thread' => '__ID__']),
				'browse'   => urlGen()->searchWithoutQuery(),
			],
			'text'          => (array)trans('chat.js') + ['too_fast' => trans('chat.error_too_fast')],
		], $state);

		MetaTag::set('title', trans('chat.page_title'));
		MetaTag::set('description', trans('chat.page_title'));

		return view('front.chat.index', ['chatState' => $chatState]);
	}
}
