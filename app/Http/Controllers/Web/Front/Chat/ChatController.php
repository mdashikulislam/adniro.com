<?php

namespace App\Http\Controllers\Web\Front\Chat;

use App\Http\Controllers\Web\Front\FrontController;
use App\Models\Post;
use App\Services\Chat\ChatAccess;
use App\Services\Chat\ChatConversations;
use App\Services\Chat\ChatEmailChoice;
use App\Services\Chat\ChatLink;
use App\Services\Chat\ChatMessages;
use App\Services\Chat\ChatOffers;
use App\Services\Chat\ChatPhotos;
use App\Services\Chat\ChatPresenter;
use App\Services\Chat\ChatPrivacy;
use App\Services\Chat\ChatSafety;
use App\Services\Chat\ChatSettings;
use App\Services\Chat\ChatTools;
use App\Services\Chat\ChatUnread;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

/*
 * The chat pages (adniro): the inbox, an open conversation, and "Chat with seller" from a listing.
 * One view; the page's first data is in it, so it needs no extra request.
 */
class ChatController extends FrontController
{
	public function index(Request $request): View
	{
		return $this->page($request);
	}

	public function show(Request $request, string $key): View
	{
		$userId = (int)$request->user()->id;
		$threadId = ChatLink::threadIdFrom($key);
		abort_if($threadId === null, 404);
		$chatThread = ChatAccess::threadFor($threadId, $userId)->load(ChatPresenter::THREAD_RELATIONS);

		return $this->page($request, [
			'open' => ['thread' => ChatPresenter::thread($chatThread, $userId)] + ChatMessages::history($chatThread, $userId),
		]);
	}

	/** An old address with the conversation's number: on to its coded address, for its participants only. */
	public function legacy(Request $request, int $thread): RedirectResponse
	{
		$chatThread = ChatAccess::threadFor($thread, (int)$request->user()->id);

		return redirect()->to(ChatLink::url((int)$chatThread->id), 301);
	}
	
	/**
	 * "Chat with seller" on a listing. The two people have one conversation, whatever the listing: if it exists
	 * it opens, with this listing ready to be the subject of the next message; if not, nothing is saved until
	 * the first message.
	 */
	public function draft(Request $request, int $post): View|RedirectResponse
	{
		$user = $request->user();
		$userId = (int)$user->id;
		$listing = Post::query()->unarchived()->with(['picture', 'category', 'currency', 'user'])->findOrFail($post);

		$existing = empty($listing->user_id) ? null : ChatConversations::between($userId, (int)$listing->user_id);
		$reason = ChatConversations::cannotStartReason($listing, $user);
		if ($reason !== null && $existing === null) {
			flash($reason)->error();

			return redirect()->to(urlGen()->post($listing));
		}

		$about = [
			'post'      => ChatPresenter::post($listing),
			'other'     => ChatPresenter::person($listing->user, (int)$listing->user_id),
			'start_url' => route('chat.start', ['post' => $listing->id]),
		];
		if ($existing === null) {
			return $this->page($request, ['draft' => $about]);
		}

		// Already talking about this listing, or no longer allowed to write: just the conversation
		if ($reason !== null || (int)$existing->post_id === (int)$listing->id) {
			return redirect()->to(ChatLink::url((int)$existing->id));
		}

		$chatThread = ChatAccess::threadFor((int)$existing->id, $userId)->load(ChatPresenter::THREAD_RELATIONS);

		return $this->page($request, [
			'open' => ['thread' => ChatPresenter::thread($chatThread, $userId), 'about' => $about] + ChatMessages::history($chatThread, $userId),
		]);
	}

	private function page(Request $request, array $state = []): View
	{
		$user = $request->user();
		$userId = (int)$user->id;
		$filter = ChatConversations::filterFrom($request->query('filter'));

		$chatState = array_merge([
			'me'            => ['id' => $userId, 'name' => (string)$user->name],
			'filter'        => $filter,
			'inbox'         => ChatConversations::inbox($userId, $filter),
			'open'          => null,
			'draft'         => null,
			'unread_total'  => ChatUnread::total($userId),
			'emails'        => ChatEmailChoice::isOn($user),
			'quick_replies' => array_values((array)trans('chat.quick_replies')),
			// For the owner of the listing a conversation is about, when a question waits for an answer
			'seller_quick_replies' => array_values((array)trans('chat.seller_quick_replies')),
			// The member's away message and saved replies
			'tools'         => ChatTools::of($userId),
			// What the member lets others see: online and typing, and what they have read
			'privacy'       => ChatPrivacy::of($userId),
			'safety'        => ChatSafety::browserPatterns(),
			// Turned off in Admin > Settings > Chat: conversations can be read, nothing can be sent
			'paused'        => !ChatSettings::isEnabled(),
			'limits'        => [
				'photos'         => ChatSettings::photosEnabled(),
				'offers'         => ChatOffers::isEnabled(),
				'offer_max'      => ChatOffers::AMOUNT_MAX,
				'tools_text_max' => ChatTools::TEXT_MAX,
				'saved_replies'  => ChatTools::SAVED_REPLIES_MAX,
				'unsend_minutes' => ChatSettings::unsendMinutes(),
				'body_max'       => ChatMessages::BODY_MAX,
				'photo_max_kb'   => ChatPhotos::MAX_UPLOAD_KB,
				'history_page'   => ChatMessages::PAGE_SIZE,
				'search_min'     => ChatConversations::SEARCH_MIN,
			],
			'urls'          => [
				'inbox'    => route('chat.index'),
				'threads'  => route('chat.api.threads'),
				'open'     => route('chat.api.thread', ['thread' => '__ID__']),
				'messages' => route('chat.api.messages', ['thread' => '__ID__']),
				'read'     => route('chat.api.read', ['thread' => '__ID__']),
				'block'    => route('chat.api.block', ['user' => '__ID__']),
				'report'   => route('chat.api.report', ['thread' => '__ID__']),
				'settings' => route('chat.api.settings'),
				'hide'     => route('chat.api.hide', ['thread' => '__ID__']),
				'unsend'   => route('chat.api.unsend', ['thread' => '__ID__', 'message' => '__MESSAGE__']),
				'offer'    => route('chat.offer', ['post' => '__ID__']),
				'offer_answer' => route('chat.api.offer.answer', ['thread' => '__ID__', 'message' => '__MESSAGE__']),
				'tools'    => route('chat.api.tools'),
				'tidy'     => route('chat.api.tidy', ['thread' => '__ID__']),
				'privacy'  => route('chat.api.privacy'),
				'sold'     => route('chat.api.sold', ['post' => '__ID__']),
				'browse'   => urlGen()->searchWithoutQuery(),
			],
			'text'          => (array)trans('chat.js') + [
				'too_fast'      => trans('chat.error_too_fast'),
				'offer_title'   => trans('chat.make_offer'),
				'offer_invalid' => trans('chat.error_offer_amount'),
				'sold_confirm'  => trans('chat.mark_sold'),
				'saved_replies' => trans('chat.saved_replies'),
				'unblock'       => trans('chat.unblock'),
			],
		], $state);

		MetaTag::set('title', trans('chat.page_title'));
		MetaTag::set('description', trans('chat.page_title'));

		return view('front.chat.index', ['chatState' => $chatState]);
	}
}
